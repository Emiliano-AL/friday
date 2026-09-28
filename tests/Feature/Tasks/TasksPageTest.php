<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

test('the tasks page exposes the BoardTask shape with standalone support', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Carbon::setTestNow('2026-09-28 10:00:00');
    $task = Task::factory()->for($project)->create();

    Carbon::setTestNow('2026-09-28 10:05:00');
    $standalone = Task::factory()->standalone()->for($user, 'assignee')->create();
    $standalone->comments()->create(['user_id' => $user->id, 'body' => 'Hola']);
    Carbon::setTestNow();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 2)
            ->where('tasks.0.project', null)
            ->where('tasks.0.commentsCount', 1)
            ->where('tasks.1.project.id', $project->id)
            ->where('tasks.1.project.title', $project->title)
            ->where('tasks.1.commentsCount', 0)
            ->where('tasks.1.canUpdate', true)
            ->where('tasks.1.canDelete', true)
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
        );
});

test('the tasks page scope includes assigned and member tasks and excludes foreign ones', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $foreign = User::factory()->create();

    $memberProject = Project::factory()->for($owner, 'owner')->hasAttached($user, [], 'members')->create();

    Carbon::setTestNow('2026-09-28 10:00:00');
    $memberTask = Task::factory()->for($memberProject)->create();

    Carbon::setTestNow('2026-09-28 10:05:00');
    $assignedTask = Task::factory()->standalone()->for($user, 'assignee')->create();
    Carbon::setTestNow();

    $foreignProject = Project::factory()->for($foreign, 'owner')->create();
    $foreignTask = Task::factory()->for($foreignProject)->create();
    $foreignStandalone = Task::factory()->standalone()->for($foreign, 'assignee')->create();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->has('tasks', 2)
            ->where('tasks.0.id', $assignedTask->id)
            ->where('tasks.1.id', $memberTask->id)
            ->where('projects.0.id', $memberProject->id)
        );
});
