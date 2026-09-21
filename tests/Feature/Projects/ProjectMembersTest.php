<?php

use App\Models\Project;
use App\Models\User;

test('owners can add an existing user as collaborator by email', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => $collaborator->email,
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $collaborator->id,
    ]);

    $this->actingAs($collaborator)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
        );
});

test('duplicate collaborators are rejected', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => $collaborator->email,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('project_user', 1);
});

test('unregistered emails are rejected', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => 'nobody@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('project_user', 0);
});

test('the owner can not be added as collaborator', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post("/projects/{$project->id}/members", [
        'email' => $owner->email,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('project_user', 0);
});

test('owners can remove collaborators', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();

    $response = $this->actingAs($owner)->delete("/projects/{$project->id}/members/{$collaborator->id}");

    $response->assertRedirect(route('projects.show', $project, absolute: false));
    $this->assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $collaborator->id,
    ]);

    $this->actingAs($collaborator)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page->has('projects', 0));
});

test('owners can not remove themselves', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->delete("/projects/{$project->id}/members/{$owner->id}");

    $response->assertNotFound();
    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

test('non-owners can not manage collaborators', function () {
    $owner = User::factory()->create();
    $collaborator = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($collaborator, [], 'members')->create();

    $this->actingAs($collaborator)->post("/projects/{$project->id}/members", [
        'email' => $other->email,
    ])->assertNotFound();

    $this->actingAs($collaborator)->delete("/projects/{$project->id}/members/{$other->id}")->assertNotFound();

    $this->assertDatabaseCount('project_user', 1);
});
