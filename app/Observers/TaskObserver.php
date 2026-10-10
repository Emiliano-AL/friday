<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskObserver
{
    /**
     * Handle the Task "created" event: backfill the author when missing,
     * assign the sequential human code, and record creation for project tasks.
     */
    public function created(Task $task): void
    {
        $this->assignAuthorAndCode($task);

        if ($task->project_id === null || ! Auth::check()) {
            return;
        }

        Activity::query()->create([
            'user_id' => Auth::id(),
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'sprint_id' => $task->sprint_id,
            'type' => ActivityType::TaskCreated,
        ]);
    }

    /**
     * Assign the author (assignee, then project owner, when the creator did not
     * set one) and generate the per-author sequential code (FRN-1, FRN-2, ...).
     *
     * The numeric suffix is computed in PHP from existing codes so the query
     * stays portable across PostgreSQL and SQLite. Each attempt runs inside a
     * (possibly nested, savepoint-backed) transaction so a concurrent unique
     * violation can be rolled back and retried with the next number without
     * aborting the surrounding transaction.
     */
    private function assignAuthorAndCode(Task $task): void
    {
        if ($task->author_id === null) {
            $task->author_id = $task->assignee_id ?? $task->project?->owner_id;

            if ($task->author_id !== null) {
                $task->saveQuietly();
            }
        }

        if ($task->code !== null || $task->author_id === null) {
            return;
        }

        $lastException = null;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $next = $this->nextCodeNumber($task);

            try {
                DB::connection()->beginTransaction();

                $task->code = 'FRN-'.$next;
                $task->saveQuietly();

                DB::connection()->commit();

                return;
            } catch (QueryException $exception) {
                DB::connection()->rollBack();

                if (! in_array($exception->getCode(), ['23505', '23000'], true)) {
                    throw $exception;
                }

                $lastException = $exception;
            }
        }

        throw $lastException;
    }

    /**
     * Compute the next sequence number for the task's author codes.
     */
    private function nextCodeNumber(Task $task): int
    {
        $suffixes = Task::query()
            ->where('author_id', $task->author_id)
            ->where('code', 'like', 'FRN-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, 4));

        return ($suffixes->max() ?? 0) + 1;
    }

    /**
     * Handle the Task "updating" event: keep completed_at in sync with the
     * status transition. The attribute is set here so it persists within the
     * same update statement (the original status is still available at this
     * point, unlike in the "updated" event).
     */
    public function updating(Task $task): void
    {
        if (! $task->isDirty('status')) {
            return;
        }

        if ($task->status === TaskStatus::Done) {
            $task->completed_at = now();

            return;
        }

        if ($task->getOriginal('status') === TaskStatus::Done) {
            $task->completed_at = null;
        }
    }

    /**
     * Handle the Task "updated" event: record the completion once the
     * transition has persisted.
     */
    public function updated(Task $task): void
    {
        if (! $task->wasChanged('status') || $task->status !== TaskStatus::Done) {
            return;
        }

        if ($task->project_id === null || ! Auth::check()) {
            return;
        }

        Activity::query()->create([
            'user_id' => Auth::id(),
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'sprint_id' => $task->sprint_id,
            'type' => ActivityType::TaskCompleted,
        ]);
    }
}
