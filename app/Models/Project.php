<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $title
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Collection<int, Sprint> $sprints
 * @property-read Collection<int, Task> $tasks
 */
#[Fillable(['owner_id', 'title', 'description', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    /**
     * Get the user who owns the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the collaborators of the project (the owner is not part of this relation).
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    /**
     * Get the sprints of the project (no global order; apply orderBy('start_date') when listing).
     *
     * @return HasMany<Sprint, $this>
     */
    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    /**
     * Get the tasks of the project (no global order; apply orderByDesc('updated_at') when listing).
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Determine whether the given user owns the project.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Determine whether the given user is a member (owner or collaborator).
     */
    public function isMember(User $user): bool
    {
        return $this->isOwnedBy($user) || $this->members()->whereKey($user->id)->exists();
    }

    /**
     * Get the sprint currently considered active for display purposes.
     *
     * Display-only derivation (the domain has no sprint status yet): the
     * sprint with the greatest start_date whose start_date is today or
     * earlier. Overdue sprints remain active until the team closes them.
     *
     * @return HasOne<Sprint, $this>
     */
    public function activeSprint(): HasOne
    {
        return $this->hasOne(Sprint::class)
            ->where('start_date', '<=', now()->toDateString())
            ->orderByDesc('start_date');
    }

    /**
     * Get the completion percentage of the project.
     *
     * Round(done / total * 100); total = 0 results in 0, always within the
     * 0-100 range. Only tasks with status TaskStatus::Done count as done.
     */
    public function progressPercentage(): int
    {
        $total = $this->tasks()->count();
        $done = $this->tasks()->where('status', TaskStatus::Done)->count();

        return $total === 0 ? 0 : (int) round($done / $total * 100);
    }
}
