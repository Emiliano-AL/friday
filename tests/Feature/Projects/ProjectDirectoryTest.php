<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-09-25 12:00:00'));

afterEach(fn () => Carbon::setTestNow());

test('directory payload exposes the full DirectoryProject shape', function () {
    $user = User::factory()->create();
    $member = User::factory()->create(['avatar' => 'https://example.com/a.png']);

    $project = Project::factory()->for($user, 'owner')->create([
        'title' => 'Pasarela de Pagos',
        'description' => 'Unificación de checkout',
    ]);
    $project->members()->attach($member);

    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Todo]);

    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Sprint 14',
        'start_date' => '2026-09-20',
        'end_date' => '2026-10-04',
    ]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->where('projects.0.id', $project->id)
            ->where('projects.0.title', 'Pasarela de Pagos')
            ->where('projects.0.description', 'Unificación de checkout')
            ->where('projects.0.status', 'active')
            ->where('projects.0.statusLabel', 'Activo')
            ->where('projects.0.progress', 50)
            ->where('projects.0.taskDoneCount', 2)
            ->where('projects.0.taskTotalCount', 4)
            ->where('projects.0.activeSprint.id', $sprint->id)
            ->where('projects.0.activeSprint.name', 'Sprint 14')
            ->where('projects.0.activeSprint.startDate', '2026-09-20')
            ->where('projects.0.activeSprint.endDate', '2026-10-04')
            ->where('projects.0.owner.name', $user->name)
            ->where('projects.0.owner.avatar', null)
            ->where('projects.0.members.0.id', $member->id)
            ->where('projects.0.members.0.avatar', 'https://example.com/a.png')
            ->where('projects.0.isOwner', true)
        );
});

test('active sprint is null when no sprint has started', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-14',
    ]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->where('projects.0.activeSprint', null)
        );
});

test('directory counts reflect the full universe of user projects', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Project::factory()->for($user, 'owner')->count(2)->create(['status' => ProjectStatus::Active]);
    Project::factory()->for($user, 'owner')->create(['status' => ProjectStatus::Completed]);
    Project::factory()->hasAttached($user, [], 'members')->create(['status' => ProjectStatus::Archived]);
    Project::factory()->for($other, 'owner')->create(['status' => ProjectStatus::Active]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->has('projects', 4)
            ->where('counts.active', 2)
            ->where('counts.completed', 1)
            ->where('counts.archived', 1)
            ->where('kpis.activeProjects', 2)
        );
});

test('member projects are flagged as not owned and never list foreign projects', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $foreign = User::factory()->create();

    $shared = Project::factory()->for($owner, 'owner')->hasAttached($member, [], 'members')->create();
    Project::factory()->for($foreign, 'owner')->create(['title' => 'Ajeno']);

    $this->actingAs($member)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $shared->id)
            ->where('projects.0.isOwner', false)
        );
});

test('directory lists projects ordered by most recently updated', function () {
    $user = User::factory()->create();

    $first = Project::factory()->for($user, 'owner')->create(['title' => 'Primero']);
    $second = Project::factory()->for($user, 'owner')->create(['title' => 'Segundo']);

    $first->touch();

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->where('projects.0.id', $first->id)
            ->where('projects.1.id', $second->id)
        );
});

test('completed sprints KPI counts only past sprints of the current quarter', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create(['end_date' => '2026-09-20']); // pasado, trimestre actual
    Sprint::factory()->for($project)->create(['end_date' => '2026-06-30']); // pasado, trimestre anterior
    Sprint::factory()->for($project)->create(['end_date' => '2026-10-02']); // futuro
    Sprint::factory()->for($project)->create(['end_date' => '2026-09-26']); // futuro (mañana)

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->where('kpis.completedSprintsThisQuarter', 1)
        );
});
