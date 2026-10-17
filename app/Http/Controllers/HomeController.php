<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\ProjectStatus;
use App\Enums\SprintStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the public landing page for guests or the home dashboard for authenticated users.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            return Inertia::render('Landing');
        }

        if (! $this->hasActivity($user)) {
            return Inertia::render('AppHome', ['dashboard' => null]);
        }

        $sprint = $this->activeSprint($this->projectIds($user));

        return Inertia::render('AppHome', [
            'dashboard' => [
                'context' => $this->context($user, $sprint),
                'summary' => $this->summary($user, $sprint),
                'myTasks' => $this->myTasks($user),
                'sprintPanel' => $sprint !== null ? $this->sprintPanel($sprint) : null,
                'projects' => $this->projects($user),
                'weeklyInsight' => $this->weeklyInsight($user),
                'activity' => $this->activity($user),
            ],
        ]);
    }

    /**
     * Determine whether the user participates in a project or has assigned tasks.
     */
    private function hasActivity(User $user): bool
    {
        return $this->projectQuery($user)->exists()
            || Task::query()->where('assignee_id', $user->id)->exists();
    }

    /**
     * Projects the user participates in (owner or collaborator).
     *
     * @return Builder<Project>
     */
    private function projectQuery(User $user): Builder
    {
        return Project::query()
            ->where(fn (Builder $query) => $query->where('owner_id', $user->id)
                ->orWhereHas('members', fn (Builder $members) => $members->whereKey($user->id)));
    }

    /**
     * Ids of the projects the user participates in.
     *
     * @return array<int, int>
     */
    private function projectIds(User $user): array
    {
        return $this->projectQuery($user)->pluck('id')->all();
    }

    /**
     * The most recent active sprint (by start_date) across the user's projects.
     *
     * @param  array<int, int>  $projectIds
     */
    private function activeSprint(array $projectIds): ?Sprint
    {
        if ($projectIds === []) {
            return null;
        }

        return Sprint::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', SprintStatus::Active)
            ->with('project:id,title')
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn (Builder $query) => $query->where('status', TaskStatus::Done),
            ])
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Contextual subtitle payload for the greeting.
     *
     * @return array<string, mixed>
     */
    private function context(User $user, ?Sprint $sprint): array
    {
        return [
            'priorityTasks' => $this->urgentTaskCount($user),
            'sprint' => $sprint !== null ? [
                'name' => $sprint->name,
                'coverage' => $this->coverage($sprint),
            ] : null,
        ];
    }

    /**
     * Summary card counts for the dashboard header.
     *
     * @return array<string, mixed>
     */
    private function summary(User $user, ?Sprint $sprint): array
    {
        return [
            'sprint' => $sprint !== null ? [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'coverage' => $this->coverage($sprint),
                'daysRemaining' => $this->daysRemaining($sprint),
                'project' => ['id' => $sprint->project->id, 'title' => $sprint->project->title],
            ] : null,
            'tasks' => [
                'active' => $this->activeTaskCount($user),
                'urgent' => $this->urgentTaskCount($user),
            ],
            'projects' => [
                'active' => $this->projectQuery($user)
                    ->where('status', ProjectStatus::Active)
                    ->count(),
            ],
        ];
    }

    /**
     * Count of the user active (status != Done) assigned tasks.
     */
    private function activeTaskCount(User $user): int
    {
        return Task::query()
            ->where('assignee_id', $user->id)
            ->where('status', '!=', TaskStatus::Done)
            ->count();
    }

    /**
     * Count of the user active assigned tasks with Urgent or High priority.
     */
    private function urgentTaskCount(User $user): int
    {
        return Task::query()
            ->where('assignee_id', $user->id)
            ->where('status', '!=', TaskStatus::Done)
            ->whereIn('priority', [TaskPriority::Urgent, TaskPriority::High])
            ->count();
    }

    /**
     * The user active assigned tasks, most urgent and recently updated first.
     *
     * @return array<string, mixed>
     */
    private function myTasks(User $user): array
    {
        $query = Task::query()
            ->where('assignee_id', $user->id)
            ->where('status', '!=', TaskStatus::Done)
            ->with('project:id,title')
            ->withCount('comments')
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $total = (clone $query)->count();

        return [
            'total' => $total,
            'items' => $query->limit(6)->get()->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status->value,
                'statusLabel' => $task->status->label(),
                'priority' => $task->priority->value,
                'priorityLabel' => $task->priority->label(),
                'project' => $task->project !== null
                    ? ['id' => $task->project->id, 'title' => $task->project->title]
                    : null,
                'commentsCount' => $task->comments_count,
            ])->values()->all(),
        ];
    }

    /**
     * Panel payload for the active sprint, including the burndown series.
     *
     * @return array<string, mixed>
     */
    private function sprintPanel(Sprint $sprint): array
    {
        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'project' => ['id' => $sprint->project->id, 'title' => $sprint->project->title],
            'total' => $sprint->tasks_count,
            'done' => $sprint->done_tasks_count,
            'coverage' => $this->coverage($sprint),
            'daysRemaining' => $this->daysRemaining($sprint),
            'burndown' => $this->burndown($sprint),
        ];
    }

    /**
     * Percentage of Done tasks of the sprint (0-100).
     */
    private function coverage(Sprint $sprint): int
    {
        return $sprint->tasks_count === 0
            ? 0
            : (int) round($sprint->done_tasks_count / $sprint->tasks_count * 100);
    }

    /**
     * Weekday-only days remaining until the sprint end date, mirroring
     * ProjectSprintController::sprintDetail.
     */
    private function daysRemaining(Sprint $sprint): int
    {
        $today = now()->startOfDay();
        $end = $sprint->end_date->startOfDay();

        $daysRemaining = 0;
        $cursor = $today->copy()->addDay();

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $daysRemaining++;
            }

            $cursor = $cursor->addDay();
        }

        return $daysRemaining;
    }

    /**
     * One burndown point per natural day of the sprint: the ideal line is a
     * straight 0 -> total progression and done is the cumulative count of
     * task_completed activities of the sprint per day.
     *
     * @return array<string, mixed>
     */
    private function burndown(Sprint $sprint): array
    {
        $start = $sprint->start_date->startOfDay();
        $end = $sprint->end_date->startOfDay();
        $totalDays = (int) $start->diffInDays($end) + 1;
        $total = $sprint->tasks_count;

        $completionsByDay = Activity::query()
            ->where('sprint_id', $sprint->id)
            ->where('type', ActivityType::TaskCompleted)
            ->whereBetween('created_at', [$start, $end->copy()->endOfDay()])
            ->get(['id', 'created_at'])
            ->groupBy(fn (Activity $activity) => $activity->created_at->toDateString());

        $done = 0;
        $points = [];

        for ($day = 1; $day <= $totalDays; $day++) {
            $date = $start->copy()->addDays($day - 1);
            $done += $completionsByDay->get($date->toDateString(), collect())->count();

            $points[] = [
                'date' => $date->toDateString(),
                'ideal' => (int) round($day * $total / $totalDays),
                'done' => $done,
            ];
        }

        return [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'total' => $total,
            'points' => $points,
        ];
    }

    /**
     * The user active projects with progress and a status note, most recently
     * updated first.
     *
     * @return array<string, mixed>
     */
    private function projects(User $user): array
    {
        $projects = $this->projectQuery($user)
            ->where('status', ProjectStatus::Active)
            ->with('activeSprint:id,project_id,name,start_date,end_date')
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn (Builder $query) => $query->where('status', TaskStatus::Done),
                'tasks as open_tasks_count' => fn (Builder $query) => $query->where('status', '!=', TaskStatus::Done),
            ])
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        return [
            'items' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'progress' => $project->tasks_count === 0
                    ? 0
                    : (int) round($project->done_tasks_count / $project->tasks_count * 100),
                'note' => $project->activeSprint !== null
                    ? $project->activeSprint->name.' • '.$this->daysRemaining($project->activeSprint).' días restantes'
                    : $project->open_tasks_count.' tareas abiertas',
            ])->values()->all(),
        ];
    }

    /**
     * The latest team events of the user projects, most recent first.
     *
     * @return array<string, mixed>
     */
    private function activity(User $user): array
    {
        $items = Activity::query()
            ->whereIn('project_id', $this->projectIds($user))
            ->with([
                'user:id,name,avatar',
                'task:id,title',
                'project:id,title',
                'sprint:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'typeLabel' => $activity->type->label(),
                'user' => [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'avatar' => $activity->user->avatar,
                ],
                'task' => $activity->task !== null
                    ? ['id' => $activity->task->id, 'title' => $activity->task->title]
                    : null,
                'project' => [
                    'id' => $activity->project->id,
                    'title' => $activity->project->title,
                ],
                'sprint' => $activity->sprint !== null
                    ? ['id' => $activity->sprint->id, 'name' => $activity->sprint->name]
                    : null,
                'createdAt' => $activity->created_at?->toIso8601String(),
            ]);

        return ['items' => $items->values()->all()];
    }

    /**
     * Weekly insight: completions of the current ISO week and the average
     * cycle days of the tasks the user completed this week (null when none).
     *
     * @return array<string, mixed>|null
     */
    private function weeklyInsight(User $user): ?array
    {
        $weekStart = now()->startOfWeek();

        $completed = Activity::query()
            ->where('user_id', $user->id)
            ->where('type', ActivityType::TaskCompleted)
            ->where('created_at', '>=', $weekStart)
            ->count();

        if ($completed === 0) {
            return null;
        }

        $avgCycleDays = Task::query()
            ->where('assignee_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $weekStart)
            ->get(['created_at', 'completed_at'])
            ->avg(fn (Task $task) => $task->created_at->diffInDays($task->completed_at));

        return [
            'completed' => $completed,
            'avgCycleDays' => round($avgCycleDays ?? 0, 1),
        ];
    }
}
