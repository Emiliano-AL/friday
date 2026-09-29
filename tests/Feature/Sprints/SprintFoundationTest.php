<?php

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

test('sprints have a nullable goal and default to planned status', function () {
    $project = Project::factory()->for(User::factory()->create(), 'owner')->create();

    $withGoal = Sprint::factory()->for($project)->create(['goal' => 'Entregar checkout seguro']);
    $plain = Sprint::factory()->for($project)->create();

    expect($withGoal->refresh()->goal)->toBe('Entregar checkout seguro');
    expect($plain->refresh()->goal)->toBeNull();
    expect($plain->refresh()->status)->toBe(SprintStatus::Planned);
});

test('active sprint is resolved by status rather than by dates', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create([
        'name' => 'Reciente planificado',
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-27',
        'status' => SprintStatus::Planned,
    ]);

    $active = Sprint::factory()->for($project)->create([
        'name' => 'Activo anterior',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'status' => SprintStatus::Active,
    ]);

    $project->load('activeSprint');

    expect($project->activeSprint->id)->toBe($active->id);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->where('project.activeSprint.id', $active->id)
            ->where('project.activeSprint.status', 'active')
        );
});

test('project payloads expose sprint status and goal', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Sprint::factory()->for($project)->create([
        'name' => 'Con objetivo',
        'goal' => 'Reducir fricción de checkout',
        'status' => SprintStatus::Active,
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.sprints', 1)
            ->where('project.sprints.0.status', 'active')
            ->where('project.sprints.0.statusLabel', 'Activo')
            ->where('project.sprints.0.goal', 'Reducir fricción de checkout')
            ->where('project.activeSprint.statusLabel', 'Activo')
        );
});

test('completed sprints are read-only for the owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'name' => 'Cerrado',
        'status' => SprintStatus::Completed,
    ]);

    $this->actingAs($user)
        ->put("/projects/{$project->id}/sprints/{$sprint->id}", [
            'name' => 'Renombrado',
            'start_date' => $sprint->start_date->toDateString(),
            'end_date' => $sprint->end_date->toDateString(),
        ])
        ->assertSessionHasErrors('sprint');

    expect($sprint->refresh()->name)->toBe('Cerrado');

    $this->actingAs($user)
        ->delete("/projects/{$project->id}/sprints/{$sprint->id}")
        ->assertSessionHasErrors('sprint');

    $this->assertDatabaseHas('sprints', ['id' => $sprint->id]);
});

test('planned and active sprints remain editable and deletable', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    foreach ([SprintStatus::Planned, SprintStatus::Active] as $status) {
        $sprint = Sprint::factory()->for($project)->create(['status' => $status]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/sprints/{$sprint->id}", [
                'name' => "Editado {$status->value}",
                'start_date' => $sprint->start_date->toDateString(),
                'end_date' => $sprint->end_date->toDateString(),
            ])
            ->assertSessionDoesntHaveErrors();

        expect($sprint->refresh()->name)->toBe("Editado {$status->value}");

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/sprints/{$sprint->id}")
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    }
});
