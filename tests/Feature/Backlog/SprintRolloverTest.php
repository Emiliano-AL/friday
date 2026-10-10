<?php

use App\Enums\SprintStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('completing a sprint returns its unfinished tasks to the backlog', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = $project->sprints()->create([
        'name' => 'Sprint 9',
        'status' => SprintStatus::Active,
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-16',
    ]);

    $unfinished = Task::factory()->for($project)->for($user, 'author')->create([
        'sprint_id' => $sprint->id,
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::High,
        'points' => 5,
    ]);
    $done = Task::factory()->for($project)->create([
        'sprint_id' => $sprint->id,
        'status' => TaskStatus::Done,
    ]);
    $standalone = Task::factory()->standalone()->for($user, 'author')->create([
        'status' => TaskStatus::Backlog,
        'priority' => TaskPriority::Low,
        'points' => 3,
    ]);

    $this->actingAs($user)
        ->post("/projects/{$project->id}/sprints/{$sprint->id}/complete")
        ->assertRedirect()
        ->assertSessionHas('success', 'Sprint completado.');

    expect($sprint->refresh()->status)->toBe(SprintStatus::Completed);

    // The unfinished task goes back to the backlog keeping its scope and triage.
    expect($unfinished->refresh())
        ->sprint_id->toBeNull()
        ->project_id->toBe($project->id)
        ->priority->toBe(TaskPriority::High)
        ->points->toBe(5)
        ->status->toBe(TaskStatus::InProgress)
        ->completed_at->toBeNull();

    // The done task stays bound to the completed sprint.
    expect($done->refresh()->sprint_id)->toBe($sprint->id);

    // Standalone tasks are untouched.
    expect($standalone->refresh())
        ->sprint_id->toBeNull()
        ->project_id->toBeNull()
        ->points->toBe(3);

    // The returned task is part of the backlog and still visible to its author.
    expect(Task::query()->backlog()->pluck('id'))->toContain($unfinished->id)
        ->and(Task::query()->visibleTo($user)->pluck('id'))->toContain($unfinished->id);
});
