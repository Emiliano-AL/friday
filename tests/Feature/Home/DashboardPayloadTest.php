<?php

use App\Enums\SprintStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('dashboard reflects the user real task counts and priority ordering', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Pasarela de Pagos']);

    Carbon::setTestNow('2026-10-03 12:00:00');

    $urgentOld = Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Urgente vieja',
        'priority' => TaskPriority::Urgent,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-01 09:00:00',
    ]);
    $urgentNew = Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Urgente nueva',
        'priority' => TaskPriority::Urgent,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-02 09:00:00',
    ]);
    $high = Task::factory()->for($project)->for($user, 'assignee')->create([
        'title' => 'Alta',
        'priority' => TaskPriority::High,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-03 09:00:00',
    ]);
    $mediumNew = Task::factory()->for($project)->for($user, 'assignee')->create([
        'priority' => TaskPriority::Medium,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-03 09:00:00',
    ]);
    $mediumMid = Task::factory()->for($project)->for($user, 'assignee')->create([
        'priority' => TaskPriority::Medium,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-02 09:00:00',
    ]);
    $mediumOld = Task::factory()->for($project)->for($user, 'assignee')->create([
        'priority' => TaskPriority::Medium,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-01 09:00:00',
    ]);
    $lowNew = Task::factory()->for($project)->for($user, 'assignee')->create([
        'priority' => TaskPriority::Low,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-03 09:00:00',
    ]);
    $lowNewest = Task::factory()->for($project)->for($user, 'assignee')->create([
        'priority' => TaskPriority::Low,
        'status' => TaskStatus::InProgress,
        'updated_at' => '2026-10-04 09:00:00',
    ]);

    Comment::factory()->count(2)->for($urgentNew)->for($user, 'author')->create();

    Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::Done,
        'completed_at' => '2026-10-03 10:00:00',
    ]);
    Task::factory()->for($project)->for($other, 'assignee')->create([
        'status' => TaskStatus::InProgress,
    ]);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['context']['priorityTasks'])->toBe(3)
        ->and($dashboard['summary']['tasks'])->toBe(['active' => 8, 'urgent' => 3])
        ->and($dashboard['myTasks']['total'])->toBe(8);

    $items = $dashboard['myTasks']['items'];

    expect($items)->toHaveCount(6)
        ->and(collect($items)->pluck('id')->all())->toBe([
            $urgentNew->id,
            $urgentOld->id,
            $high->id,
            $mediumNew->id,
            $mediumMid->id,
            $mediumOld->id,
        ])
        ->and(collect($items)->pluck('id')->all())->not->toContain($lowNew->id, $lowNewest->id);

    expect($items[0])->toHaveKeys([
        'id', 'title', 'status', 'statusLabel', 'priority', 'priorityLabel', 'project', 'commentsCount',
    ])->and($items[0])->toMatchArray([
        'id' => $urgentNew->id,
        'title' => 'Urgente nueva',
        'status' => 'in_progress',
        'statusLabel' => 'En progreso',
        'priority' => 'urgent',
        'priorityLabel' => 'Urgente',
        'project' => ['id' => $project->id, 'title' => 'Pasarela de Pagos'],
        'commentsCount' => 2,
    ]);

    Carbon::setTestNow();
});

test('projects panel lists only the user active projects with progress and note', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Carbon::setTestNow('2026-10-03 12:00:00');

    $withSprint = Project::factory()->for($user, 'owner')->create([
        'title' => 'Con Sprint',
        'updated_at' => '2026-10-03 09:00:00',
    ]);
    $activeA = Project::factory()->for($user, 'owner')->create(['updated_at' => '2026-10-02 09:00:00']);
    $activeB = Project::factory()->for($user, 'owner')->create(['updated_at' => '2026-10-01 09:00:00']);
    $activeC = Project::factory()->for($user, 'owner')->create(['updated_at' => '2026-09-30 09:00:00']);
    $activeD = Project::factory()->for($user, 'owner')->create(['updated_at' => '2026-09-29 09:00:00']);
    $completed = Project::factory()->completed()->for($user, 'owner')->create([
        'updated_at' => '2026-10-04 09:00:00',
    ]);
    $foreign = Project::factory()->for($other, 'owner')->create(['updated_at' => '2026-10-05 09:00:00']);

    Task::factory()->count(3)->for($withSprint)->create(['status' => TaskStatus::InProgress]);
    Task::factory()->for($withSprint)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($activeA)->create(['status' => TaskStatus::InProgress]);
    Task::factory()->for($activeA)->create(['status' => TaskStatus::Done]);

    Sprint::factory()->for($withSprint)->create([
        'name' => 'Sprint 14',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-07',
    ]);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['summary']['projects']['active'])->toBe(5);

    $items = $dashboard['projects']['items'];

    expect($items)->toHaveCount(4)
        ->and(collect($items)->pluck('id')->all())->toBe([
            $withSprint->id,
            $activeA->id,
            $activeB->id,
            $activeC->id,
        ])
        ->and(collect($items)->pluck('id')->all())->not->toContain($activeD->id, $completed->id, $foreign->id);

    expect($items[0]['progress'])->toBe($withSprint->progressPercentage())
        ->and($items[0]['note'])->toBe('Sprint 14 • 3 días restantes')
        ->and($items[1]['progress'])->toBe($activeA->progressPercentage())
        ->and($items[1]['note'])->toBe('1 tareas abiertas')
        ->and($items[2]['progress'])->toBe(0)
        ->and($items[2]['note'])->toBe('0 tareas abiertas');

    Carbon::setTestNow();
});
