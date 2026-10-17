<?php

namespace App\Models;

use App\Enums\SprintStatus;
use Carbon\CarbonImmutable;
use Database\Factories\SprintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $goal
 * @property SprintStatus $status
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Task> $tasks
 * @property-read Project $project
 * @property-read int|null $tasks_count
 * @property-read int|null $done_tasks_count
 */
#[Fillable(['project_id', 'name', 'goal', 'status', 'start_date', 'end_date'])]
class Sprint extends Model
{
    /** @use HasFactory<SprintFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'goal' => 'string',
            'status' => SprintStatus::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Transition the sprint from planned to active.
     *
     * @throws ValidationException When the sprint is not planned.
     */
    public function start(): void
    {
        if ($this->status !== SprintStatus::Planned) {
            throw ValidationException::withMessages([
                'sprint' => 'Solo se puede activar un sprint planificado.',
            ]);
        }

        $this->update(['status' => SprintStatus::Active]);
    }

    /**
     * Transition the sprint from active to completed.
     *
     * @throws ValidationException When the sprint is not active.
     */
    public function complete(): void
    {
        if ($this->status !== SprintStatus::Active) {
            throw ValidationException::withMessages([
                'sprint' => 'Solo se puede completar un sprint activo.',
            ]);
        }

        $this->update(['status' => SprintStatus::Completed]);
    }

    /**
     * Get the tasks bound to this sprint.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the project the sprint belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
