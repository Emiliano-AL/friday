<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function runDescriptionConversion(): void
{
    $migration = include database_path('migrations/2026_09_30_000000_convert_long_text_fields_to_block_content.php');

    $migration->up();
}

function paragraphText(string $json): ?string
{
    $decoded = json_decode($json, true);

    return $decoded['blocks'][0]['data']['text'] ?? null;
}

test('plain text descriptions are wrapped as a paragraph block in all three fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => 'Proyecto <con> "comillas" & especiales']);
    $sprint = Sprint::factory()->for($project)->create(['goal' => 'Objetivo del sprint']);
    $task = Task::factory()->for($project)->create(['description' => 'Descripción de la tarea']);

    runDescriptionConversion();

    expect(paragraphText($project->refresh()->description))->toBe('Proyecto <con> "comillas" & especiales');
    expect(paragraphText($sprint->refresh()->goal))->toBe('Objetivo del sprint');
    expect(paragraphText($task->refresh()->description))->toBe('Descripción de la tarea');

    foreach ([$project->description, $sprint->goal, $task->description] as $json) {
        expect(json_decode($json, true)['blocks'][0]['type'] ?? null)->toBe('paragraph');
    }
});

test('valid block json is left untouched by the conversion', function () {
    $content = json_encode([
        'time' => 1727654400000,
        'blocks' => [
            ['type' => 'header', 'data' => ['text' => 'Encabezado', 'level' => 2]],
            ['type' => 'paragraph', 'data' => ['text' => 'Párrafo']],
        ],
        'version' => '2.31',
    ], JSON_THROW_ON_ERROR);

    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => $content]);
    $sprint = Sprint::factory()->for($project)->create(['goal' => $content]);
    $task = Task::factory()->for($project)->create(['description' => $content]);

    runDescriptionConversion();

    expect($project->refresh()->description)->toBe($content);
    expect($sprint->refresh()->goal)->toBe($content);
    expect($task->refresh()->description)->toBe($content);
});

test('non block json strings are wrapped as a paragraph', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => '{"just":"an","object":true}']);

    runDescriptionConversion();

    expect(paragraphText($project->refresh()->description))->toBe('{"just":"an","object":true}');
});

test('null values stay null and the conversion is idempotent', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => null]);
    $sprint = Sprint::factory()->for($project)->create(['goal' => null]);
    $task = Task::factory()->for($project)->create(['description' => null]);

    runDescriptionConversion();
    runDescriptionConversion();

    expect($project->refresh()->description)->toBeNull();
    expect($sprint->refresh()->goal)->toBeNull();
    expect($task->refresh()->description)->toBeNull();

    $project->update(['description' => 'Texto plano']);
    runDescriptionConversion();
    $firstPass = $project->refresh()->description;
    runDescriptionConversion();

    expect($project->refresh()->description)->toBe($firstPass);
    expect(paragraphText($firstPass))->toBe('Texto plano');
});

test('existing rows already migrated keep their content after a second run', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => 'Original']);

    DB::table('projects')->where('id', $project->id)->update(['description' => 'Cambiado fuera del modelo']);

    runDescriptionConversion();

    expect(paragraphText($project->refresh()->description))->toBe('Cambiado fuera del modelo');
});
