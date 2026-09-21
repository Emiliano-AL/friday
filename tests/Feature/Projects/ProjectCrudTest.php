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
