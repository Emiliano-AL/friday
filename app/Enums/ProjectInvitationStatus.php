<?php

namespace App\Enums;

enum ProjectInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Accepted => 'Aceptada',
            self::Declined => 'Rechazada',
            self::Cancelled => 'Cancelada',
        };
    }
}
