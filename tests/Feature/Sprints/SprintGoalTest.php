<?php

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('sprint goal is optional and persisted on create and update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint con objetivo',
        'goal' => 'Entregar el checkout seguro',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
    ])->assertSessionDoesntHaveErrors();

    $sprint = Sprint::query()->where('project_id', $project->id)->sole();

    expect($sprint->goal)->toBe('Entregar el checkout seguro');

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => 'Sprint con objetivo',
        'goal' => 'Objetivo refinado',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
    ])->assertSessionDoesntHaveErrors();

    expect($sprint->refresh()->goal)->toBe('Objetivo refinado');
});

test('sprint goal accepts null and rejects more than 1000 characters', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sin objetivo',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
    ])->assertSessionDoesntHaveErrors();

    expect(Sprint::query()->where('project_id', $project->id)->sole()->goal)->toBeNull();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Objetivo gigante',
        'goal' => str_repeat('a', 1001),
        'start_date' => '2026-10-08',
        'end_date' => '2026-10-14',
    ])->assertSessionHasErrors('goal');
});

test('sprint end date cannot be before the start date on create and update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Fechas invertidas',
        'start_date' => '2026-10-07',
        'end_date' => '2026-10-01',
    ])->assertSessionHasErrors('end_date');

    $sprint = Sprint::factory()->for($project)->create([
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'status' => SprintStatus::Planned,
    ]);

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => $sprint->name,
        'start_date' => '2026-10-07',
        'end_date' => '2026-10-01',
    ])->assertSessionHasErrors('end_date');

    expect($sprint->refresh()->start_date->toDateString())->toBe('2026-10-01');
});
