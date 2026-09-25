<?php

use App\Models\Project;
use App\Models\User;

test('authenticated users receive their project summaries on authenticated pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AppHome')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('projects.0.title', $project->title)
        );
});

test('users without projects receive an empty projects list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('projects', 0));
});

test('guests do not receive the projects prop', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('projects'));
});
