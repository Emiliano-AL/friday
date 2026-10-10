<?php

namespace App\Observers;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentObserver
{
    /**
     * Handle the Comment "created" event: record the comment for project tasks.
     */
    public function created(Comment $comment): void
    {
        $projectId = $comment->task?->project_id;

        if ($projectId === null || ! Auth::check()) {
            return;
        }

        Activity::query()->create([
            'user_id' => $comment->user_id,
            'project_id' => $projectId,
            'task_id' => $comment->task_id,
            'sprint_id' => null,
            'type' => ActivityType::CommentCreated,
        ]);
    }
}
