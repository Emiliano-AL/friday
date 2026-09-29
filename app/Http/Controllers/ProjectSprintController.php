<?php

namespace App\Http\Controllers;

use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectSprintController extends Controller
{
    /**
     * Store a newly created sprint for the project (owner of an active project).
     */
    public function store(StoreSprintRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [Sprint::class, $project]);

        $project->sprints()->create([
            ...$request->validated(),
            'status' => $request->boolean('start_now')
                ? SprintStatus::Active
                : SprintStatus::Planned,
        ]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Update the sprint's name and dates (owner of an active project).
     */
    public function update(UpdateSprintRequest $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->ensureSprintIsWritable($sprint);

        $this->authorize('update', [Sprint::class, $project]);

        $sprint->update($request->validated());

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove the sprint from the project (owner of an active project).
     */
    public function destroy(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->ensureSprintIsWritable($sprint);

        $this->authorize('delete', [Sprint::class, $project]);

        $sprint->delete();

        return redirect()->route('projects.show', $project);
    }

    /**
     * Activate a planned sprint (owner of an active project).
     */
    public function start(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $sprint->start();

        return back()->with('success', 'Sprint activado.');
    }

    /**
     * Complete an active sprint (owner of an active project).
     */
    public function complete(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $sprint->complete();

        return back()->with('success', 'Sprint completado.');
    }

    /**
     * Display the sprint detail page (any member of the project).
     */
    public function show(Request $request, Project $project, Sprint $sprint): Response
    {
        abort_unless($project->isMember($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $project->loadMissing(['members:id,name,avatar', 'sprints:id,project_id,name,start_date']);

        $tasks = $sprint->tasks()
            ->with(['project:id,title', 'sprint:id,name', 'assignee:id,name,avatar'])
            ->withCount('comments')
            ->orderByDesc('updated_at')
            ->get();

        $canWrite = $project->isOwnedBy($request->user())
            && $project->status->isActive()
            && $sprint->status !== SprintStatus::Completed;

        return Inertia::render('Sprints/Show', [
            'sprint' => $this->sprintDetail($sprint, $project, $canWrite),
            'metrics' => $this->metrics($tasks),
            'tasks' => $tasks
                ->map(fn (Task $task) => $this->taskPayload($task, $request->user(), $project, $canWrite))
                ->values(),
            'taskContext' => [
                'project' => ['id' => $project->id, 'title' => $project->title],
                'members' => $project->members->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar' => $member->avatar,
                ])->values(),
                'sprints' => $project->sprints->map(fn (Sprint $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                ])->values(),
            ],
            'canWrite' => $canWrite,
        ]);
    }

    /**
     * Build the sprint header payload for the detail page.
     *
     * @return array<string, mixed>
     */
    private function sprintDetail(Sprint $sprint, Project $project, bool $canWrite): array
    {
        $today = now()->startOfDay();
        $start = $sprint->start_date->startOfDay();
        $end = $sprint->end_date->startOfDay();

        $daysRemaining = 0;
        $cursor = $today->copy()->addDay();

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $daysRemaining++;
            }

            $cursor = $cursor->addDay();
        }

        $totalDays = $start->diffInDays($end);
        $elapsedDays = $start->diffInDays($today);

        $percentElapsed = $totalDays <= 0
            ? ($today->gte($start) ? 100 : 0)
            : (int) round(min(100, max(0, $elapsedDays / $totalDays * 100)));

        $iterationIds = $project->sprints->sortBy('start_date')->pluck('id');
        $current = $iterationIds->search($sprint->id);

        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'status' => $sprint->status->value,
            'statusLabel' => $sprint->status->label(),
            'startDate' => $sprint->start_date->toDateString(),
            'endDate' => $sprint->end_date->toDateString(),
            'iteration' => [
                'current' => $current === false ? 1 : $current + 1,
                'total' => $iterationIds->count(),
            ],
            'daysRemaining' => $daysRemaining,
            'percentElapsed' => $percentElapsed,
            'canWrite' => $canWrite,
            'project' => ['id' => $project->id, 'title' => $project->title],
        ];
    }

    /**
     * Compute the sprint metrics from its real tasks.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<string, mixed>
     */
    private function metrics($tasks): array
    {
        $total = $tasks->count();
        $done = $tasks->where('status', TaskStatus::Done)->count();

        $breakdown = collect([TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::InReview, TaskStatus::Done])
            ->map(fn (TaskStatus $status) => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $tasks->where('status', $status)->count(),
                'percent' => $total === 0
                    ? 0
                    : (int) round($tasks->where('status', $status)->count() / $total * 100),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        return [
            'total' => $total,
            'done' => $done,
            'coverage' => $total === 0 ? 0 : (int) round($done / $total * 100),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Map a task to the shared board payload used by the sprint task list.
     *
     * @return array<string, mixed>
     */
    private function taskPayload(Task $task, User $user, Project $project, bool $sprintWritable): array
    {
        $isMember = $project->isOwnedBy($user)
            || $project->members->contains('id', $user->id);

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
            'project' => ['id' => $project->id, 'title' => $project->title],
            'sprint' => $task->sprint !== null
                ? ['id' => $task->sprint->id, 'name' => $task->sprint->name]
                : null,
            'assignee' => $task->assignee !== null
                ? ['id' => $task->assignee->id, 'name' => $task->assignee->name, 'avatar' => $task->assignee->avatar]
                : null,
            'commentsCount' => $task->comments_count,
            'canUpdate' => $sprintWritable && $isMember,
            'canDelete' => $sprintWritable && $project->isOwnedBy($user),
        ];
    }

    /**
     * Block sprint management when the project is not active, with a clear message.
     */
    private function ensureProjectIsActive(Project $project): void
    {
        if (! $project->status->isActive()) {
            back()->withErrors([
                'project' => 'El proyecto no admite cambios en su estado actual.',
            ])->throwResponse();
        }
    }

    /**
     * Block writes on a completed sprint, with a clear message.
     */
    private function ensureSprintIsWritable(Sprint $sprint): void
    {
        if ($sprint->status === SprintStatus::Completed) {
            back()->withErrors([
                'sprint' => 'El sprint ya está completado y es de solo lectura.',
            ])->throwResponse();
        }
    }
}
