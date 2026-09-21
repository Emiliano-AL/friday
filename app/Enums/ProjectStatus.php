<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Completed = 'completed';

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, ProjectStatus>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Active => [self::Archived, self::Completed],
            self::Archived, self::Completed => [self::Active],
        };
    }

    /**
     * Determine whether the status allows content edits.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Archived => 'Archivado',
            self::Completed => 'Completado',
        };
    }
}
