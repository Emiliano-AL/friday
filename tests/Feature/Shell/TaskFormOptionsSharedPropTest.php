<?php

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the task form options are only sent when the form asks for them', function () {
    $user = User::factory()->create();
    Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->missing('taskFormProjects')
            ->reloadOnly('taskFormProjects', fn (Assert $reload) => $reload
                ->has('taskFormProjects', 1)
            )
        );
});

test('the task form options list the user projects with their team and open sprints', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $teammate = User::factory()->create(['name' => 'Beto']);
    $owned = Project::factory()->for($user, 'owner')->hasAttached($teammate, [], 'members')->create(['title' => 'Beta']);
    $joined = Project::factory()->hasAttached($user, [], 'members')->create(['title' => 'Alfa']);
    Project::factory()->create(['title' => 'Ajeno']);

    $active = Sprint::factory()->for($owned)->create(['status' => SprintStatus::Active, 'name' => 'Activo']);
    $planned = Sprint::factory()->for($owned)->create(['status' => SprintStatus::Planned, 'name' => 'Planeado']);
    Sprint::factory()->for($owned)->create(['status' => SprintStatus::Completed, 'name' => 'Cerrado']);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('taskFormProjects', fn (Assert $reload) => $reload
                ->has('taskFormProjects', 2)
                ->where('taskFormProjects.0.id', $joined->id)
                ->where('taskFormProjects.1.id', $owned->id)
                ->where('taskFormProjects.1.members', [
                    ['id' => $user->id, 'name' => 'Ana', 'avatar' => null],
                    ['id' => $teammate->id, 'name' => 'Beto', 'avatar' => null],
                ])
                ->where('taskFormProjects.1.sprints', [
                    ['id' => $active->id, 'name' => 'Activo'],
                    ['id' => $planned->id, 'name' => 'Planeado'],
                ])
            )
        );
});
