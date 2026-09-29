<?php

namespace App\Enums;

enum SprintStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';

    /**
     * Get the statuses this status may transition to (forward-only).
     *
     * @return array<int, SprintStatus>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Planned => [self::Active],
            self::Active => [self::Completed],
            self::Completed => [],
        };
    }

    /**
     * Determine whether the status may transition to the given status.
     */
    public function canTransitionTo(SprintStatus $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificado',
            self::Active => 'Activo',
            self::Completed => 'Completado',
        };
    }
}
