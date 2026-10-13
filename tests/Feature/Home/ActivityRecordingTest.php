<?php

use App\Enums\ActivityType;
use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

test('marking a task as done sets completed_at and records a task completed activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($sprint)->create([
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($user);
    $task->update(['status' => TaskStatus::Done]);

    expect($task->refresh()->completed_at)->not->toBeNull();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::TaskCompleted->value,
    ]);
});

test('completing a standalone task sets completed_at without a project activity', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->create([
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($user);
    $task->update(['status' => TaskStatus::Done]);

    expect($task->refresh()->completed_at)->not->toBeNull();
    expect(Activity::query()->count())->toBe(0);
});

test('reopening a done task clears completed_at without recording a new activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'status' => TaskStatus::Done,
        'completed_at' => now(),
    ]);

    $this->actingAs($user);

    $task->update(['status' => TaskStatus::InProgress]);

    expect($task->refresh()->completed_at)->toBeNull();
    expect(Activity::query()->count())->toBe(0);
});

test('reaching done from another status records exactly one completion activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'status' => TaskStatus::InProgress,
    ]);

    $this->actingAs($user);

    $task->update(['status' => TaskStatus::Done]);
    $countAfterCompletion = Activity::query()->count();

    $task->update(['status' => TaskStatus::InReview]);
    $task->update(['status' => TaskStatus::Todo]);

    expect(Activity::query()->count())->toBe($countAfterCompletion);
});

test('creating a task inside a project records a task created activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user);
    $task = Task::factory()->for($project)->create();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'type' => ActivityType::TaskCreated->value,
    ]);
});

test('creating a standalone task records no activity', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Task::factory()->standalone()->create();

    expect(Activity::query()->count())->toBe(0);
});

test('creating a comment records a comment created activity for the task project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user);
    Comment::factory()->for($task)->for($user, 'author')->create();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'task_id' => $task->id,
        'type' => ActivityType::CommentCreated->value,
    ]);
});

test('starting and completing a sprint records the sprint lifecycle activities', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'status' => SprintStatus::Planned,
    ]);

    $this->actingAs($user);

    $sprint->start();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::SprintStarted->value,
    ]);

    $sprint->complete();

    $this->assertDatabaseHas('activities', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::SprintCompleted->value,
    ]);

    expect(Activity::query()->where('sprint_id', $sprint->id)->count())->toBe(2);
});

test('sprint lifecycle activities use the acting user as the actor', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create([
        'status' => SprintStatus::Planned,
    ]);

    $this->actingAs($member);
    $sprint->start();

    $this->assertDatabaseHas('activities', [
        'user_id' => $member->id,
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => ActivityType::SprintStarted->value,
    ]);

    expect(Activity::query()->where('user_id', $owner->id)->count())->toBe(0);
});
