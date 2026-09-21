<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

test('owners can archive an active project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Archived->value,
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Archived->value]);
});

test('owners can reactivate an archived project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->archived()->create();

    $response = $this->actingAs($user)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Active->value,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Active->value]);
});

test('owners can complete an active project and reactivate it', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Completed->value,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Completed->value]);

    $this->actingAs($user)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Active->value,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Active->value]);
});

test('illegal transitions are rejected and the status is unchanged', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->archived()->create();

    $response = $this->actingAs($user)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Completed->value,
    ]);

    $response->assertSessionHasErrors('status');
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Archived->value]);
});

test('non-owners can not change the project status', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();

    $this->actingAs($collaborator)->put("/projects/{$project->id}/status", [
        'status' => ProjectStatus::Archived->value,
    ])->assertNotFound();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => ProjectStatus::Active->value]);
});

test('archived projects are read-only until reactivated', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->archived()->create(['title' => 'Original']);

    $response = $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => 'Intento de edición',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Original']);
});

test('completed projects are read-only until reactivated', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->completed()->create(['title' => 'Original']);

    $response = $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => 'Intento de edición',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Original']);
});
