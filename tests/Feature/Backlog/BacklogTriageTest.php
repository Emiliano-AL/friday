<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('the author can triage their own standalone pending changing priority and points', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'priority' => 'low',
        'points' => null,
    ]);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['priority' => 'high', 'points' => 5])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'priority' => 'high',
        'points' => 5,
    ]);
});

test('a third user can not view, update or delete the standalone pending of another user', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();
    $task = Task::factory()->standalone()->for($author, 'author')->create([
        'status' => 'backlog',
        'title' => 'Pendiente ajeno',
    ]);

    $this->actingAs($stranger)->get("/tasks/{$task->id}")->assertNotFound();

    $this->actingAs($stranger)
        ->put("/tasks/{$task->id}", ['title' => 'Secuestrada'])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete("/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Pendiente ajeno']);
});

test('triage can assign a standalone pending to a project the user belongs to', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['project_id' => $project->id])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'project_id' => $project->id]);
});

test('triage can not move a pending into a project the user is not a member of', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $foreignProject = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'title' => 'Pendiente propio',
        'priority' => 'medium',
        'points' => null,
    ]);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['project_id' => $foreignProject->id])
        ->assertNotFound();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_id' => null,
        'title' => 'Pendiente propio',
        'priority' => 'medium',
        'points' => null,
    ]);
});

test('a standalone pending can not be assigned to a sprint without a project', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();
    $sprint = $project->sprints()->create([
        'name' => 'Sprint 9',
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-17',
    ]);
    $task = Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'sprint_id' => null,
    ]);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['sprint_id' => $sprint->id])
        ->assertSessionHasErrors('sprint_id');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_id' => null,
        'sprint_id' => null,
    ]);
});

test('triage can not assign a pending to a sprint of another project', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();
    $otherProject = Project::factory()->for($owner, 'owner')->create();
    $foreignSprint = $otherProject->sprints()->create([
        'name' => 'Sprint ajeno',
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-17',
    ]);
    $task = Task::factory()->for($project)->for($user, 'author')->create([
        'status' => 'backlog',
        'sprint_id' => null,
    ]);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", [
            'project_id' => $project->id,
            'sprint_id' => $foreignSprint->id,
        ])
        ->assertSessionHasErrors('sprint_id');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_id' => $project->id,
        'sprint_id' => null,
    ]);
});

test('the author can delete their standalone pending', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)->delete("/tasks/{$task->id}");

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('a third user can not delete the standalone pending of another user', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();
    $task = Task::factory()->standalone()->for($author, 'author')->create(['status' => 'backlog']);

    $this->actingAs($stranger)
        ->delete("/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id]);
});
