<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-09-25 12:00:00'));

afterEach(fn () => Carbon::setTestNow());

test('show payload exposes the active sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'start_date' => '2026-09-20',
        'end_date' => '2026-10-04',
    ]);
    Sprint::factory()->for($project)->create([
        'name' => 'Sprint 15',
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-20',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.activeSprint.id', $sprint->id)
            ->where('project.activeSprint.name', 'Sprint 14')
        );
});

test('show payload exposes real task counts', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Task::factory()->for($project)->count(3)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.progress', 60)
            ->where('project.taskDoneCount', 3)
            ->where('project.taskTotalCount', 5)
        );
});

test('show payload exposes avatars for owner members and assignees', function () {
    $user = User::factory()->create(['avatar' => 'https://example.com/owner.png']);
    $member = User::factory()->create(['avatar' => 'https://example.com/member.png']);
    $assignee = User::factory()->create(['avatar' => 'https://example.com/assignee.png']);

    $project = Project::factory()->for($user, 'owner')->hasAttached($member, [], 'members')->create();

    Task::factory()->for($project)->for($assignee, 'assignee')->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.owner.avatar', 'https://example.com/owner.png')
            ->where('project.members.0.avatar', 'https://example.com/member.png')
            ->where('project.tasks.0.assignee.avatar', 'https://example.com/assignee.png')
        );
});
