<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $sprint_id
 * @property int|null $assignee_id
 * @property int|null $author_id
 * @property string|null $code
 * @property int|null $points
 * @property string $title
 * @property string|null $description
 * @property TaskType $type
 * @property TaskPriority $priority
 * @property TaskStatus $status
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Sprint|null $sprint
 * @property-read User|null $assignee
 * @property-read User|null $author
 * @property-read Collection<int, Comment> $comments
 */
#[Fillable(['project_id', 'sprint_id', 'assignee_id', 'author_id', 'code', 'points', 'title', 'description', 'type', 'priority', 'status'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'points' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the project the task belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the sprint the task is linked to (null for isolated tasks).
     *
     * @return BelongsTo<Sprint, $this>
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * Get the user assigned to the task (null when unassigned).
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the user who authored (registered) the task.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope the query to the tasks a user can see: standalone tasks they
     * authored or are assigned to, plus tasks of projects they own or belong to.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($sub) => $sub->where('author_id', $user->id)
            ->orWhere('assignee_id', $user->id)
            ->orWhereHas('project', fn ($project) => $project->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($members) => $members->whereKey($user->id))));
    }

    /**
     * Scope the query to backlog items: tasks not assigned to any sprint that
     * are not done yet.
     *
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeBacklog(Builder $query): Builder
    {
        return $query->whereNull('sprint_id')
            ->where('status', '!=', TaskStatus::Done);
    }

    /**
     * Get the comments of the task (no global order; apply chronological order when listing).
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
