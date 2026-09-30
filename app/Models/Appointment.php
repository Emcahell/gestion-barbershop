<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'barber_id', 'service_id', 'date', 'time', 'status', 'completed_at', 'price'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Los atributos que se convertirán automáticamente en tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'status' => AppointmentStatus::class,
            'completed_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Al completarse la cita se sella el momento y el precio del servicio.
     *
     * Los reportes suman el ingreso en el instante en que la cita pasa a
     * «Completada»: cambiar después el precio de un servicio no altera el
     * histórico y las citas canceladas nunca suman.
     */
    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            if ($appointment->status !== AppointmentStatus::Completed) {
                return;
            }

            if ($appointment->completed_at === null) {
                $appointment->completed_at = now();
            }

            if ($appointment->price === null) {
                $appointment->price = $appointment->service()->value('price');
            }
        });
    }

    /**
     * Cliente que reservó la cita.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Barbero asignado a la cita.
     *
     * @return BelongsTo<User, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    /**
     * Servicio contratado.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Horarios reservables dentro del horario laboral.
     *
     * Genera una lista fija de horas (09:00, 10:00, ...) desde la apertura
     * hasta la última hora que permite iniciar un turno antes del cierre.
     *
     * @return list<string>
     */
    public static function slots(): array
    {
        $slots = [];
        $slot = CarbonImmutable::parse(config('appointments.open'));
        $close = CarbonImmutable::parse(config('appointments.close'));

        while ($slot->lt($close)) {
            $slots[] = $slot->format('H:i');
            $slot = $slot->addMinutes((int) config('appointments.slot_minutes'));
        }

        return $slots;
    }

    /**
     * Fecha y hora exactas en las que comienza la cita.
     */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->toDateString().' '.$this->time);
    }

    /**
     * ¿La cita sigue pendiente y aún no comenzó?
     */
    public function isUpcoming(): bool
    {
        return $this->status === AppointmentStatus::Scheduled && $this->startsAt()->isFuture();
    }

    /**
     * ¿Puede cancelarse? Debe faltar más de las horas configuradas.
     */
    public function canBeCancelled(): bool
    {
        $deadline = now()->addHours((int) config('appointments.cancellation_hours'));

        return $this->status === AppointmentStatus::Scheduled
            && $this->startsAt()->greaterThan($deadline);
    }
}
