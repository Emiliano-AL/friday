<?php

use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('dashboard payload exposes the ids the navigation links need', function () {
    $user = User::factory()->create();

    Carbon::setTestNow('2026-10-05 09:00:00');

    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Pasarela de Pagos']);
    $secondProject = Project::factory()->for($user, 'owner')->create();

    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-07',
    ]);

    Task::factory()->count(3)->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($user);

    $task = Task::factory()->for($project)->create([
        'title' => 'SDK Webhook handler',
        'status' => TaskStatus::InProgress,
    ]);
    $task->update(['status' => TaskStatus::Done]);
    Comment::factory()->for($task)->for($user, 'author')->create();

    $dashboard = AssertableInertia::fromTestResponse(
        $this->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['sprintPanel']['id'])->toBe($sprint->id)
        ->and($dashboard['sprintPanel']['project']['id'])->toBe($project->id)
        ->and($dashboard['myTasks']['total'])->toBe(3)
        ->and($dashboard['projects']['items'])->toHaveCount(2)
        ->and(collect($dashboard['projects']['items'])->pluck('id')->all())
        ->toBe([$project->id, $secondProject->id]);

    $activityTasks = collect($dashboard['activity']['items'])->pluck('task')->filter()->all();

    expect($activityTasks)->not->toBeEmpty()
        ->and(collect($activityTasks)->pluck('id')->all())->toContain($task->id);

    Carbon::setTestNow();
});

test('dashboard navigation targets respond successfully for the acting user', function () {
    $user = User::factory()->create();

    Carbon::setTestNow('2026-10-05 09:00:00');

    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-07',
    ]);
    $task = Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($user);

    $targets = [
        route('projects.sprints.show', [$project->id, $sprint->id]),
        route('tasks.index'),
        route('projects.index'),
        route('tasks.show', $task->id),
    ];

    foreach ($targets as $target) {
        $this->get($target)->assertOk();
    }

    Carbon::setTestNow();
});
