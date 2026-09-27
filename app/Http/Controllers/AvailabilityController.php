<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BarberUnavailability;
use App\Support\MonthCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    /**
     * Calendario de días y horas no disponibles del barbero autenticado.
     *
     * El barbero solo gestiona su propia disponibilidad: el admin crea y
     * administra las cuentas, pero no la agenda de cada barbero.
     */
    public function index(Request $request): View
    {
        $barberId = (int) $request->user()->id;
        $month = MonthCalendar::month($request->input('month'));
        $selectedDate = $this->resolveDate($request);

        return view('availability.index', [
            'calendar' => MonthCalendar::make($month),
            'dayOffs' => BarberUnavailability::dayOffDates($barberId, $month),
            'partialDays' => BarberUnavailability::partialDates($barberId, $month),
            'selectedDate' => $selectedDate,
            'isDayOff' => $selectedDate !== null && BarberUnavailability::isDayOff($barberId, $selectedDate),
            'blockedTimes' => $selectedDate !== null ? BarberUnavailability::blockedTimes($barberId, $selectedDate) : [],
            'canEdit' => $selectedDate !== null && ! Carbon::parse($selectedDate)->lt(today()),
            'slots' => Appointment::slots(),
            'existingAppointments' => $selectedDate === null
                ? collect()
                : $request->user()->barberAppointments()
                    ->where('date', $selectedDate)
                    ->where('status', AppointmentStatus::Scheduled)
                    ->with(['client', 'service'])
                    ->orderBy('time')
                    ->get(),
        ]);
    }

    /**
     * Marca o desmarca un día completo como no disponible.
     */
    public function toggleDayOff(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'date.required' => 'Debes indicar el día que quieres bloquear.',
            'date.after_or_equal' => 'Solo puedes bloquear días desde hoy en adelante.',
        ]);

        $barberId = (int) $request->user()->id;
        $date = Carbon::parse($data['date'])->toDateString();

        $existing = BarberUnavailability::query()
            ->where('barber_id', $barberId)
            ->where('date', $date)
            ->whereNull('time')
            ->first();

        $formatted = Carbon::parse($date)->locale('es')->isoFormat('dddd D [de] MMMM');

        if ($existing !== null) {
            $existing->delete();

            return back()->with('success', "El día {$formatted} vuelve a estar disponible para reservas.");
        }

        BarberUnavailability::create(['barber_id' => $barberId, 'date' => $date, 'time' => null]);

        return back()->with('success', "Día bloqueado: los clientes ya no podrán reservar el {$formatted}.");
    }

    /**
     * Guarda las horas no disponibles de un día (por ejemplo, si ese día
     * el barbero empieza a las 11:00 bloquea 09:00 y 10:00).
     */
    public function saveHours(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'times' => ['nullable', 'array'],
            'times.*' => ['string', Rule::in(Appointment::slots())],
        ], [
            'date.required' => 'Debes indicar el día que quieres modificar.',
            'date.after_or_equal' => 'Solo puedes modificar días desde hoy en adelante.',
            'times.array' => 'La lista de horas indicada no es válida.',
            'times.*.in' => 'Solo puedes bloquear horas dentro del horario de atención de la barbería.',
        ]);

        $barberId = (int) $request->user()->id;
        $date = Carbon::parse($data['date'])->toDateString();
        $times = array_values(array_unique($data['times'] ?? []));

        // El nuevo conjunto sustituye al anterior.
        BarberUnavailability::query()
            ->where('barber_id', $barberId)
            ->where('date', $date)
            ->whereNotNull('time')
            ->delete();

        foreach ($times as $time) {
            BarberUnavailability::create(['barber_id' => $barberId, 'date' => $date, 'time' => $time]);
        }

        $formatted = Carbon::parse($date)->locale('es')->isoFormat('dddd D [de] MMMM');

        if ($times === []) {
            return back()->with('success', "Horario actualizado: el {$formatted} no tiene horas bloqueadas.");
        }

        return back()->with('success', 'Horario actualizado: los clientes no podrán reservar esas horas.');
    }

    /**
     * Fecha seleccionada en el calendario (opcional).
     */
    private function resolveDate(Request $request): ?string
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ], [
            'date.date' => 'La fecha indicada no es válida.',
        ]);

        return isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : null;
    }
}
