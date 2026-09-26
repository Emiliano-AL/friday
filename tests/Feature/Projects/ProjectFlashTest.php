<?php

use App\Models\Project;
use App\Models\User;

test('flash messages are shared with authenticated responses', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['success' => 'Proyecto creado.'])
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->where('flash.success', 'Proyecto creado.')
            ->where('flash.error', null)
        );
});

test('flash messages default to null', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->where('flash.success', null)
            ->where('flash.error', null)
        );
});

test('creating a project flashes a success message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/projects', ['title' => 'Nuevo proyecto'])
        ->assertSessionHas('success');
});

test('updating a project flashes a success message', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->put("/projects/{$project->id}", ['title' => 'Renombrado'])
        ->assertSessionHas('success');
});

test('transitioning a project flashes a success message', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->put("/projects/{$project->id}/status", ['status' => 'archived'])
        ->assertSessionHas('success');
});

test('deleting a project flashes a success message', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->delete("/projects/{$project->id}")
        ->assertSessionHas('success');
});
