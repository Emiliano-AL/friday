<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    /**
     * Store a newly created task for the project (member of an active project).
     */
    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [Task::class, $project]);

        $validated = $request->validated();
        $validated['type'] ??= TaskType::Other->value;
        $validated['priority'] ??= TaskPriority::Medium->value;

        $project->tasks()->create($validated);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Update the task's fields and status (member of an active project).
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        abort_unless($task->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('update', [Task::class, $project]);

        $task->update($request->validated());

        return redirect()->route('projects.show', $project);
    }

    /**
     * Store a new comment on the task (member of an active project).
     */
    public function storeComment(StoreCommentRequest $request, Project $project, Task $task): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        abort_unless($task->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [Task::class, $project]);

        $task->comments()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove the task and its comments from the project (owner of an active project).
     */
    public function destroy(Request $request, Project $project, Task $task): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($task->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('delete', [Task::class, $project]);

        $task->delete();

        return redirect()->route('projects.show', $project);
    }

    /**
     * Block task management when the project is not active, with a clear message.
     */
    private function ensureProjectIsActive(Project $project): void
    {
        if (! $project->status->isActive()) {
            back()->withErrors([
                'project' => 'El proyecto no admite cambios en su estado actual.',
            ])->throwResponse();
        }
    }
}
