<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\BarberUnavailability;
use App\Models\Service;
use App\Models\User;
use App\Support\MonthCalendar;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * List the authenticated client's appointments (upcoming and past).
     */
    public function index(Request $request): View
    {
        $appointments = $request->user()
            ->appointments()
            ->with(['barber', 'service'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return view('appointments.index', [
            'upcoming' => $appointments
                ->filter(fn (Appointment $appointment) => $appointment->isUpcoming())
                ->values(),
            'past' => $appointments
                ->reject(fn (Appointment $appointment) => $appointment->isUpcoming())
                ->values(),
        ]);
    }

    /**
     * Show the booking form with the slots available for barber and date.
     */
    public function create(Request $request): View
    {
        $data = $request->validate([
            'service_id' => ['nullable', 'integer'],
            'barber_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'date.after_or_equal' => 'Solo se pueden reservar turnos desde hoy en adelante.',
        ]);

        $serviceId = $data['service_id'] ?? null;
        $barberId = $data['barber_id'] ?? null;
        $date = $data['date'] ?? today()->toDateString();
        $month = MonthCalendar::month($request->input('month'));

        $takenTimes = [];

        if ($barberId && $date) {
            $takenTimes = Appointment::where('barber_id', $barberId)
                ->where('date', $date)
                ->where('status', AppointmentStatus::Scheduled)
                ->pluck('time')
                ->map(fn (string $time) => substr($time, 0, 5))
                ->all();
        }

        $barbers = User::where('role', 'barber')->orderBy('name')->get();

        // Días y horas que el barbero marcó como no disponibles.
        $blockedDays = [];
        $blockedTimes = [];
        $dayOff = false;

        if ($barberId) {
            $blockedDays = BarberUnavailability::dayOffDates((int) $barberId, $month);

            if ($date) {
                $dayOff = BarberUnavailability::isDayOff((int) $barberId, $date);
                $blockedTimes = $dayOff ? [] : BarberUnavailability::blockedTimes((int) $barberId, $date);
            }
        }

        return view('appointments.create', [
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'barbers' => $barbers,
            'slots' => Appointment::slots(),
            'takenTimes' => $takenTimes,
            'blockedTimes' => $blockedTimes,
            'blockedDays' => $blockedDays,
            'dayOff' => $dayOff,
            'calendar' => MonthCalendar::make($month),
            'selectedBarberName' => $barbers->firstWhere('id', (int) $barberId)?->name,
            'selectedServiceId' => $serviceId,
            'selectedBarberId' => $barberId,
            'selectedDate' => $date,
        ]);
    }

    /**
     * Store a new appointment after checking the barber's availability.
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /**
         * Regla de negocio crítica: un barbero no puede tener dos citas
         * en la misma fecha y hora.
         */
        $slotAlreadyTaken = Appointment::where('barber_id', $data['barber_id'])
            ->where('date', $data['date'])
            ->where('time', $data['time'])
            ->where('status', AppointmentStatus::Scheduled)
            ->exists();

        if ($slotAlreadyTaken) {
            return back()
                ->withErrors(['time' => 'Ese horario ya fue reservado por otro cliente. Elige un turno distinto.'])
                ->withInput();
        }

        try {
            $request->user()->appointments()->create($data);
        } catch (UniqueConstraintViolationException) {
            /*
             * Carrera entre dos solicitudes: la validación anterior vio el
             * turno libre, pero otro cliente lo tomó antes de que llegáramos
             * aquí. La base de datos solo dejó pasar a uno (constraint único
             * de slot_key) y a este usuario le mostramos el mismo aviso.
             */
            return back()
                ->withErrors(['time' => 'Ese horario acaba de ocuparse: otro cliente reservó al mismo tiempo. Elige un turno distinto.'])
                ->withInput();
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', '¡Turno reservado con éxito!');
    }

    /**
     * Cancel an appointment (only when there are enough hours left).
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === $request->user()->id, 403);

        $hours = (int) config('appointments.cancellation_hours');

        if (! $appointment->canBeCancelled()) {
            return back()->withErrors([
                'appointment' => "Solo puedes cancelar la cita si faltan más de {$hours} horas para el turno.",
            ]);
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return back()->with('success', 'La cita fue cancelada. El turno volvió a estar disponible.');
    }
}
