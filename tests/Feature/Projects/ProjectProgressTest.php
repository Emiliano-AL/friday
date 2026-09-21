<?php

use App\Models\Project;
use App\Models\User;

test('projects without tasks report zero progress', function () {
    $project = Project::factory()->create();

    expect($project->progressPercentage())->toBe(0);
});

test('index and show expose the same zero progress for projects without tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->where('projects.0.progress', 0)
        );

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->where('project.progress', 0)
            ->where('project.allowedTransitions', ['archived', 'completed'])
        );
});

test('progress is always within the zero to one hundred range', function () {
    $project = Project::factory()->create();

    $progress = $project->progressPercentage();

    expect($progress)->toBeGreaterThanOrEqual(0);
    expect($progress)->toBeLessThanOrEqual(100);
});
