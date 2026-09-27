<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';

    case Barber = 'barber';

    case Admin = 'admin';

    /**
     * Etiqueta en español para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Client => 'Cliente',
            self::Barber => 'Barbero',
            self::Admin => 'Administrador',
        };
    }
}
