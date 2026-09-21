<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the project (any member).
     */
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * Determine whether the user can update the project (owner of an active project).
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can delete the project (owner).
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Determine whether the user can change the project status (owner).
     */
    public function transition(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Determine whether the user can manage project collaborators (owner).
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }
}
