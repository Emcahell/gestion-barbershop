<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';

    case Cancelled = 'cancelled';

    case Completed = 'completed';

    /**
     * Etiqueta en español para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
        };
    }
}
