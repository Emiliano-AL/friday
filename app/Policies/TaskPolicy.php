<?php

namespace App\Policies;

use App\Models\Project;
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
     * Determine whether the user can view a task of the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * Determine whether the user can create tasks in the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $project->isMember($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can update tasks of the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isMember($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can delete tasks of the project (owner only).
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $project->status->isActive();
    }
}
