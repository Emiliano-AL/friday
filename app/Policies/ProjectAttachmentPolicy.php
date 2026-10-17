<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;

class ProjectAttachmentPolicy
{
    /**
     * Determine whether the user can open or download the attachment (any member).
     */
    public function view(User $user, ProjectAttachment $attachment): bool
    {
        return $attachment->project->isMember($user);
    }

    /**
     * Determine whether the user can add attachments to the project (member of an active project).
     */
    public function create(User $user, Project $project): bool
    {
        return $project->isMember($user) && $project->status->isActive();
    }

    /**
     * Determine whether the user can rename the attachment (author or owner of an active project).
     */
    public function update(User $user, ProjectAttachment $attachment): bool
    {
        return $this->manages($user, $attachment);
    }

    /**
     * Determine whether the user can delete the attachment (author or owner of an active project).
     */
    public function delete(User $user, ProjectAttachment $attachment): bool
    {
        return $this->manages($user, $attachment);
    }

    private function manages(User $user, ProjectAttachment $attachment): bool
    {
        return ($attachment->user_id === $user->id || $attachment->project->isOwnedBy($user))
            && $attachment->project->status->isActive();
    }
}
