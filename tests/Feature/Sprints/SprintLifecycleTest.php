<?php

use App\Enums\ProjectStatus;
use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('owner starts a planned sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create(['status' => SprintStatus::Planned]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/start")
        ->assertRedirect()
        ->assertSessionHas('success', 'Sprint activado.');

    expect($sprint->refresh()->status)->toBe(SprintStatus::Active);
});

test('owner completes an active sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create(['status' => SprintStatus::Active]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/complete")
        ->assertRedirect()
        ->assertSessionHas('success', 'Sprint completado.');

    expect($sprint->refresh()->status)->toBe(SprintStatus::Completed);
});

test('forward-only transitions are rejected with a clear message', function (SprintStatus $from, string $action, SprintStatus $expected) {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create(['status' => $from]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/{$action}")
        ->assertSessionHasErrors('sprint');

    expect($sprint->refresh()->status)->toBe($expected);
})->with([
    'complete on planned' => [SprintStatus::Planned, 'complete', SprintStatus::Planned],
    'start on active' => [SprintStatus::Active, 'start', SprintStatus::Active],
    'complete on completed' => [SprintStatus::Completed, 'complete', SprintStatus::Completed],
    'start on completed (reactivation)' => [SprintStatus::Completed, 'start', SprintStatus::Completed],
]);

test('collaborators cannot transition sprints', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create(['status' => SprintStatus::Planned]);

    $member = User::factory()->create();
    $project->members()->attach($member);

    $this->actingAs($member)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/start")
        ->assertNotFound();

    expect($sprint->refresh()->status)->toBe(SprintStatus::Planned);
});

test('transitions are blocked when the project is not active', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['status' => ProjectStatus::Archived]);
    $sprint = Sprint::factory()->for($project)->create(['status' => SprintStatus::Planned]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/start")
        ->assertSessionHasErrors('project');

    expect($sprint->refresh()->status)->toBe(SprintStatus::Planned);
});

test('sprints from another project are not reachable', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($otherProject)->create(['status' => SprintStatus::Planned]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/start")
        ->assertNotFound();
});
