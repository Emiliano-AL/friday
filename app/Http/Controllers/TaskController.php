<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveTaskRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Display the global "Mis Tareas" board for the authenticated user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tasks = Task::query()
            ->where(fn ($query) => $query->where('assignee_id', $user->id)
                ->orWhereHas('project', fn ($project) => $project->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))))
            ->with(['project:id,title', 'sprint:id,name', 'assignee:id,name,avatar'])
            ->withCount('comments')
            ->orderByDesc('updated_at')
            ->get();

        $projectsById = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))
            ->with(['members:id,name,avatar', 'sprints:id,project_id,name'])
            ->orderBy('title')
            ->get(['id', 'title', 'owner_id', 'status'])
            ->keyBy('id');

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks->map(fn (Task $task) => $this->taskPayload($task, $user, $projectsById)),
            'projects' => $projectsById->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'members' => $project->members->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar' => $member->avatar,
                ])->values(),
                'sprints' => $project->sprints->map(fn ($sprint) => [
                    'id' => $sprint->id,
                    'name' => $sprint->name,
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Store a new task, standalone or bound to one of the user's projects.
     */
    public function store(SaveTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['type'] ??= 'other';
        $validated['priority'] ??= 'medium';
        $validated['status'] ??= 'todo';

        if ($validated['project_id'] ?? null) {
            $project = Project::query()->findOrFail($validated['project_id']);

            abort_unless($project->isMember($request->user()), 404);

            $this->authorize('create', [Task::class, $project]);
        }

        Task::query()->create($validated);

        return back()->with('success', 'Tarea creada.');
    }

    /**
     * Update the task (full form or partial, e.g. status-only changes).
     */
    public function update(SaveTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return back()->with('success', 'Tarea actualizada.');
    }

    /**
     * Remove the task (owner of an active project, or its assignee when standalone).
     */
    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tarea eliminada.');
    }

    /**
     * Return the task with its comments as JSON (comments modal on the global board).
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        abort_unless($request->user()->can('view', $task), 404);

        return response()->json($this->taskDetail($task));
    }

    /**
     * Store a new comment on the task and return the refreshed detail JSON.
     */
    public function storeComment(StoreCommentRequest $request, Task $task): JsonResponse
    {
        abort_unless($request->user()->can('view', $task), 404);

        $task->comments()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json($this->taskDetail($task->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function taskDetail(Task $task): array
    {
        $task->loadMissing(['project:id,title', 'sprint:id,name', 'assignee:id,name,avatar'])
            ->load(['comments' => fn ($query) => $query->orderBy('created_at')->with('author:id,name')]);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'comments' => $task->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => ['id' => $comment->author->id, 'name' => $comment->author->name],
                'createdAt' => $comment->created_at->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    private function taskPayload(Task $task, User $user, $projects): array
    {
        $project = $task->project_id !== null ? $projects->get($task->project_id) : null;

        $canUpdate = $project !== null
            ? ($project->isOwnedBy($user) || $project->members->contains('id', $user->id)) && $project->status->isActive()
            : $task->assignee_id === $user->id;

        $canDelete = $project !== null
            ? $project->isOwnedBy($user) && $project->status->isActive()
            : $task->assignee_id === $user->id;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type->value,
            'typeLabel' => $task->type->label(),
            'priority' => $task->priority->value,
            'priorityLabel' => $task->priority->label(),
            'status' => $task->status->value,
            'statusLabel' => $task->status->label(),
            'project' => $project !== null ? ['id' => $project->id, 'title' => $project->title] : null,
            'sprint' => $task->sprint !== null ? ['id' => $task->sprint->id, 'name' => $task->sprint->name] : null,
            'assignee' => $task->assignee !== null
                ? ['id' => $task->assignee->id, 'name' => $task->assignee->name, 'avatar' => $task->assignee->avatar]
                : null,
            'commentsCount' => $task->comments_count,
            'canUpdate' => $canUpdate,
            'canDelete' => $canDelete,
        ];
    }
}
