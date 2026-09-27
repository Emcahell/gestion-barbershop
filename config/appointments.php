<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horario de atención
    |--------------------------------------------------------------------------
    |
    | Hora de apertura y cierre de la barbería. La última hora reservable
    | es la anterior al cierre, para que la cita no se extienda fuera
    | del horario laboral.
    |
    */

    'open' => env('APPOINTMENTS_OPEN', '09:00'),
    'close' => env('APPOINTMENTS_CLOSE', '19:00'),

    /*
    |--------------------------------------------------------------------------
    | Duración del turno
    |--------------------------------------------------------------------------
    |
    | Tamaño fijo de cada intervalo de reserva (en minutos).
    |
    */

    'slot_minutes' => (int) env('APPOINTMENTS_SLOT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Cancelación
    |--------------------------------------------------------------------------
    |
    | Horas mínimas de anticipación para que un cliente pueda cancelar
    | una cita sin penalidad.
    |
    */

    'cancellation_hours' => (int) env('APPOINTMENTS_CANCELLATION_HOURS', 2),

];
