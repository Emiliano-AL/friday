<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('users can create standalone tasks without a project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'title' => 'Comprar café para la oficina',
            'assignee_id' => $user->id,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Comprar café para la oficina',
        'project_id' => null,
        'assignee_id' => $user->id,
    ]);
});

test('standalone tasks must be assigned to their creator', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->post('/tasks', [
            'title' => 'Tarea ajena',
            'assignee_id' => $other->id,
        ])
        ->assertSessionHasErrors(['assignee_id']);

    $this->actingAs($user)
        ->post('/tasks', ['title' => 'Sin responsable'])
        ->assertSessionHasErrors(['assignee_id']);
});

test('standalone tasks cannot have a sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $sprint = $project->sprints()->create([
        'name' => 'Sprint 1',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($user)
        ->post('/tasks', [
            'title' => 'Tarea con sprint ilegal',
            'assignee_id' => $user->id,
            'sprint_id' => $sprint->id,
        ])
        ->assertSessionHasErrors(['sprint_id']);
});

test('project tasks still require assignee and sprint to belong to the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $foreignSprint = $otherProject->sprints()->create([
        'name' => 'Sprint ajeno',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($user)
        ->post('/tasks', [
            'title' => 'Tarea con sprint ajeno',
            'project_id' => $project->id,
            'sprint_id' => $foreignSprint->id,
        ])
        ->assertSessionHasErrors(['sprint_id']);
});

test('assignees can update and delete their standalone tasks', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'assignee')->create();

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['title' => 'Renombrada', 'status' => 'in_progress'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Renombrada',
        'status' => 'in_progress',
    ]);

    $this->actingAs($user)->delete("/tasks/{$task->id}");

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('other users cannot update or delete standalone tasks', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $task = Task::factory()->standalone()->for($owner, 'assignee')->create();

    $this->actingAs($stranger)
        ->put("/tasks/{$task->id}", ['title' => 'Secuestrada'])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete("/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => $task->title]);
});
