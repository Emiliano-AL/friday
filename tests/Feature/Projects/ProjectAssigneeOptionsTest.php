<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('tasks page assignee options include the project owner alongside pivot members', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $collaboratorProject = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached([$user, $member], [], 'members')
        ->create();

    $ownedProject = Project::factory()->for($user, 'owner')->create();

    Task::factory()->for($collaboratorProject)->create();
    Task::factory()->for($ownedProject)->create();

    $this->actingAs($user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->has('projects', 2)
            ->where('projects', fn ($projects) => collect($projects)->contains(
                fn ($project) => $project['id'] === $collaboratorProject->id
                    && collect($project['members'])->where('id', $owner->id)->count() === 1
                    && collect($project['members'])->pluck('id')->sort()->values()->all()
                        === collect([$owner->id, $user->id, $member->id])->sort()->values()->all()
                    && collect($project['members'])->firstWhere('id', $owner->id)['name'] === $owner->name
                    && collect($project['members'])->firstWhere('id', $owner->id)['avatar'] === $owner->avatar
            ))
            ->where('projects', fn ($projects) => collect($projects)->contains(
                fn ($project) => $project['id'] === $ownedProject->id
                    && collect($project['members'])->pluck('id')->all() === [$user->id]
            ))
        );
});

test('assigning a project task to a non-collaborator is rejected on both store endpoints', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea con intruso',
        'assignee_id' => $outsider->id,
    ])->assertSessionHasErrors(['assignee_id' => 'El responsable debe ser miembro del proyecto.']);

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Tarea global con intruso',
        'project_id' => $project->id,
        'assignee_id' => $outsider->id,
    ])->assertSessionHasErrors(['assignee_id' => 'El responsable debe ser miembro del proyecto.']);

    expect(Task::query()->count())->toBe(0);
});

test('assigning a project task to its owner succeeds', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea del propietario',
        'assignee_id' => $owner->id,
    ])->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Tarea del propietario',
        'assignee_id' => $owner->id,
    ]);
});

test('removing a collaborator unassigns their tasks in the project only', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($member, [], 'members')
        ->create();

    $openTask = Task::factory()->for($project)->for($member, 'assignee')->create();
    $standaloneTask = Task::factory()->standalone()->for($member, 'assignee')->create();

    $this->actingAs($owner)
        ->delete("/projects/{$project->id}/members/{$member->id}")
        ->assertRedirect(route('projects.show', $project));

    expect($openTask->fresh()->assignee_id)->toBeNull()
        ->and($standaloneTask->fresh()->assignee_id)->toBe($member->id);
});
