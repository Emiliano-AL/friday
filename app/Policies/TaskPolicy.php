<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view the tasks of the project.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * Determine whether the user can view a task (project member, or its assignee when standalone).
     */
    public function view(User $user, Task $task): bool
    {
        if ($task->project === null) {
            return $task->assignee_id === $user->id;
        }

        return $task->project->isMember($user);
    }

    /**
     * Determine whether the user can create tasks in the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $project->isMember($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can update the task (project member on active projects, or its assignee when standalone).
     */
    public function update(User $user, Task $task): bool
    {
        if ($task->project === null) {
            return $task->assignee_id === $user->id;
        }

        return $task->project->isMember($user) && $task->project->status->isActive();
    }

    /**
     * Determine whether the user can delete the task (project owner on active projects, or its assignee when standalone).
     */
    public function delete(User $user, Task $task): bool
    {
        if ($task->project === null) {
            return $task->assignee_id === $user->id;
        }

        return $task->project->isOwnedBy($user) && $task->project->status->isActive();
    }
}
