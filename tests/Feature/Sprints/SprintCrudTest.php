<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('project owners can create a sprint and it appears in the project page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint 1',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-14',
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('sprints', [
        'project_id' => $project->id,
        'name' => 'Sprint 1',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-14',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.sprints', 1)
            ->where('project.sprints.0.name', 'Sprint 1')
            ->where('project.sprints.0.startDate', '2026-09-01')
            ->where('project.sprints.0.endDate', '2026-09-14')
        );
});

test('sprints are listed ordered by start date ascending', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create(['name' => 'Tercero', 'start_date' => '2026-10-01', 'end_date' => '2026-10-07']);
    Sprint::factory()->for($project)->create(['name' => 'Primero', 'start_date' => '2026-09-01', 'end_date' => '2026-09-07']);
    Sprint::factory()->for($project)->create(['name' => 'Segundo', 'start_date' => '2026-09-15', 'end_date' => '2026-09-21']);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.sprints', 3)
            ->where('project.sprints.0.name', 'Primero')
            ->where('project.sprints.1.name', 'Segundo')
            ->where('project.sprints.2.name', 'Tercero')
        );
});

test('sprints can not be created without a valid name', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => '',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-14',
    ])->assertSessionHasErrors('name');

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => '   ',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-14',
    ])->assertSessionHasErrors('name');

    expect(Sprint::query()->count())->toBe(0);
});

test('sprints can not be created with an end date before the start date', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint 1',
        'start_date' => '2026-09-14',
        'end_date' => '2026-09-01',
    ]);

    $response->assertSessionHasErrors('end_date');
    expect(Sprint::query()->count())->toBe(0);
});

test('sprints can not be created with non-date values', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint 1',
        'start_date' => 'not-a-date',
        'end_date' => '2026-09-14',
    ]);

    $response->assertSessionHasErrors('start_date');
    expect(Sprint::query()->count())->toBe(0);
});

test('sprints accept past dates and overlapping periods', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Pasado',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-15',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Solapado',
        'start_date' => '2025-01-10',
        'end_date' => '2025-01-20',
    ])->assertSessionHasNoErrors();

    expect(Sprint::query()->count())->toBe(2);
});

test('project owners can edit a sprint name and dates', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Antes',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);

    $response = $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Después',
        'start_date' => '2026-09-02',
        'end_date' => '2026-09-10',
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('sprints', [
        'id' => $sprint->id,
        'name' => 'Después',
        'start_date' => '2026-09-02',
        'end_date' => '2026-09-10',
    ]);
});

test('sprints can not be updated with an end date before the start date', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Original',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);

    $response = $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Cambiado',
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-01',
    ]);

    $response->assertSessionHasErrors('end_date');
    $this->assertDatabaseHas('sprints', [
        'id' => $sprint->id,
        'name' => 'Original',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ]);
});

test('sprints from another project are not accessible for update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($otherProject)->create(['name' => 'Ajeno']);

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Cambiado',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-07',
    ])->assertNotFound();

    $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'name' => 'Ajeno']);
});

test('project owners can delete a sprint without affecting the project or other sprints', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create(['name' => 'A eliminar']);
    $otherSprint = Sprint::factory()->for($project)->create(['name' => 'Permanece']);

    $response = $this->actingAs($user)->delete("/projects/{$project->id}/sprints/{$sprint->id}");

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    $this->assertDatabaseHas('projects', ['id' => $project->id]);
    $this->assertDatabaseHas('sprints', ['id' => $otherSprint->id, 'name' => 'Permanece']);
});

test('sprints from another project are not accessible for deletion', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($otherProject)->create(['name' => 'Ajeno']);

    $this->actingAs($user)->delete("/projects/{$project->id}/sprints/{$sprint->id}")->assertNotFound();

    $this->assertDatabaseHas('sprints', ['id' => $sprint->id, 'name' => 'Ajeno']);
});
