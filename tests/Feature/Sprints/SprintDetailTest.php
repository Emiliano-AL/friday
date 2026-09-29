<?php

use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-09-25 12:00:00'));

afterEach(fn () => Carbon::setTestNow());

function sprintWithTasks(User $owner): array
{
    $project = Project::factory()->for($owner, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'goal' => 'Reducir fricción de checkout',
        'start_date' => '2026-09-22',
        'end_date' => '2026-09-29',
        'status' => SprintStatus::Active,
    ]);

    Task::factory()->for($project)->count(2)->create(['sprint_id' => $sprint->id, 'status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['sprint_id' => $sprint->id, 'status' => TaskStatus::Todo]);
    Task::factory()->for($project)->create(['sprint_id' => $sprint->id, 'status' => TaskStatus::InReview]);

    return [$project, $sprint];
}

test('members see the sprint detail with real metrics and tasks', function () {
    $owner = User::factory()->create();
    [$project, $sprint] = sprintWithTasks($owner);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sprints/Show')
            ->where('sprint.id', $sprint->id)
            ->where('sprint.name', 'Sprint 14')
            ->where('sprint.goal', 'Reducir fricción de checkout')
            ->where('sprint.status', 'active')
            ->where('sprint.statusLabel', 'Activo')
            ->where('sprint.startDate', '2026-09-22')
            ->where('sprint.endDate', '2026-09-29')
            ->where('sprint.iteration.current', 1)
            ->where('sprint.iteration.total', 1)
            ->where('sprint.daysRemaining', 2)
            ->where('sprint.percentElapsed', 43)
            ->where('sprint.canWrite', true)
            ->where('sprint.project.id', $project->id)
            ->where('metrics.total', 4)
            ->where('metrics.done', 2)
            ->where('metrics.coverage', 50)
            ->has('metrics.breakdown', 3)
            ->where('metrics.breakdown.0.status', 'todo')
            ->where('metrics.breakdown.0.count', 1)
            ->where('metrics.breakdown.2.status', 'done')
            ->where('metrics.breakdown.2.count', 2)
            ->has('tasks', 4)
            ->where('tasks.0.sprint.id', $sprint->id)
            ->has('taskContext.sprints', 1)
            ->where('canWrite', true)
        );
});

test('metrics are zero and comprehensible when the sprint has no tasks', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'start_date' => '2026-09-22',
        'end_date' => '2026-09-29',
        'status' => SprintStatus::Active,
    ]);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertInertia(fn ($page) => $page
            ->where('metrics.total', 0)
            ->where('metrics.done', 0)
            ->where('metrics.coverage', 0)
            ->has('metrics.breakdown', 0)
            ->has('tasks', 0)
        );
});

test('elapsed percent is clamped and single day sprints behave', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $future = Sprint::factory()->for($project)->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'status' => SprintStatus::Planned,
    ]);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}/sprints/{$future->id}")
        ->assertInertia(fn ($page) => $page
            ->where('sprint.percentElapsed', 0)
            ->where('sprint.daysRemaining', 8)
        );

    $past = Sprint::factory()->for($project)->create([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-10',
        'status' => SprintStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}/sprints/{$past->id}")
        ->assertInertia(fn ($page) => $page
            ->where('sprint.percentElapsed', 100)
            ->where('sprint.daysRemaining', 0)
            ->where('sprint.canWrite', false)
        );
});

test('iteration number orders sprints by start date across the project', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    Sprint::factory()->for($project)->create(['start_date' => '2026-09-01', 'end_date' => '2026-09-07']);
    $second = Sprint::factory()->for($project)->create(['start_date' => '2026-09-08', 'end_date' => '2026-09-14']);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}/sprints/{$second->id}")
        ->assertInertia(fn ($page) => $page
            ->where('sprint.iteration.current', 2)
            ->where('sprint.iteration.total', 2)
        );
});

test('non members and foreign sprints get an indistinguishable 404', function () {
    $owner = User::factory()->create();
    [$project, $sprint] = sprintWithTasks($owner);

    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertNotFound();

    $otherProject = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get("/projects/{$otherProject->id}/sprints/{$sprint->id}")
        ->assertNotFound();
});

test('collaborators can view the detail but cannot write', function () {
    $owner = User::factory()->create();
    [$project, $sprint] = sprintWithTasks($owner);

    $member = User::factory()->create();
    $project->members()->attach($member);

    $this->actingAs($member)
        ->get("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sprint.canWrite', false)
            ->where('canWrite', false)
            ->has('tasks', 4)
        );
});
