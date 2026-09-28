<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('the tasks index page renders with tasks and projects for the user', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
        );
});

test('tasks outside the user scope are not listed', function () {
    $user = User::factory()->create();
    $foreign = User::factory()->create();
    $foreignProject = Project::factory()->for($foreign, 'owner')->create();
    Task::factory()->for($foreignProject)->create();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page->has('tasks', 0));
});

test('a task can be partially updated with only its status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)
        ->put("/tasks/{$task->id}", ['status' => 'in_progress'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
});

test('the task detail endpoint returns the task with its comments as JSON', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();
    $task->comments()->create(['user_id' => $user->id, 'body' => 'Primer comentario']);

    $response = $this->actingAs($user)
        ->getJson("/tasks/{$task->id}");

    $response->assertOk()
        ->assertJsonPath('id', $task->id)
        ->assertJsonPath('comments.0.body', 'Primer comentario');
});

test('comments can be stored on tasks via the global route and return the task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'assignee')->create();

    $response = $this->actingAs($user)
        ->postJson("/tasks/{$task->id}/comments", ['body' => 'Comentario global']);

    $response->assertOk()->assertJsonPath('comments.0.body', 'Comentario global');
    $this->assertDatabaseHas('comments', ['task_id' => $task->id, 'body' => 'Comentario global']);
});

test('foreign users cannot access tasks outside their scope via global routes', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($stranger)->get("/tasks/{$task->id}")->assertNotFound();
    $this->actingAs($stranger)->getJson("/tasks/{$task->id}")->assertNotFound();
});
