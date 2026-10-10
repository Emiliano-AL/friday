<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

test('projects index can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/projects');

    $response->assertStatus(200);
});

test('new projects can be created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => 'Lanzamiento Q4',
        'description' => 'Campaña de lanzamiento del trimestre',
    ]);

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'owner_id' => $user->id,
        'title' => 'Lanzamiento Q4',
        'description' => 'Campaña de lanzamiento del trimestre',
        'status' => ProjectStatus::Active->value,
    ]);
});

test('projects can be created without a description', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => 'Proyecto sin descripción',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('projects', [
        'title' => 'Proyecto sin descripción',
        'description' => null,
    ]);
});

test('projects can not be created without a title', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => '',
    ]);

    $response->assertSessionHasErrors('title');
    expect(Project::query()->count())->toBe(0);
});

test('projects can not be created with a title longer than 255 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => str_repeat('a', 256),
    ]);

    $response->assertSessionHasErrors('title');
    expect(Project::query()->count())->toBe(0);
});

test('projects index lists owned and member projects but never foreign ones', function () {
    $user = User::factory()->create();
    $collaborator = User::factory()->create();
    $foreign = User::factory()->create();

    $owned = Project::factory()->for($user, 'owner')->create(['title' => 'Proyecto propio']);
    $member = Project::factory()->hasAttached($collaborator, [], 'members')->create(['title' => 'Proyecto colaborador']);
    $foreignProject = Project::factory()->for($foreign, 'owner')->create(['title' => 'Proyecto ajeno']);

    $response = $this->actingAs($collaborator)->get('/projects');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Projects/Index')
        ->has('projects', 1)
        ->where('projects.0.id', $member->id)
        ->where('projects.0.title', 'Proyecto colaborador')
        ->where('projects.0.progress', 0)
        ->missing('projects.1')
    );

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $owned->id)
        );

    expect($foreignProject->title)->toBe('Proyecto ajeno');
});

test('project owners can edit the title and description', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Antiguo']);

    $response = $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => 'Nuevo título',
        'description' => 'Nueva descripción',
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'title' => 'Nuevo título',
        'description' => 'Nueva descripción',
    ]);
});

test('non-owners can not edit or update projects', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $foreign = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create(['title' => 'Original']);

    $this->actingAs($collaborator)->get("/projects/{$project->id}/edit")->assertNotFound();
    $this->actingAs($collaborator)->put("/projects/{$project->id}", ['title' => 'Cambiado'])->assertNotFound();

    $this->actingAs($foreign)->get("/projects/{$project->id}/edit")->assertNotFound();
    $this->actingAs($foreign)->put("/projects/{$project->id}", ['title' => 'Cambiado'])->assertNotFound();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Original']);
});

test('project owners can delete projects and memberships are dissolved', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->withMembers(2)->create();

    $response = $this->actingAs($user)->delete("/projects/{$project->id}");

    $response->assertRedirect(route('projects.index', absolute: false));

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseCount('project_user', 0);
});

test('collaborators can view the project page', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();

    $response = $this->actingAs($collaborator)->get("/projects/{$project->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Projects/Show'));
});

test('projects can not be created with a whitespace-only title', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => '   ',
    ]);

    $response->assertSessionHasErrors('title');
    expect(Project::query()->count())->toBe(0);
});

test('projects can not be updated with a whitespace-only title', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Original']);

    $response = $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => '   ',
    ]);

    $response->assertSessionHasErrors('title');
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Original']);
});

test('un proyecto se crea con clave color icono y fecha de término completos', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => 'Lanzamiento Q4',
        'key' => 'frid',
        'color' => '#8b5cf6',
        'icon' => 'rocket',
        'target_date' => '2026-12-31',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('projects', [
        'title' => 'Lanzamiento Q4',
        'key' => 'FRID',
        'color' => '#8b5cf6',
        'icon' => 'rocket',
        'target_date' => '2026-12-31',
    ]);
});

test('la clave duplicada del mismo propietario se rechaza al crear', function () {
    $user = User::factory()->create();
    Project::factory()->for($user, 'owner')->create(['key' => 'FRID']);

    $this->actingAs($user)->post('/projects', [
        'title' => 'Duplicado mayúsculas',
        'key' => 'FRID',
    ])->assertSessionHasErrors('key');

    $this->actingAs($user)->post('/projects', [
        'title' => 'Duplicado minúsculas',
        'key' => 'frid',
    ])->assertSessionHasErrors('key');

    expect(Project::query()->count())->toBe(1);
});

test('la clave duplicada del mismo propietario se rechaza al actualizar salvo la propia', function () {
    $user = User::factory()->create();
    $first = Project::factory()->for($user, 'owner')->create(['key' => 'FRID']);
    $second = Project::factory()->for($user, 'owner')->create(['key' => 'WEB']);

    $this->actingAs($user)->put("/projects/{$second->id}", [
        'title' => $second->title,
        'key' => 'FRID',
    ])->assertSessionHasErrors('key');

    $this->actingAs($user)->put("/projects/{$second->id}", [
        'title' => $second->title,
        'key' => 'WEB',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', ['id' => $first->id, 'key' => 'FRID']);
    $this->assertDatabaseHas('projects', ['id' => $second->id, 'key' => 'WEB']);
});

test('la misma clave es válida para otro propietario', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($owner, 'owner')->create(['key' => 'FRID']);

    $response = $this->actingAs($other)->post('/projects', [
        'title' => 'Clave compartida',
        'key' => 'FRID',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('projects', ['key' => 'FRID', 'owner_id' => $other->id]);
});

test('la clave rechaza longitudes inválidas y caracteres no alfanuméricos', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', [
        'title' => 'Clave corta',
        'key' => 'A',
    ])->assertSessionHasErrors('key');

    $this->actingAs($user)->post('/projects', [
        'title' => 'Clave larga',
        'key' => 'ABCDEF',
    ])->assertSessionHasErrors('key');

    $this->actingAs($user)->post('/projects', [
        'title' => 'Clave con guion',
        'key' => 'FR-ID',
    ])->assertSessionHasErrors('key');

    expect(Project::query()->count())->toBe(0);
});

test('la clave se normaliza a mayúsculas al guardar', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['key' => 'WEB']);

    $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => $project->title,
        'key' => 'frid',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'key' => 'FRID']);
});

test('se rechazan color e icono fuera del conjunto permitido', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', [
        'title' => 'Color inválido',
        'color' => '#000000',
    ])->assertSessionHasErrors('color');

    $this->actingAs($user)->post('/projects', [
        'title' => 'Icono inválido',
        'icon' => 'not_an_icon',
    ])->assertSessionHasErrors('icon');

    expect(Project::query()->count())->toBe(0);
});

test('se rechaza una fecha de término imposible', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/projects', [
        'title' => 'Fecha imposible',
        'target_date' => '2026-02-31',
    ]);

    $response->assertSessionHasErrors('target_date');
    expect(Project::query()->count())->toBe(0);
});

test('actualizar con fecha de término vacía la deja sin valor', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['target_date' => '2026-12-31']);

    $response = $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => $project->title,
        'target_date' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'target_date' => null]);
});

test('quien no es propietario no puede actualizar la identidad y recibe 404', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create([
        'key' => 'FRID',
        'color' => '#8b5cf6',
        'icon' => 'rocket',
        'target_date' => '2026-12-31',
    ]);

    $this->actingAs($collaborator)->put("/projects/{$project->id}", [
        'title' => 'Cambiado',
        'key' => 'OTRO',
        'color' => '#ef4444',
        'icon' => 'bug_report',
        'target_date' => '2027-01-01',
    ])->assertNotFound();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'title' => $project->title,
        'key' => 'FRID',
        'color' => '#8b5cf6',
        'icon' => 'rocket',
        'target_date' => '2026-12-31',
    ]);
});
