<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

test('global task creation honors an explicit backlog status', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Pendiente del backlog',
        'status' => 'backlog',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => 'Pendiente del backlog',
        'status' => 'backlog',
        'author_id' => $user->id,
    ]);
});

test('global task creation validates points', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Puntos negativos',
        'points' => -1,
    ])->assertSessionHasErrors('points');

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Demasiados puntos',
        'points' => 100,
    ])->assertSessionHasErrors('points');

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Cero puntos',
        'points' => 0,
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post('/tasks', [
        'title' => 'Casi cien',
        'points' => 99,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', ['title' => 'Cero puntos', 'points' => 0]);
    $this->assertDatabaseHas('tasks', ['title' => 'Casi cien', 'points' => 99]);
});

test('global task update validates points', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create();

    $this->actingAs($user)->put("/tasks/{$task->id}", ['points' => -1])
        ->assertSessionHasErrors('points');

    $this->actingAs($user)->put("/tasks/{$task->id}", ['points' => 100])
        ->assertSessionHasErrors('points');

    $this->actingAs($user)->put("/tasks/{$task->id}", ['points' => 5])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'points' => 5]);
});

test('project task update validates points', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create();

    $payload = [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => $task->status->value,
    ];

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [...$payload, 'points' => -1])
        ->assertSessionHasErrors('points');

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [...$payload, 'points' => 100])
        ->assertSessionHasErrors('points');

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [...$payload, 'points' => 0])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'points' => 0]);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [...$payload, 'points' => 99])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'points' => 99]);
});

test('project members can create a task and it appears in the project page', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $assignee = User::factory()->create();
    $project->members()->attach($assignee);

    $response = $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Implementar login',
        'description' => '**Contexto** del problema',
        'type' => 'feature',
        'priority' => 'high',
        'assignee_id' => $assignee->id,
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Implementar login',
        'type' => 'feature',
        'priority' => 'high',
        'status' => 'backlog',
        'assignee_id' => $assignee->id,
        'sprint_id' => null,
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.tasks', 1)
            ->where('project.tasks.0.title', 'Implementar login')
            ->where('project.tasks.0.type', 'feature')
            ->where('project.tasks.0.typeLabel', 'Feature')
            ->where('project.tasks.0.priority', 'high')
            ->where('project.tasks.0.priorityLabel', 'Alta')
            ->where('project.tasks.0.status', 'backlog')
            ->where('project.tasks.0.statusLabel', 'Backlog')
            ->where('project.tasks.0.assignee.id', $assignee->id)
            ->where('project.tasks.0.sprint', null)
            ->where('project.tasks.0.description', '**Contexto** del problema')
            ->where('project.tasks.0.commentsCount', 0)
        );
});

test('tasks are created isolated with defaults when type and priority are omitted', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea rápida',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Tarea rápida',
        'type' => TaskType::Other->value,
        'priority' => TaskPriority::Medium->value,
        'status' => TaskStatus::Backlog->value,
        'sprint_id' => null,
        'assignee_id' => null,
        'description' => null,
    ]);
});

test('tasks can be created linked to a sprint of the same project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea de sprint',
        'type' => 'bug',
        'priority' => 'urgent',
        'sprint_id' => $sprint->id,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'sprint_id' => $sprint->id,
        'type' => 'bug',
        'priority' => 'urgent',
    ]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks', 1)
            ->where('project.tasks.0.sprint.id', $sprint->id)
            ->where('project.tasks.0.sprint.name', $sprint->name)
        );
});

test('tasks are listed ordered by recently updated first', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    Task::factory()->for($project)->create(['title' => 'Primera', 'updated_at' => '2026-09-20 10:00:00']);
    Task::factory()->for($project)->create(['title' => 'Segunda', 'updated_at' => '2026-09-21 10:00:00']);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks', 2)
            ->where('project.tasks.0.title', 'Segunda')
            ->where('project.tasks.1.title', 'Primera')
        );
});

test('tasks can not be created without a valid title', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => '',
    ])->assertSessionHasErrors('title');

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => '   ',
    ])->assertSessionHasErrors('title');

    expect(Task::query()->count())->toBe(0);
});

test('tasks can not be created with an assignee who is not a project member', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea con intruso',
        'assignee_id' => $outsider->id,
    ]);

    $response->assertSessionHasErrors('assignee_id');
    expect(Task::query()->count())->toBe(0);
});

test('tasks can not be created with a sprint from another project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $foreignSprint = Sprint::factory()->for($otherProject)->create();

    $response = $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea con sprint ajeno',
        'sprint_id' => $foreignSprint->id,
    ]);

    $response->assertSessionHasErrors('sprint_id');
    expect(Task::query()->count())->toBe(0);
});

test('tasks can not be created with invalid enum values', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea con tipo inválido',
        'type' => 'chore',
    ])->assertSessionHasErrors('type');

    $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
        'title' => 'Tarea con prioridad inválida',
        'priority' => 'extreme',
    ])->assertSessionHasErrors('priority');

    expect(Task::query()->count())->toBe(0);
});

test('project members can edit all fields of a task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $assignee = User::factory()->create();
    $project->members()->attach($assignee);
    $sprint = Sprint::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Antes',
        'description' => 'Descripción vieja',
    ]);

    $response = $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Después',
        'description' => '**Nueva** descripción',
        'type' => 'bug',
        'priority' => 'urgent',
        'status' => 'in_progress',
        'assignee_id' => $assignee->id,
        'sprint_id' => $sprint->id,
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Después',
        'description' => '**Nueva** descripción',
        'type' => 'bug',
        'priority' => 'urgent',
        'status' => 'in_progress',
        'assignee_id' => $assignee->id,
        'sprint_id' => $sprint->id,
    ]);
});

test('task status can move freely between flow values', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['status' => 'todo']);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'done',
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'backlog',
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'backlog']);
});

test('tasks can not be updated with invalid data and keep their values', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Original',
        'status' => 'todo',
    ]);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => '',
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'todo',
    ])->assertSessionHasErrors('title');

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiado',
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'invalid-status',
    ])->assertSessionHasErrors('status');

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiado',
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'todo',
        'assignee_id' => $outsider->id,
    ])->assertSessionHasErrors('assignee_id');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Original',
        'status' => 'todo',
        'assignee_id' => null,
    ]);
});

test('tasks from another project are not accessible for update or delete', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($otherProject)->create(['title' => 'Ajena']);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => 'Cambiado',
        'type' => 'other',
        'priority' => 'medium',
        'status' => 'todo',
    ])->assertNotFound();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Ajena']);
});

test('isolated tasks can be linked to and unlinked from a sprint', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Tarea movible',
        'sprint_id' => null,
    ]);

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => $task->status->value,
        'sprint_id' => $sprint->id,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'sprint_id' => $sprint->id]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks', 1)
            ->where('project.tasks.0.sprint.id', $sprint->id)
        );

    $this->actingAs($user)->put("/projects/{$project->id}/tasks/{$task->id}", [
        'title' => $task->title,
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => $task->status->value,
        'sprint_id' => null,
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'sprint_id' => null]);

    $this->actingAs($user)
        ->get("/projects/{$project->id}")
        ->assertInertia(fn ($page) => $page
            ->has('project.tasks', 1)
            ->where('project.tasks.0.sprint', null)
        );
});

test('deleting a sprint leaves its tasks isolated', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $sprint = Sprint::factory()->for($project)->create();
    $linked = Task::factory()->for($project)->create(['title' => 'Vinculada', 'sprint_id' => $sprint->id]);
    $isolated = Task::factory()->for($project)->create(['title' => 'Aislada']);

    $sprint->delete();

    $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    $this->assertDatabaseHas('tasks', ['id' => $linked->id, 'sprint_id' => null, 'title' => 'Vinculada']);
    $this->assertDatabaseHas('tasks', ['id' => $isolated->id, 'sprint_id' => null]);
});

test('project owners can delete a task and its comments without affecting anything else', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($project)->create(['title' => 'A eliminar']);
    Comment::factory()->for($task)->for($user, 'author')->create(['body' => 'Nota']);
    $otherTask = Task::factory()->for($project)->create(['title' => 'Permanece']);

    $response = $this->actingAs($user)->delete("/projects/{$project->id}/tasks/{$task->id}");

    $response->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    $this->assertDatabaseMissing('comments', ['task_id' => $task->id]);
    $this->assertDatabaseHas('projects', ['id' => $project->id]);
    $this->assertDatabaseHas('tasks', ['id' => $otherTask->id, 'title' => 'Permanece']);
});

test('tasks from another project are not accessible for deletion', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create();
    $otherProject = Project::factory()->for($user, 'owner')->create();
    $task = Task::factory()->for($otherProject)->create(['title' => 'Ajena']);

    $this->actingAs($user)->delete("/projects/{$project->id}/tasks/{$task->id}")->assertNotFound();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Ajena']);
});

test('a project member can move a project task via status-only global update', function () {
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->hasAttached($member, [], 'members')->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Arrastrada',
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($member)->put("/tasks/{$task->id}", ['status' => 'in_progress'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Arrastrada',
        'type' => $task->type->value,
        'priority' => $task->priority->value,
        'status' => 'in_progress',
        'sprint_id' => $task->sprint_id,
    ]);

    $this->actingAs($outsider)->put("/tasks/{$task->id}", ['status' => 'done'])
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
});

test('destroying a task through the global route redirects back to the page it was requested from', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->from('/backlog')
        ->delete("/tasks/{$task->id}")
        ->assertRedirect('/backlog');

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('destroying a task through the global route falls back to the tasks index without a referer', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->delete("/tasks/{$task->id}")
        ->assertRedirect(route('tasks.index', absolute: false));

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('destroying a task ignores a referer that points to another origin', function () {
    $user = User::factory()->create();
    $task = Task::factory()->standalone()->for($user, 'author')->create(['status' => 'backlog']);

    $this->actingAs($user)
        ->from('https://evil.example.com/backlog')
        ->delete("/tasks/{$task->id}")
        ->assertRedirect(route('tasks.index', absolute: false));

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});
