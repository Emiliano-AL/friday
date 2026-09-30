<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('task description rejects more than 50000 characters and accepts the limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Tarea con descripción gigante',
        'description' => str_repeat('a', 50001),
        'assignee_id' => $user->id,
    ])->assertSessionHasErrors('description');

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Tarea límite',
        'description' => str_repeat('a', 50000),
        'assignee_id' => $user->id,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Tarea límite',
    ]);

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Tarea sin descripción',
        'assignee_id' => $user->id,
    ])->assertSessionDoesntHaveErrors();
});

test('project description rejects more than 50000 characters in store and update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/projects', [
        'title' => 'Proyecto inválido',
        'description' => str_repeat('b', 50001),
    ])->assertSessionHasErrors('description');

    $this->actingAs($user)->post('/projects', [
        'title' => 'Proyecto límite',
        'description' => str_repeat('b', 50000),
    ])->assertSessionDoesntHaveErrors();

    $project = Project::query()->where('title', 'Proyecto límite')->sole();

    $this->actingAs($user)->put("/projects/{$project->id}", [
        'title' => 'Proyecto límite',
        'description' => str_repeat('c', 50001),
    ])->assertSessionHasErrors('description');
});

test('sprint goal rejects more than 50000 characters in store and update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint inválido',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'goal' => str_repeat('d', 50001),
    ])->assertSessionHasErrors('goal');

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint límite',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'goal' => str_repeat('d', 50000),
    ])->assertSessionDoesntHaveErrors();

    $sprint = Sprint::query()->where('name', 'Sprint límite')->sole();

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Sprint límite',
        'goal' => str_repeat('e', 50001),
    ])->assertSessionHasErrors('goal');

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Sprint límite',
        'goal' => str_repeat('e', 50000),
    ])->assertSessionDoesntHaveErrors();
});

test('structured block content persists as-is through task project and sprint endpoints', function () {
    $content = json_encode([
        'time' => 1727654400000,
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Contenido <b>enriquecido</b>']],
        ],
        'version' => '2.31',
    ], JSON_THROW_ON_ERROR);

    $user = User::factory()->create();

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Tarea enriquecida',
        'description' => $content,
        'assignee_id' => $user->id,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('tasks', ['title' => 'Tarea enriquecida', 'description' => $content]);

    $this->actingAs($user)->post('/projects', [
        'title' => 'Proyecto enriquecido',
        'description' => $content,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('projects', ['title' => 'Proyecto enriquecido', 'description' => $content]);

    $project = Project::query()->where('title', 'Proyecto enriquecido')->sole();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint enriquecido',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'goal' => $content,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('sprints', ['name' => 'Sprint enriquecido', 'goal' => $content]);
});
