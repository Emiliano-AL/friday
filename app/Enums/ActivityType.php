<?php

namespace App\Enums;

enum ActivityType: string
{
    case TaskCreated = 'task_created';
    case TaskCompleted = 'task_completed';
    case CommentCreated = 'comment_created';
    case SprintStarted = 'sprint_started';
    case SprintCompleted = 'sprint_completed';

    public function label(): string
    {
        return match ($this) {
            self::TaskCreated => 'creó la tarea',
            self::TaskCompleted => 'completó la tarea',
            self::CommentCreated => 'comentó en',
            self::SprintStarted => 'inició el sprint',
            self::SprintCompleted => 'completó el sprint',
        };
    }
}
