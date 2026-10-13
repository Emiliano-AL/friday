<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('project payload includes open tasks count for owner and each member', function () {
    $owner = User::factory()->create();
    $memberA = User::factory()->create();
    $memberB = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached([$memberA, $memberB], [], 'members')
        ->create();

    $otherProject = Project::factory()->for($owner, 'owner')->create();

    Task::factory()->for($project)->for($owner, 'assignee')->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($project)->for($owner, 'assignee')->create(['status' => TaskStatus::InProgress]);
    Task::factory()->for($project)->for($owner, 'assignee')->create(['status' => TaskStatus::Done]);

    Task::factory()->for($project)->for($memberA, 'assignee')->create(['status' => TaskStatus::Todo]);
    Task::factory()->standalone()->for($memberA, 'assignee')->create(['status' => TaskStatus::Todo]);
    Task::factory()->for($otherProject)->for($memberA, 'assignee')->create(['status' => TaskStatus::Todo]);

    Task::factory()->for($project)->create(['status' => TaskStatus::Todo, 'assignee_id' => null]);

    $this->actingAs($owner)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.owner.id', $owner->id)
            ->where('project.owner.openTasksCount', 2)
            ->where('project.members', fn ($members) => collect($members)->firstWhere('id', $memberA->id)['openTasksCount'] === 1
                && collect($members)->firstWhere('id', $memberB->id)['openTasksCount'] === 0)
        );
});

test('owner never appears inside the members list', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($member, [], 'members')
        ->create();

    $this->actingAs($owner)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.owner.id', $owner->id)
            ->has('project.members', 1)
            ->where('project.members', fn ($members) => collect($members)->doesntContain('id', $owner->id))
        );
});

test('users who are not owner or collaborator still get a 404', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($member, [], 'members')
        ->create();

    $this->actingAs($outsider)->get("/projects/{$project->id}")->assertNotFound();

    $this->actingAs($member)->get("/projects/{$project->id}")->assertOk();
});

test('unregistered member emails get a clear spanish invitation message', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post("/projects/{$project->id}/members", ['email' => 'nadie@test.dev'])
        ->assertSessionHas('success', 'Invitación enviada a nadie@test.dev.');
});
