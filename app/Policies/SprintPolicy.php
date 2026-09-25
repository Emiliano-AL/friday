<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class SprintPolicy
{
    /**
     * Determine whether the user can view the sprints of the project.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * Determine whether the user can view a sprint of the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * Determine whether the user can create sprints in the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can update sprints of the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can delete sprints of the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $project->status->isActive();
    }
}
