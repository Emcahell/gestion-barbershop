<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Rejilla de calendario compartida por la pantalla de disponibilidad del
 * barbero y por el calendario de reserva del cliente (semana en lunes,
 * etiquetas en español).
 */
final class MonthCalendar
{
    /**
     * Normaliza el parámetro de mes (Y-m); cualquier valor inválido usa el mes actual.
     */
    public static function month(?string $input): string
    {
        if (is_string($input) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $input) === 1) {
            return $input;
        }

        return today()->format('Y-m');
    }

    /**
     * Construye la rejilla del mes indicado.
     *
     * @return array{
     *     month: string,
     *     label: string,
     *     previous: string,
     *     next: string,
     *     weeks: list<list<CarbonImmutable|null>>
     * }
     */
    public static function make(string $month): array
    {
        $first = CarbonImmutable::parse($month.'-01');
        $lastDay = $first->endOfMonth();

        $weeks = [];
        $week = [];
        $cursor = $first->startOfWeek(CarbonImmutable::MONDAY);

        while ($cursor->lte($lastDay)) {
            // Fuera del mes seleccionado se deja la celda vacía.
            $week[] = $cursor->month === $first->month ? $cursor : null;

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $cursor = $cursor->addDay();
        }

        // Completa la última fila para alinear la cuadrícula.
        while ($week !== []) {
            $week[] = null;

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return [
            'month' => $month,
            'label' => $first->locale('es')->isoFormat('MMMM [de] YYYY'),
            'previous' => $first->subMonthNoOverflow()->format('Y-m'),
            'next' => $first->addMonthNoOverflow()->format('Y-m'),
            'weeks' => $weeks,
        ];
    }
}
