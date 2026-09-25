<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('collaborators can create, update and comment tasks but never delete them', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();
    $task = Task::factory()->for($project)->create(['title' => 'Original']);

    $this->actingAs($collaborator)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea del colaborador',
    ])->assertSessionHasNoErrors();

    $this->actingAs($collaborator)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiada por colaborador',
        'type' => 'other',
        'priority' => 'medium',
        'status' => 'in_progress',
    ])->assertSessionHasNoErrors();

    $this->actingAs($collaborator)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => 'Comentario del colaborador']
    )->assertSessionHasNoErrors();

    $this->actingAs($collaborator)->delete("/projects/{$project->id}/tasks/{$task->id}")->assertNotFound();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Cambiada por colaborador']);
    $this->assertDatabaseHas('comments', ['task_id' => $task->id, 'user_id' => $collaborator->id]);
    expect(Task::query()->count())->toBe(2);
});

test('non members can not access the project page or its task operations', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($outsider)->get("/projects/{$project->id}")->assertNotFound();

    $this->actingAs($outsider)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea intrusa',
    ])->assertNotFound();

    $this->actingAs($outsider)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiada',
        'type' => 'other',
        'priority' => 'medium',
        'status' => 'todo',
    ])->assertNotFound();

    $this->actingAs($outsider)->delete("/projects/{$project->id}/tasks/{$task->id}")->assertNotFound();

    $this->actingAs($outsider)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => 'Comentario intruso']
    )->assertNotFound();

    expect(Task::query()->count())->toBe(1);
    expect(Comment::query()->count())->toBe(0);
});

test('member writes are blocked with a clear message when the project is not active', function (string $state) {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->$state()->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Original',
        'status' => 'todo',
    ]);

    $blockedMessage = ['project' => 'El proyecto no admite cambios en su estado actual.'];

    $this->actingAs($owner)->post("/projects/{$project->id}/tasks", [
        'title' => 'Nueva',
    ])->assertSessionHasErrors($blockedMessage);
    expect(Task::query()->count())->toBe(1);

    $this->actingAs($owner)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiada',
        'type' => 'other',
        'priority' => 'medium',
        'status' => 'done',
    ])->assertSessionHasErrors($blockedMessage);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Original', 'status' => 'todo']);

    $this->actingAs($owner)->delete("/projects/{$project->id}/tasks/{$task->id}")
        ->assertSessionHasErrors($blockedMessage);
    $this->assertDatabaseHas('tasks', ['id' => $task->id]);

    $this->actingAs($owner)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => 'Comentario bloqueado']
    )->assertSessionHasErrors($blockedMessage);
    expect(Comment::query()->count())->toBe(0);
})->with(['archived', 'completed']);

test('tasks and comments remain visible for members when the project is not active', function (string $state, bool $asCollaborator) {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->$state()->create();
    if ($asCollaborator) {
        $project->members()->attach($collaborator);
    }
    $task = Task::factory()->for($project)->create();
    Comment::factory()->for($task)->for($owner, 'author')->create();

    $viewer = $asCollaborator ? $collaborator : $owner;

    $this->actingAs($viewer)
        ->get("/projects/{$project->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.tasks', 1)
            ->has('project.tasks.0.comments', 1)
        );
})->with([
    'archived as owner' => ['archived', false],
    'completed as owner' => ['completed', false],
    'archived as collaborator' => ['archived', true],
    'completed as collaborator' => ['completed', true],
]);
