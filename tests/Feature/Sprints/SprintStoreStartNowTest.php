<?php

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('creating with start now activates the sprint immediately', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", [
        'name' => 'Sprint arrancado',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-07',
        'start_now' => true,
    ])->assertSessionDoesntHaveErrors();

    expect($project->sprints()->sole()->status)->toBe(SprintStatus::Active);
});

test('sprints are planned by default when start now is absent or false', function ($payload) {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/sprints", $payload)
        ->assertSessionDoesntHaveErrors();

    expect($project->sprints()->sole()->status)->toBe(SprintStatus::Planned);
})->with([
    'absent' => [['name' => 'Por defecto', 'start_date' => '2026-10-01', 'end_date' => '2026-10-07']],
    'false' => [['name' => 'Explicito', 'start_date' => '2026-10-01', 'end_date' => '2026-10-07', 'start_now' => false]],
]);

test('start now is ignored on update', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'status' => SprintStatus::Planned,
    ]);

    $this->actingAs($user)->put("/projects/{$project->id}/sprints/{$sprint->id}", [
        'name' => $sprint->name,
        'start_date' => $sprint->start_date->toDateString(),
        'end_date' => $sprint->end_date->toDateString(),
        'start_now' => true,
    ])->assertSessionDoesntHaveErrors();

    expect($sprint->refresh()->status)->toBe(SprintStatus::Planned);
});
