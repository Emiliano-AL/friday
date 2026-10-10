<?php

namespace App\Http\Controllers;

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;

class BacklogController extends Controller
{
    /**
     * Display the general backlog: every visible pending (unsprinted, not done)
     * task across standalone items and projects, for quick capture and triage.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $tasks = Task::query()
            ->visibleTo($user)
            ->backlog()
            ->with(['project:id,title,owner_id,status', 'sprint:id,name,status', 'assignee:id,name,avatar'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Backlog/Index', [
            'tasks' => $tasks->map(fn (Task $task) => $this->taskPayload($task, $user))->values(),
            'summary' => $this->summary($tasks),
            'nextSprint' => $this->nextSprint($user),
            'projects' => $this->projectOptions($user),
        ]);
    }

    /**
     * Summary counts for the backlog header and filters, computed over the
     * same filtered collection that feeds the tasks payload (SC-003).
     *
     * @param  SupportCollection<int, Task>  $tasks
     * @return array{total: int, totalPoints: int, withoutProject: int, withProject: int}
     */
    private function summary(SupportCollection $tasks): array
    {
        $withoutProject = $tasks->whereNull('project_id')->count();

        return [
            'total' => $tasks->count(),
            'totalPoints' => $tasks->sum(fn (Task $task) => $task->points ?? 0),
            'withoutProject' => $withoutProject,
            'withProject' => $tasks->count() - $withoutProject,
        ];
    }

    /**
     * Map a task to the Backlog/Index payload (see specs/018-backlog-quick-capture/contracts/backlog.md).
     *
     * @return array<string, mixed>
     */
    private function taskPayload(Task $task, User $user): array
    {
        return [
            'id' => $task->id,
            'code' => $task->code,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type->value,
            'priority' => $task->priority->value,
            'status' => $task->status->value,
            'points' => $task->points,
            'completedAt' => $task->completed_at?->toIso8601String(),
            'createdAt' => $task->created_at?->toIso8601String(),
            'project' => $task->project !== null
                ? ['id' => $task->project->id, 'title' => $task->project->title]
                : null,
            'sprint' => $task->sprint !== null
                ? ['id' => $task->sprint->id, 'name' => $task->sprint->name, 'status' => $task->sprint->status->value]
                : null,
            'assignee' => $task->assignee !== null
                ? ['id' => $task->assignee->id, 'name' => $task->assignee->name, 'avatar' => $task->assignee->avatar]
                : null,
            'authorId' => $task->author_id,
            'canUpdate' => $user->can('update', $task),
            'canDelete' => $user->can('delete', $task),
        ];
    }

    /**
     * Nearest active or planned sprint across the user's projects (owner or
     * member), surfaced by the delivery banner to pull work into planning.
     *
     * @return array{id: int, name: string, projectId: int, projectTitle: string, status: string, startsAt: string, endsAt: string}|null
     */
    private function nextSprint(User $user): ?array
    {
        $sprint = Sprint::query()
            ->whereIn('status', [SprintStatus::Active, SprintStatus::Planned])
            ->whereHas('project', fn ($project) => $project->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($members) => $members->whereKey($user->id)))
            ->with('project:id,title')
            ->orderBy('start_date')
            ->first();

        if ($sprint === null) {
            return null;
        }

        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'projectId' => $sprint->project_id,
            'projectTitle' => $sprint->project->title,
            'status' => $sprint->status->value,
            'startsAt' => $sprint->start_date->toDateString(),
            'endsAt' => $sprint->end_date->toDateString(),
        ];
    }

    /**
     * Project choices for the quick capture composer, same shape as
     * TaskController@index (owner + member projects with their people and sprints).
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function projectOptions(User $user): SupportCollection
    {
        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))
            ->with([
                'owner:id,name,avatar',
                'members:id,name,avatar',
                'sprints' => fn ($sprints) => $sprints
                    ->whereIn('status', [SprintStatus::Active, SprintStatus::Planned])
                    ->select('id', 'project_id', 'name'),
            ])
            ->orderBy('title')
            ->get(['id', 'title', 'owner_id', 'status'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'members' => $project->members
                    ->prepend($project->owner)
                    ->unique('id')
                    ->map(fn (User $member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatar' => $member->avatar,
                    ])->values(),
                'sprints' => $project->sprints->map(fn ($sprint) => [
                    'id' => $sprint->id,
                    'name' => $sprint->name,
                ])->values(),
            ]);
    }
}
