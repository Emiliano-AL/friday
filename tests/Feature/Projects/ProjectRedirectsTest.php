<?php

use App\Models\Project;
use App\Models\User;

test('the create route redirects to the directory with the create dialog query', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/projects/create')
        ->assertRedirect('/projects?new=1');
});

test('the edit route redirects owners to the show page with the edit query', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->get("/projects/{$project->id}/edit")
        ->assertRedirect("/projects/{$project->id}?edit=1");
});

test('the edit route still rejects non owners with a not found', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->hasAttached($member, [], 'members')->create();

    $this->actingAs($member)
        ->get("/projects/{$project->id}/edit")
        ->assertNotFound();
});
