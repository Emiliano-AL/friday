<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Sprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SprintObserver
{
    /**
     * Handle the Sprint "updated" event: record lifecycle transitions after
     * they have persisted (status is forward-only: Planned -> Active -> Completed).
     */
    public function updated(Sprint $sprint): void
    {
        if (! $sprint->wasChanged('status')) {
            return;
        }

        if ($sprint->status === SprintStatus::Completed) {
            $this->returnUnfinishedTasksToBacklog($sprint);
        }

        if (! Auth::check()) {
            return;
        }

        $type = match ($sprint->status) {
            SprintStatus::Active => ActivityType::SprintStarted,
            SprintStatus::Completed => ActivityType::SprintCompleted,
            default => null,
        };

        if ($type === null) {
            return;
        }

        Activity::query()->create([
            'user_id' => Auth::id(),
            'project_id' => $sprint->project_id,
            'task_id' => null,
            'sprint_id' => $sprint->id,
            'type' => $type,
        ]);
    }

    /**
     * Send the sprint's unfinished tasks back to the backlog (US4): they keep
     * their project, priority, points and status, only the sprint link is cut.
     */
    private function returnUnfinishedTasksToBacklog(Sprint $sprint): void
    {
        DB::transaction(fn () => $sprint->tasks()
            ->where('status', '!=', TaskStatus::Done)
            ->update(['sprint_id' => null]));
    }
}
