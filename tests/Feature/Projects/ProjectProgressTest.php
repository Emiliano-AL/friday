<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('projects without tasks report zero progress', function () {
    $project = Project::factory()->create();

    expect($project->progressPercentage())->toBe(0);
});

test('progress is the rounded percentage of done tasks over total tasks', function () {
    $project = Project::factory()->create();

    Task::factory()->for($project)->count(3)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['status' => TaskStatus::InProgress]);

    expect($project->progressPercentage())->toBe(75);
});

test('progress rounds down to the nearest integer', function () {
    $project = Project::factory()->create();

    Task::factory()->for($project)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Todo]);

    expect($project->progressPercentage())->toBe(33);
});

test('tasks in review do not count as done', function () {
    $project = Project::factory()->create();

    Task::factory()->for($project)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->create(['status' => TaskStatus::InReview]);
    Task::factory()->for($project)->create(['status' => TaskStatus::Backlog]);

    expect($project->progressPercentage())->toBe(33);
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

test('index and show expose the same non-zero progress', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Done]);
    Task::factory()->for($project)->count(2)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Index')
            ->where('projects.0.progress', 50)
            ->where('projects.0.taskDoneCount', 2)
            ->where('projects.0.taskTotalCount', 4)
        );

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->where('project.progress', 50)
            ->where('project.taskDoneCount', 2)
            ->where('project.taskTotalCount', 4)
        );
});

test('progress is always within the zero to one hundred range', function () {
    $project = Project::factory()->create();

    $progress = $project->progressPercentage();

    expect($progress)->toBeGreaterThanOrEqual(0);
    expect($progress)->toBeLessThanOrEqual(100);
});
