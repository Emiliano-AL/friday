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

test('activity feed shows the latest 8 team events of the user projects', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create();
    $outsider = User::factory()->create();

    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Pasarela de Pagos']);
    $memberProject = Project::factory()->for($outsider, 'owner')->create(['title' => 'App Móvil']);
    $memberProject->members()->attach($user->id);
    $foreignProject = Project::factory()->for($outsider, 'owner')->create(['title' => 'Proyecto Ajeno']);

    $this->actingAs($user);

    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'status' => SprintStatus::Planned,
    ]);

    Carbon::setTestNow('2026-10-01 09:00:00');
    $taskA = Task::factory()->for($project)->for($teammate, 'assignee')->create(['title' => 'Tarea A']);

    Carbon::setTestNow('2026-10-01 10:00:00');
    $taskA->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-01 11:00:00');
    Comment::factory()->for($taskA)->for($teammate, 'author')->create();

    Carbon::setTestNow('2026-10-02 09:00:00');
    $taskB = Task::factory()->for($project)->create(['title' => 'Tarea B']);

    Carbon::setTestNow('2026-10-02 10:00:00');
    $taskB->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-03 09:00:00');
    $taskC = Task::factory()->for($project)->create(['title' => 'Tarea C']);

    Carbon::setTestNow('2026-10-03 10:00:00');
    $taskC->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-03 11:00:00');
    $sprint->start();

    Carbon::setTestNow('2026-10-03 12:00:00');
    $sprint->complete();

    Carbon::setTestNow('2026-10-03 13:00:00');
    Task::factory()->for($memberProject)->create(['title' => 'Tarea del proyecto miembro']);

    Carbon::setTestNow('2026-10-03 14:00:00');
    Task::factory()->for($foreignProject)->create(['title' => 'Tarea externa']);

    Carbon::setTestNow('2026-10-03 15:00:00');
    Task::factory()->for($project)->create(['title' => 'Tarea D']);

    Carbon::setTestNow('2026-10-03 16:00:00');
    Task::factory()->for($project)->create(['title' => 'Tarea E']);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->get(route('home')),
    )->toArray()['props']['dashboard'];

    $items = $dashboard['activity']['items'];

    expect($items)->toHaveCount(8);

    // 10 events in the user projects (9 owned + 1 member), latest 8 kept,
    // ordered created_at desc: the two oldest (Tarea A created/completed) drop out.
    expect(collect($items)->pluck('createdAt')->all())
        ->toBe(collect($items)->pluck('createdAt')->sortDesc()->values()->all());

    $subjects = collect($items)
        ->map(fn (array $item) => $item['task']['title'] ?? $item['sprint']['name'] ?? null)
        ->all();

    expect($subjects)->not->toContain('Tarea A')
        ->and($subjects)->not->toContain('Tarea externa')
        ->and($subjects)->toContain('Tarea B', 'Tarea C', 'Tarea del proyecto miembro', 'Sprint 14');

    Carbon::setTestNow();
});

test('activity item shape exposes type label actor task and project refs', function () {
    $user = User::factory()->create(['name' => 'Elena Rostova']);
    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Pasarela de Pagos']);

    $this->actingAs($user);

    Carbon::setTestNow('2026-10-03 09:12:00');
    $task = Task::factory()->for($project)->create([
        'title' => 'SDK Webhook handler',
        'status' => TaskStatus::InProgress,
    ]);

    Carbon::setTestNow('2026-10-03 10:00:00');
    $task->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-03 11:00:00');
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'status' => SprintStatus::Planned,
    ]);
    $sprint->start();

    $dashboard = AssertableInertia::fromTestResponse(
        $this->get(route('home')),
    )->toArray()['props']['dashboard'];

    $items = $dashboard['activity']['items'];

    expect($items)->toHaveCount(3);

    expect($items[0])->toMatchArray([
        'type' => 'sprint_started',
        'typeLabel' => 'inició el sprint',
        'user' => ['id' => $user->id, 'name' => 'Elena Rostova', 'avatar' => null],
        'task' => null,
        'project' => ['id' => $project->id, 'title' => 'Pasarela de Pagos'],
        'sprint' => ['id' => $sprint->id, 'name' => 'Sprint 14'],
    ])->and($items[0]['createdAt'])->toBe('2026-10-03T11:00:00+00:00')
        ->and($items[1])->toMatchArray([
            'type' => 'task_completed',
            'typeLabel' => 'completó la tarea',
            'task' => ['id' => $task->id, 'title' => 'SDK Webhook handler'],
        ])
        ->and($items[2])->toMatchArray([
            'type' => 'task_created',
            'typeLabel' => 'creó la tarea',
            'task' => ['id' => $task->id, 'title' => 'SDK Webhook handler'],
            'sprint' => null,
        ]);

    Carbon::setTestNow();
});

test('activity feed is empty when the user projects have no events yet', function () {
    $user = User::factory()->create();
    Project::factory()->for($user, 'owner')->create();

    $dashboard = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['activity']['items'])->toBe([]);
});
