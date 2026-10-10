<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('weekly insight aggregates the user completions since the ISO week start', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user);

    Carbon::setTestNow('2026-09-25 10:00:00');

    $lastWeekTask = Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
        'created_at' => '2026-09-20 10:00:00',
    ]);
    $lastWeekTask->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-04 12:00:00');

    $taskA = Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
        'created_at' => '2026-10-01 12:00:00',
    ]);
    $taskA->update(['status' => TaskStatus::Done]);

    $taskB = Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
        'created_at' => '2026-10-02 12:00:00',
    ]);
    $taskB->update(['status' => TaskStatus::Done]);

    $foreignTask = Task::factory()->for($project)->for($other, 'assignee')->create([
        'status' => TaskStatus::InProgress,
    ]);
    $this->actingAs($other);
    $foreignTask->update(['status' => TaskStatus::Done]);
    $this->actingAs($user);

    $dashboard = AssertableInertia::fromTestResponse(
        $this->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard['weeklyInsight'])->toBe(['completed' => 2, 'avgCycleDays' => 2.5]);

    Carbon::setTestNow();
});

test('weekly insight is null when the user completed nothing this week', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user);

    Carbon::setTestNow('2026-09-25 10:00:00');

    $lastWeekTask = Task::factory()->for($project)->for($user, 'assignee')->create([
        'status' => TaskStatus::InProgress,
        'created_at' => '2026-09-20 10:00:00',
    ]);
    $lastWeekTask->update(['status' => TaskStatus::Done]);

    Carbon::setTestNow('2026-10-04 12:00:00');

    $dashboard = AssertableInertia::fromTestResponse(
        $this->get(route('home')),
    )->toArray()['props']['dashboard'];

    expect($dashboard)->not->toBeNull()
        ->and($dashboard['weeklyInsight'])->toBeNull();

    Carbon::setTestNow();
});
