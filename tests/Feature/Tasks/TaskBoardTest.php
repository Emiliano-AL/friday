<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

test('the project payload carries the fields the board views consume', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $assignee = User::factory()->create();
    $project->members()->attach($assignee);
    $sprint = Sprint::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Tarea del tablero',
        'sprint_id' => $sprint->id,
        'assignee_id' => $assignee->id,
    ]);
    Comment::factory()->for($task)->for($user, 'author')->create();

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.tasks', 1)
            ->where('project.tasks.0.status', $task->status->value)
            ->where('project.tasks.0.priority', $task->priority->value)
            ->where('project.tasks.0.sprint.id', $sprint->id)
            ->where('project.tasks.0.sprint.name', $sprint->name)
            ->where('project.tasks.0.assignee.id', $assignee->id)
            ->where('project.tasks.0.assignee.name', $assignee->name)
            ->has('project.sprints', 1)
            ->where('project.sprints.0.startDate', $sprint->start_date->toDateString())
        );
});

test('board drag moves a task through the full status chain via the task update endpoint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Tarea del tablero',
        'status' => 'backlog',
    ]);

    $chain = ['todo', 'in_progress', 'in_review', 'done', 'backlog'];

    foreach ($chain as $status) {
        $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
            'title' => $task->title,
            'type' => $task->type->value,
            'priority' => $task->priority->value,
            'status' => $status,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => $status]);
    }
});

test('board drops with an invalid status are rejected and keep the task unchanged', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'blocked-column',
    ])->assertSessionHasErrors('status');

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'todo']);
});

test('board moves are blocked with a clear message when the project is not active', function (string $state) {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->$state()->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($owner)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'done',
    ])->assertSessionHasErrors([
        'project' => 'El proyecto no admite cambios en su estado actual.',
    ]);

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'todo']);
})->with(['archived', 'completed']);
