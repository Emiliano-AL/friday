<?php

use App\Models\Project;
use App\Models\User;

test('non-members get a not found response for any project route', function () {
    $owner = User::factory()->create();
    $foreign = User::factory()->create();
    $collaborator = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($foreign)->get("/projects/{$project->id}")->assertNotFound();
    $this->actingAs($foreign)->get("/projects/{$project->id}/edit")->assertNotFound();

    $this->actingAs($foreign)->put("/projects/{$project->id}", ['title' => 'Cambiado'])->assertNotFound();

    $this->actingAs($foreign)->put("/projects/{$project->id}/status", ['status' => 'archived'])->assertNotFound();

    $this->actingAs($foreign)->delete("/projects/{$project->id}")->assertNotFound();

    $this->actingAs($foreign)->post("/projects/{$project->id}/members", [
        'email' => $collaborator->email,
    ])->assertNotFound();

    $this->actingAs($foreign)->delete("/projects/{$project->id}/members/{$collaborator->id}")->assertNotFound();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => $project->title]);
    $this->assertDatabaseCount('project_user', 0);
});

test('the projects index never lists foreign projects', function () {
    $user = User::factory()->create();
    $foreign = User::factory()->create();

    Project::factory()->for($foreign, 'owner')->count(3)->create();

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page->has('projects', 0));
});

test('guests are redirected to login from any projects route', function () {
    $project = Project::factory()->create();

    $this->get('/projects')->assertRedirect(route('login', absolute: false));
    $this->get("/projects/{$project->id}")->assertRedirect(route('login', absolute: false));
});
