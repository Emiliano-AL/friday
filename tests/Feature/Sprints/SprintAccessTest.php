<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('collaborators can view sprints but never write them', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();
    $sprint = Sprint::factory()->for($project)->create(['name' => 'Original']);

    $this->actingAs($collaborator)
        ->get("/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.sprints', 1)
        );

    $this->actingAs($collaborator)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint intruso',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ])->assertNotFound();

    $this->actingAs($collaborator)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Cambiado',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ])->assertNotFound();

    $this->actingAs($collaborator)->delete("/projects/{$project->id}/sprints/{$sprint->id}")->assertNotFound();

    $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'name' => 'Original']);
    expect(Sprint::query()->count())->toBe(1);
});

test('non members can not access the project page or its sprints', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create();

    $this->actingAs($outsider)->get("/projects/{$project->id}")->assertNotFound();

    $this->actingAs($outsider)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint intruso',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ])->assertNotFound();

    $this->actingAs($outsider)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Cambiado',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ])->assertNotFound();

    $this->actingAs($outsider)->delete("/projects/{$project->id}/sprints/{$sprint->id}")->assertNotFound();

    expect(Sprint::query()->count())->toBe(1);
});

test('owner writes are blocked with a clear message when the project is not active', function (string $state) {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->$state()->create();
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Original',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);

    $this->actingAs($owner)->post("/projects/{$project->id}/sprints", [
        'name' => 'Nuevo',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
    ])->assertSessionHasErrors([
        'project' => 'El proyecto no admite cambios en su estado actual.',
    ]);
    expect(Sprint::query()->count())->toBe(1);

    $this->actingAs($owner)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Cambiado',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
    ])->assertSessionHasErrors([
        'project' => 'El proyecto no admite cambios en su estado actual.',
    ]);
    $this->assertDatabaseHas('sprints', [
        'id' => $sprint->id,
        'name' => 'Original',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);

    $this->actingAs($owner)->delete("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertSessionHasErrors([
            'project' => 'El proyecto no admite cambios en su estado actual.',
        ]);
    $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'name' => 'Original']);
})->with(['archived', 'completed']);

test('sprints remain visible for members when the project is not active', function (string $state, bool $asCollaborator) {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->$state()->create();
    if ($asCollaborator) {
        $project->members()->attach($collaborator);
    }
    Sprint::factory()->for($project)->create();

    $viewer = $asCollaborator ? $collaborator : $owner;

    $this->actingAs($viewer)
        ->get("/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.sprints', 1)
        );
})->with([
    'archived as owner' => ['archived', false],
    'completed as owner' => ['completed', false],
    'archived as collaborator' => ['archived', true],
    'completed as collaborator' => ['completed', true],
]);
