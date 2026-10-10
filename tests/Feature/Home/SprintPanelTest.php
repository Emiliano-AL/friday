<?php

use App\Enums\ActivityType;
use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('sprint panel shows the most recent active sprint across the user projects', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Carbon::setTestNow('2026-10-05 09:00:00');

    $project = Project::factory()->for($user, 'owner')->create(['title' => 'Pasarela de Pagos']);
    $olderProject = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($olderProject)->create([
        'name' => 'Sprint 13',
        'status' => SprintStatus::Active,
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-30',
    ]);

    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'goal' => 'Integrar pagos',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-07',
    ]);

    $foreignProject = Project::factory()->for($other, 'owner')->create();
    Sprint::factory()->for($foreignProject)->create([
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-08',
    ]);

    Task::factory()->count(4)->for($project)->for($sprint)->create(['status' => TaskStatus::InProgress]);
    Task::factory()->count(2)->for($project)->for($sprint)->create(['status' => TaskStatus::Done]);

    Activity::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::TaskCompleted,
        'created_at' => '2026-10-05 10:00:00',
    ]);
    Activity::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::TaskCompleted,
        'created_at' => '2026-10-06 10:00:00',
    ]);
    Activity::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::TaskCompleted,
        'created_at' => '2026-10-06 15:00:00',
    ]);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['sprintPanel'])->not->toBeNull()
        ->and($dashboard['sprintPanel']['id'])->toBe($sprint->id)
        ->and($dashboard['sprintPanel']['name'])->toBe('Sprint 14')
        ->and($dashboard['sprintPanel']['goal'])->toBe('Integrar pagos')
        ->and($dashboard['sprintPanel']['project'])->toBe([
            'id' => $project->id,
            'title' => 'Pasarela de Pagos',
        ])
        ->and($dashboard['sprintPanel']['total'])->toBe(6)
        ->and($dashboard['sprintPanel']['done'])->toBe(2)
        ->and($dashboard['sprintPanel']['coverage'])->toBe(33)
        ->and($dashboard['sprintPanel']['daysRemaining'])->toBe(2);

    expect($dashboard['sprintPanel']['burndown'])->toMatchArray([
        'startDate' => '2026-10-05',
        'endDate' => '2026-10-07',
        'total' => 6,
    ])->and($dashboard['sprintPanel']['burndown']['points'])->toBe([
        ['date' => '2026-10-05', 'ideal' => 2, 'done' => 1],
        ['date' => '2026-10-06', 'ideal' => 4, 'done' => 3],
        ['date' => '2026-10-07', 'ideal' => 6, 'done' => 3],
    ]);

    expect($dashboard['summary']['sprint'])->toBe([
        'id' => $sprint->id,
        'name' => 'Sprint 14',
        'coverage' => 33,
        'daysRemaining' => 2,
        'project' => ['id' => $project->id, 'title' => 'Pasarela de Pagos'],
    ])->and($dashboard['context']['sprint'])->toBe([
        'name' => 'Sprint 14',
        'coverage' => 33,
    ]);

    Carbon::setTestNow();
});

test('sprint sections are null when the user has no active sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create([
        'status' => SprintStatus::Planned,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-07',
    ]);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->actingAs($user)->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard)->not->toBeNull()
        ->and($dashboard['sprintPanel'])->toBeNull()
        ->and($dashboard['summary']['sprint'])->toBeNull()
        ->and($dashboard['context']['sprint'])->toBeNull();
});
