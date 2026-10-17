<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Enums\ProjectStatus;
use App\Enums\SprintStatus;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $title
 * @property string|null $description
 * @property string|null $key
 * @property ProjectColor|null $color
 * @property ProjectIcon|null $icon
 * @property CarbonImmutable|null $target_date
 * @property ProjectStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $owner
 * @property-read Collection<int, Sprint> $sprints
 * @property-read Collection<int, Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read int|null $done_tasks_count
 * @property-read int|null $open_tasks_count
 */
#[Fillable(['owner_id', 'title', 'description', 'key', 'color', 'icon', 'target_date', 'status'])]
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
            'color' => ProjectColor::class,
            'icon' => ProjectIcon::class,
            'target_date' => 'date:Y-m-d',
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
     * Get every invitation sent for the project.
     *
     * @return HasMany<ProjectInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    /**
     * Get the invitations still awaiting an answer, most recently sent first.
     *
     * @return HasMany<ProjectInvitation, $this>
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending()->latest('last_sent_at');
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
     * Get the sprint currently active for the project.
     *
     * Status-based derivation: the sprint whose lifecycle status is active,
     * most recent by start_date when more than one is active.
     *
     * @return HasOne<Sprint, $this>
     */
    public function activeSprint(): HasOne
    {
        return $this->hasOne(Sprint::class)
            ->where('status', SprintStatus::Active)
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

    /**
     * Project choices for the task form: projects the user owns or belongs to,
     * with their people (owner first) and their active or planned sprints.
     *
     * @return SupportCollection<int, array{id: int, title: string, members: SupportCollection<int, array{id: int, name: string, avatar: string|null}>, sprints: SupportCollection<int, array{id: int, name: string}>}>
     */
    public static function taskFormOptionsFor(User $user): SupportCollection
    {
        return self::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))
            ->with([
                'owner:id,name,avatar',
                'members:id,name,avatar',
                'sprints' => fn ($sprints) => $sprints
                    ->whereIn('status', [SprintStatus::Active, SprintStatus::Planned])
                    ->select('id', 'project_id', 'name'),
            ])
            ->orderBy('title')
            ->get(['id', 'title', 'owner_id', 'status'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'members' => $project->members
                    ->prepend($project->owner)
                    ->unique('id')
                    ->map(fn (User $member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatar' => $member->avatar,
                    ])->values(),
                'sprints' => $project->sprints->map(fn (Sprint $sprint) => [
                    'id' => $sprint->id,
                    'name' => $sprint->name,
                ])->values(),
            ])
            ->values();
    }
}
