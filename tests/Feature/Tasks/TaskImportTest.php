<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

function importFixture(): string
{
    return file_get_contents(base_path('tests/Fixtures/task-import-example.md'));
}

/**
 * Build a one-task import file; $fields maps bullet label => value (null omits the bullet).
 *
 * @param  array<string, string|null>  $fields
 */
function importTask(string $title, array $fields = [], string $section = 'Media'): string
{
    $fields += [
        'Tipo' => 'Tarea',
        'Prioridad' => $section,
        'Responsable' => 'Emiliano',
        'Descripción' => 'Algo pasa. Hacer algo. (Origen: 1-1)',
    ];

    $bullets = collect($fields)
        ->filter(fn (?string $value) => $value !== null)
        ->map(fn (string $value, string $label) => "- **{$label}:** {$value}")
        ->implode("\n");

    return "## Prioridad {$section}\n\n### {$title}\n{$bullets}\n";
}

/**
 * @return array<string, mixed>
 */
function previewFor(User $user, string $content, ?Project $project = null): array
{
    $url = $project ? "/projects/{$project->id}/tasks/import/preview" : '/tasks/import/preview';

    test()->actingAs($user)->post($url, ['content' => $content])->assertSessionHasNoErrors();

    return session('importPreview');
}

// --- US1: import into a project ---

test('previewing the example lists its tasks and exclusions without creating anything', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $preview = previewFor($user, importFixture(), $project);

    expect(collect($preview['tasks'])->pluck('title')->all())->toBe([
        'Hacer atómica la generación de PIN',
        'Escapar comodines LIKE en las búsquedas',
        'Tests de `viewAny` para los roles purchasing y warehouse',
    ])
        ->and(collect($preview['tasks'])->pluck('type')->all())->toBe(['bug', 'bug', 'test'])
        ->and(collect($preview['tasks'])->pluck('priority')->all())->toBe(['high', 'medium', 'low'])
        ->and($preview['excluded'])->toHaveCount(1)
        ->and($preview['excluded'][0]['name'])->toBe('Truncamiento de `StockLevel.quantity`')
        ->and($preview['counts'])->toBe(['importable' => 3, 'total' => 3]);

    $this->assertDatabaseCount('tasks', 0);
});

test('confirming the import creates the tasks in the project backlog', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->post("/projects/{$project->id}/tasks/import", ['content' => importFixture()])
        ->assertSessionHas('success', 'Se importaron 3 tareas.');

    $tasks = Task::query()->orderBy('id')->get();
    expect($tasks)->toHaveCount(3)
        ->and($tasks->pluck('status')->unique()->all())->toBe([TaskStatus::Backlog])
        ->and($tasks->pluck('project_id')->unique()->all())->toBe([$project->id])
        ->and($tasks->pluck('sprint_id')->unique()->all())->toBe([null])
        ->and($tasks->pluck('author_id')->unique()->all())->toBe([$user->id]);

    $description = json_decode($tasks->first()->description, true);
    expect($description['type'])->toBe('doc')
        ->and($description['content'][0]['content'][0])->toBe([
            'type' => 'text',
            'text' => 'generatePin()',
            'marks' => [['type' => 'code']],
        ])
        ->and($tasks->first()->description)->toContain('(Origen: 4-3 W1)');
});

test('the responsible is matched by name among the project people', function () {
    $owner = User::factory()->create(['name' => 'Ana']);
    $emiliano = User::factory()->create(['name' => 'Emiliano Landa']);
    $project = Project::factory()->for($owner, 'owner')->hasAttached($emiliano, [], 'members')->create();

    $content = importTask('Primera', ['Responsable' => 'emiliano']).importTask('Segunda', ['Responsable' => 'Lucía']);
    $preview = previewFor($owner, $content, $project);

    expect($preview['tasks'][0]['assignee'])->toBe(['id' => $emiliano->id, 'name' => 'Emiliano Landa'])
        ->and($preview['tasks'][1]['assignee'])->toBeNull()
        ->and($preview['tasks'][1]['state'])->toBe('warning')
        ->and($preview['tasks'][1]['messages'])->toContain('No encontramos a «Lucía» en el proyecto; quedará sin responsable.');

    $this->actingAs($owner)->post("/projects/{$project->id}/tasks/import", ['content' => $content]);

    expect(Task::query()->where('title', 'Primera')->value('assignee_id'))->toBe($emiliano->id)
        ->and(Task::query()->where('title', 'Segunda')->value('assignee_id'))->toBeNull();
});

test('importing the same file twice creates no duplicates', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks/import", ['content' => importFixture()]);
    $preview = previewFor($user, importFixture(), $project);

    expect(collect($preview['tasks'])->pluck('state')->unique()->all())->toBe(['duplicate'])
        ->and($preview['counts']['importable'])->toBe(0);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/tasks/import", ['content' => importFixture()])
        ->assertSessionHasErrors(['content' => 'No hay tareas válidas para importar.']);

    $this->assertDatabaseCount('tasks', 3);
});

test('only members of an active project can import into it', function () {
    $stranger = User::factory()->create();
    $project = Project::factory()->create();
    $archived = Project::factory()->for($stranger, 'owner')->create(['status' => ProjectStatus::Archived]);

    $this->actingAs($stranger)
        ->post("/projects/{$project->id}/tasks/import", ['content' => importFixture()])
        ->assertNotFound();

    $this->actingAs($stranger)
        ->post("/projects/{$archived->id}/tasks/import", ['content' => importFixture()])
        ->assertForbidden();

    $this->assertDatabaseCount('tasks', 0);
});

test('a failure while creating leaves no task behind', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    Task::creating(function (Task $task) {
        if ($task->title === 'Escapar comodines LIKE en las búsquedas') {
            throw new RuntimeException('boom');
        }
    });

    $this->actingAs($user)
        ->post("/projects/{$project->id}/tasks/import", ['content' => importFixture()])
        ->assertServerError();

    $this->assertDatabaseCount('tasks', 0);
});

// --- US2: standalone tasks ---

test('standalone imports create backlog tasks without a project, assignable only to oneself', function () {
    $user = User::factory()->create(['name' => 'Emiliano']);

    $content = importTask('Mía').importTask('Ajena', ['Responsable' => 'Ana']);
    $preview = previewFor($user, $content);

    expect($preview['tasks'][0]['assignee'])->toBe(['id' => $user->id, 'name' => 'Emiliano'])
        ->and($preview['tasks'][1]['assignee'])->toBeNull();

    $this->actingAs($user)
        ->post('/tasks/import', ['content' => $content])
        ->assertSessionHas('success', 'Se importaron 2 tareas.');

    expect(Task::query()->whereNull('project_id')->where('status', TaskStatus::Backlog)->count())->toBe(2)
        ->and(Task::query()->where('title', 'Mía')->value('assignee_id'))->toBe($user->id);
});

test('standalone duplicates only consider the user own standalone tasks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Task::factory()->create(['title' => 'Mía', 'project_id' => null, 'author_id' => $user->id]);
    Task::factory()->create(['title' => 'De otro', 'project_id' => null, 'author_id' => $other->id, 'assignee_id' => $other->id]);

    $preview = previewFor($user, importTask(' mía ').importTask('De otro'));

    expect($preview['tasks'][0]['state'])->toBe('duplicate')
        ->and($preview['tasks'][1]['state'])->not->toBe('duplicate');
});

// --- US3: format errors ---

test('per-task format problems are reported in spanish', function (string $content, string $state, string $message) {
    $preview = previewFor(User::factory()->create(), $content);

    expect($preview['tasks'][0]['state'])->toBe($state)
        ->and($preview['tasks'][0]['messages'])->toContain($message);
})->with([
    'invalid type' => [importTask('A', ['Tipo' => 'Mejora']), 'error', 'Tipo «Mejora» no es válido (Tarea, Feature, Bug, Test).'],
    'invalid priority' => [importTask('A', ['Prioridad' => 'Crítica']), 'error', 'Prioridad «Crítica» no es válida (Baja, Media, Alta, Urgente).'],
    'missing responsible' => [importTask('A', ['Responsable' => null]), 'error', 'Falta el campo Responsable.'],
    'missing description' => [importTask('A', ['Descripción' => null]), 'error', 'Falta el campo Descripción.'],
    'title too long' => [importTask(str_repeat('a', 256)), 'error', 'El nombre supera 255 caracteres.'],
    'priority differs from section' => [importTask('A', ['Prioridad' => 'Alta'], 'Media'), 'warning', 'La prioridad Alta no coincide con la sección Media; se usará Alta.'],
    'missing origin' => [importTask('A', ['Descripción' => 'Sin origen.']), 'warning', 'La descripción no incluye (Origen: ...).'],
]);

test('the warning priority from the bullet wins over the section', function () {
    $preview = previewFor(User::factory()->create(), importTask('A', ['Prioridad' => 'Alta'], 'Media'));

    expect($preview['tasks'][0]['priority'])->toBe('high');
});

test('repeated names in the file keep only the first one', function () {
    $preview = previewFor(User::factory()->create(), importTask('Igual').importTask('igual'));

    expect($preview['tasks'][0]['state'])->not->toBe('repeated')
        ->and($preview['tasks'][1]['state'])->toBe('repeated')
        ->and($preview['counts'])->toBe(['importable' => 1, 'total' => 2]);
});

test('files without tasks or with too many are rejected', function (string $content, string $message) {
    $this->actingAs(User::factory()->create())
        ->post('/tasks/import/preview', ['content' => $content])
        ->assertSessionHasErrors(['content' => $message]);
})->with([
    'no tasks' => ['# Solo un título', 'No encontramos tareas con el formato esperado.'],
    'empty' => ['', 'Selecciona o pega un archivo de tareas.'],
    'too many' => [fn () => collect(range(1, 201))->map(fn (int $i) => importTask("Tarea {$i}"))->implode("\n"), 'El archivo tiene más de 200 tareas; divídelo en varios.'],
]);

test('confirming a mixed file imports only the valid tasks', function () {
    $user = User::factory()->create();
    $content = importTask('Válida 1').importTask('Mala', ['Tipo' => 'Mejora']).importTask('Válida 2');

    $this->actingAs($user)
        ->post('/tasks/import', ['content' => $content])
        ->assertSessionHas('success', 'Se importaron 2 tareas.');

    expect(Task::query()->pluck('title')->sort()->values()->all())->toBe(['Válida 1', 'Válida 2']);
});

test('the size limit is measured in bytes, not characters', function () {
    $content = importTask('A').str_repeat('ñ', 600000);

    expect(mb_strlen($content))->toBeLessThan(1048576)
        ->and(strlen($content))->toBeGreaterThan(1048576);

    $this->actingAs(User::factory()->create())
        ->post('/tasks/import/preview', ['content' => $content])
        ->assertSessionHasErrors(['content' => 'El archivo no puede superar 1 MB.']);
});

test('the preview sent to the browser carries the excerpt but not the full description', function () {
    $preview = previewFor(User::factory()->create(), importTask('A'));

    expect($preview['tasks'][0])->not->toHaveKey('description')
        ->and($preview['tasks'][0]['excerpt'])->toBe('Algo pasa. Hacer algo. (Origen: 1-1)');
});

test('the responsible is matched regardless of accents', function () {
    $owner = User::factory()->create();
    $lucia = User::factory()->create(['name' => 'Lucía Pérez']);
    $project = Project::factory()->for($owner, 'owner')->hasAttached($lucia, [], 'members')->create();

    $preview = previewFor($owner, importTask('A', ['Responsable' => 'Lucia']), $project);

    expect($preview['tasks'][0]['assignee'])->toBe(['id' => $lucia->id, 'name' => 'Lucía Pérez']);
});
