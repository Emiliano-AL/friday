<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('project members can add a comment to a task and it appears chronologically', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $response = $this->actingAs($user)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => 'Primera nota de avance']
    );

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('comments', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'body' => 'Primera nota de avance',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks', 1)
            ->has('project.tasks.0.comments', 1)
            ->where('project.tasks.0.comments.0.body', 'Primera nota de avance')
            ->where('project.tasks.0.comments.0.author.id', $user->id)
            ->where('project.tasks.0.comments.0.author.name', $user->name)
        );
});

test('comments are listed in chronological order with their authors', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();
    Comment::factory()->for($task)->for($user, 'author')->create([
        'body' => 'Comentario viejo',
        'created_at' => '2026-09-20 10:00:00',
    ]);
    Comment::factory()->for($task)->for($user, 'author')->create([
        'body' => 'Comentario nuevo',
        'created_at' => '2026-09-21 10:00:00',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks.0.comments', 2)
            ->where('project.tasks.0.comments.0.body', 'Comentario viejo')
            ->where('project.tasks.0.comments.1.body', 'Comentario nuevo')
        );
});

test('comments can not be created with an empty body', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => '']
    )->assertSessionHasErrors('body');

    $this->actingAs($user)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => '   ']
    )->assertSessionHasErrors('body');

    expect(Comment::query()->count())->toBe(0);
});

test('comments can not be added to a task from another project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $foreignTask = Task::factory()->for($otherProject)->create();

    $this->actingAs($user)->post(
        "/projects/{$project->id}/tasks/{$foreignTask->id}/comments",
        ['body' => 'Comentario intruso']
    )->assertNotFound();

    expect(Comment::query()->count())->toBe(0);
});

test('non members can not comment on project tasks', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($outsider)->post(
        "/projects/{$project->id}/tasks/{$task->id}/comments",
        ['body' => 'Comentario intruso']
    )->assertNotFound();

    expect(Comment::query()->count())->toBe(0);
});
