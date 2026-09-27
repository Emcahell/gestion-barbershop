<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Día u hora que un barbero tiene bloqueado para no recibir reservas.
 *
 * - `time = null`  → el barbero no atiende ese día completo.
 * - `time = 'H:i'` → no atiende esa hora en concreto (llega tarde, se va antes,
 *                    pausa, etc.). Los clientes no podrán elegirlos al reservar.
 */
#[Fillable(['barber_id', 'date', 'time'])]
class BarberUnavailability extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    /**
     * Barbero al que pertenece la restricción.
     *
     * @return BelongsTo<User, $this>
     */
    public function barber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    /**
     * ¿El barbero no atiende ese día completo?
     */
    public static function isDayOff(int $barberId, string $date): bool
    {
        return static::query()
            ->where('barber_id', $barberId)
            ->where('date', $date)
            ->whereNull('time')
            ->exists();
    }

    /**
     * Horas (H:i) bloqueadas para ese barbero en esa fecha.
     *
     * @return list<string>
     */
    public static function blockedTimes(int $barberId, string $date): array
    {
        return static::query()
            ->where('barber_id', $barberId)
            ->where('date', $date)
            ->whereNotNull('time')
            ->orderBy('time')
            ->pluck('time')
            ->all();
    }

    /**
     * Fechas (Y-m-d) del mes en las que el barbero no atiende el día completo.
     *
     * @return list<string>
     */
    public static function dayOffDates(int $barberId, string $month): array
    {
        return static::datesInMonth($barberId, $month, whereNull: true);
    }

    /**
     * Fechas (Y-m-d) del mes con al menos una hora bloqueada.
     *
     * @return list<string>
     */
    public static function partialDates(int $barberId, string $month): array
    {
        return static::datesInMonth($barberId, $month, whereNull: false);
    }

    /**
     * Fechas (Y-m-d) del mes que cumplan el filtro de horas.
     *
     * @return list<string>
     */
    private static function datesInMonth(int $barberId, string $month, bool $whereNull): array
    {
        $first = CarbonImmutable::parse($month.'-01');
        $last = $first->endOfMonth()->toDateString();

        $query = static::query()
            ->where('barber_id', $barberId)
            ->whereBetween('date', [$first->toDateString(), $last]);

        if ($whereNull) {
            $query->whereNull('time');
        } else {
            $query->whereNotNull('time');
        }

        return $query
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse((string) $date)->toDateString())
            ->unique()
            ->values()
            ->all();
    }
}
