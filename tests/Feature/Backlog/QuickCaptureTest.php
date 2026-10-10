<?php

use App\Models\Project;
use App\Models\User;

test('quick capture stores a backlog task with the spec defaults', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Revisar webhook',
            'status' => 'backlog',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Revisar webhook',
        'status' => 'backlog',
        'priority' => 'medium',
        'type' => 'other',
        'project_id' => null,
        'assignee_id' => null,
        'author_id' => $user->id,
        'code' => 'FRN-1',
    ]);

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Responder correo de soporte',
            'status' => 'backlog',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Responder correo de soporte',
        'author_id' => $user->id,
        'code' => 'FRN-2',
    ]);
});

test('quick capture honors the chosen priority and project', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::factory()
        ->for($owner, 'owner')
        ->hasAttached($user, [], 'members')
        ->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Revisar webhook',
            'status' => 'backlog',
            'priority' => 'urgent',
            'project_id' => $project->id,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Revisar webhook',
        'status' => 'backlog',
        'priority' => 'urgent',
        'project_id' => $project->id,
        'author_id' => $user->id,
    ]);
});

test('quick capture rejects an empty title and stores nothing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => '   ',
            'status' => 'backlog',
        ])
        ->assertSessionHasErrors(['title']);

    $this->assertDatabaseCount('tasks', 0);
});

test('quick capture rejects a null priority and stores nothing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Revisar webhook',
            'status' => 'backlog',
            'priority' => null,
        ])
        ->assertSessionHasErrors(['priority']);

    $this->assertDatabaseCount('tasks', 0);
});

test('quick capture requires authentication', function () {
    $this->post(route('tasks.store'), [
        'title' => 'Revisar webhook',
        'status' => 'backlog',
    ])->assertRedirect('/login');
});
