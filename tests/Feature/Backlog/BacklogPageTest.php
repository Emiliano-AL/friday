<?php

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

test('the backlog page requires authentication', function () {
    $this->get(route('backlog.index'))->assertRedirect('/login');
});

test('the backlog page lists only visible backlog tasks with the contract payload', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $owner = User::factory()->create();

    $memberProject = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    Carbon::setTestNow('2026-10-08 09:00:00');
    $doneTask = Task::factory()->for($memberProject)->create(['status' => 'done']);

    Carbon::setTestNow('2026-10-08 09:05:00');
    $sprint = $memberProject->sprints()->create([
        'name' => 'Sprint 9',
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-17',
    ]);
    $sprintTask = Task::factory()->for($memberProject)->create([
        'status' => 'todo',
        'sprint_id' => $sprint->id,
    ]);

    Carbon::setTestNow('2026-10-08 09:10:00');
    $memberTask = Task::factory()->for($memberProject)->create([
        'status' => 'backlog',
        'priority' => 'high',
    ]);

    Carbon::setTestNow('2026-10-08 09:15:00');
    $ownTask = Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'priority' => 'urgent',
        'points' => 5,
    ]);
    Carbon::setTestNow();

    $foreignProject = Project::factory()->for($other, 'owner')->create();
    Task::factory()->for($foreignProject)->create(['status' => 'backlog']);
    Task::factory()->standalone()->for($other, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('tasks', 2)
            ->where('tasks.0.id', $ownTask->id)
            ->where('tasks.0.code', 'FRN-1')
            ->where('tasks.0.title', $ownTask->title)
            ->where('tasks.0.priority', 'urgent')
            ->where('tasks.0.status', 'backlog')
            ->where('tasks.0.points', 5)
            ->where('tasks.0.createdAt', $ownTask->created_at->toIso8601String())
            ->where('tasks.0.project', null)
            ->where('tasks.0.sprint', null)
            ->where('tasks.0.assignee', null)
            ->where('tasks.0.authorId', $user->id)
            ->where('tasks.0.canUpdate', true)
            ->where('tasks.0.canDelete', true)
            ->where('tasks.1.id', $memberTask->id)
            ->where('tasks.1.priority', 'high')
            ->where('tasks.1.status', 'backlog')
            ->where('tasks.1.project.id', $memberProject->id)
            ->where('tasks.1.project.title', $memberProject->title)
            ->where('tasks.1.sprint', null)
            ->where('tasks.1.authorId', $memberTask->author_id)
            ->where('tasks.1.canUpdate', true)
            ->where('tasks.1.canDelete', false)
            ->has('projects', 1)
            ->where('projects.0.id', $memberProject->id)
        );

    expect($doneTask->status->value)->toBe('done')
        ->and($sprintTask->sprint_id)->not->toBeNull();
});

test('the backlog page exposes a summary computed over the visible tasks', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $memberProject = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'priority' => 'urgent',
        'points' => 3,
    ]);
    Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'priority' => 'medium',
        'points' => null,
    ]);

    // A project task without a sprint is listed under the "Con proyecto" concept.
    Task::factory()->for($memberProject)->create([
        'status' => 'backlog',
        'priority' => 'high',
        'points' => 5,
    ]);

    $sprint = $memberProject->sprints()->create([
        'name' => 'Sprint 9',
        'start_date' => '2026-10-06',
        'end_date' => '2026-10-17',
    ]);

    // Excluded from the backlog and therefore from the summary.
    Task::factory()->for($memberProject)->create(['status' => 'done', 'points' => 8]);
    Task::factory()->for($memberProject)->create(['status' => 'todo', 'sprint_id' => $sprint->id, 'points' => 2]);
    Task::factory()->standalone()->for($owner, 'author')->create(['status' => 'backlog', 'points' => 13]);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('tasks', 3)
            ->has('summary')
            ->where('summary.total', 3)
            ->where('summary.totalPoints', 8)
            ->where('summary.withoutProject', 2)
            ->where('summary.withProject', 1)
        );
});

test('the backlog page summary always satisfies the contract invariants', function () {
    $user = User::factory()->create();

    Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'points' => null,
    ]);
    Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'points' => 2,
    ]);
    Task::factory()->standalone()->for($user, 'author')->create([
        'status' => 'backlog',
        'points' => 0,
    ]);

    $page = $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk();

    $summary = $page->viewData('page')['props']['summary'];

    expect($summary['total'])->toBe(3)
        ->and($summary['withoutProject'] + $summary['withProject'])->toBe($summary['total'])
        ->and($summary['totalPoints'])->toBe(2)
        ->and($summary['withoutProject'])->toBe(3)
        ->and($summary['withProject'])->toBe(0);
});

test('the backlog page hides standalone tasks authored by other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $ownTask = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);
    $foreignTask = Task::factory()->standalone()->for($other, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $ownTask->id)
            ->where('summary.total', 1)
            ->where('summary.withoutProject', 1)
            ->where('summary.withProject', 0)
        );

    expect($foreignTask->author_id)->toBe($other->id);
});

test('the backlog page keeps tasks authored by the user even when assigned to someone else', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->standalone()
        ->for($user, 'author')
        ->for($assignee, 'assignee')
        ->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.assignee.id', $assignee->id)
            ->where('tasks.0.canUpdate', true)
            ->where('tasks.0.canDelete', true)
        );
});

test('the backlog page exposes the nearest active or planned sprint across the user projects', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $projectA = Project::factory()->for($user, 'owner')->create();

    // Project the user belongs to (not owner): its sprints are also eligible.
    $projectB = Project::factory()
        ->for($other, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    $projectA->sprints()->create([
        'name' => 'Sprint A',
        'status' => SprintStatus::Planned,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-31',
    ]);
    $activeB = $projectB->sprints()->create([
        'name' => 'Sprint B',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-16',
    ]);

    // Sprints of projects the user does not belong to are ignored.
    $foreignProject = Project::factory()->for($other, 'owner')->create();
    $foreignProject->sprints()->create([
        'name' => 'Sprint X',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-10',
    ]);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->where('nextSprint.id', $activeB->id)
            ->where('nextSprint.name', 'Sprint B')
            ->where('nextSprint.projectId', $projectB->id)
            ->where('nextSprint.projectTitle', $projectB->title)
            ->where('nextSprint.status', 'active')
            ->where('nextSprint.startsAt', '2026-10-05')
            ->where('nextSprint.endsAt', '2026-10-16')
        );
});

test('the backlog page exposes no next sprint when only completed sprints exist', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $project->sprints()->create([
        'name' => 'Sprint pasado',
        'status' => SprintStatus::Completed,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-12',
    ]);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->where('nextSprint', null)
        );
});

test('the backlog page only offers active or planned sprints as delivery options', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $planned = $project->sprints()->create([
        'name' => 'Sprint planificado',
        'status' => SprintStatus::Planned,
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-31',
    ]);
    $active = $project->sprints()->create([
        'name' => 'Sprint activo',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-16',
    ]);
    $project->sprints()->create([
        'name' => 'Sprint cerrado',
        'status' => SprintStatus::Completed,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-12',
    ]);

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('projects', 1)
            ->where('projects.0.sprints', fn ($sprints) => count($sprints) === 2
                && collect($sprints)->pluck('id')->sort()->values()->all()
                === collect([$planned->id, $active->id])->sort()->values()->all())
        );
});

test('the new task navigation destination exposes the quick capture composer', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->get(route('backlog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backlog/Index')
            ->has('tasks')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
        );
});
