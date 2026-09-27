<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Show the agenda for a given date (today by default) with day navigation.
     *
     * Las citas reservadas por los clientes aparecen aquí en su fecha,
     * de modo que toda la información se sincroniza entre perfiles.
     */
    public function index(Request $request): View
    {
        $date = $this->resolveDate($request);

        $query = Appointment::where('date', $date)
            ->with(['client', 'service', 'barber'])
            ->orderBy('time');

        // Un barbero solo ve su propia agenda; el admin ve la agenda completa.
        if ($request->user()->isBarber()) {
            $query->where('barber_id', $request->user()->id);
        }

        $appointments = $query->get();

        // Próximas citas más allá del día consultado: cualquier turno que
        // un cliente reserve con anticipación se hace visible al instante.
        $upcomingQuery = Appointment::with(['client', 'service', 'barber'])
            ->where('status', AppointmentStatus::Scheduled)
            ->where('date', '>', $date)
            ->orderBy('date')
            ->orderBy('time');

        if ($request->user()->isBarber()) {
            $upcomingQuery->where('barber_id', $request->user()->id);
        }

        return view('panel.index', [
            'appointments' => $appointments,
            'upcoming' => $upcomingQuery->limit(10)->get(),
            'selectedDate' => $date,
            'previousDate' => Carbon::parse($date)->subDay()->toDateString(),
            'nextDate' => Carbon::parse($date)->addDay()->toDateString(),
            'isToday' => $date === today()->toDateString(),
            'formattedDate' => Carbon::parse($date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'earnings' => $appointments
                ->filter(fn (Appointment $appointment) => $appointment->status !== AppointmentStatus::Cancelled)
                ->sum(fn (Appointment $appointment) => (float) $appointment->service->price),
        ]);
    }

    /**
     * Mark an appointment as completed so it shows up in the client's history.
     */
    public function complete(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $appointment->barber_id === $user->id, 403);

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            return back()->withErrors([
                'appointment' => 'Solo se pueden completar citas con estado «Agendada».',
            ]);
        }

        $appointment->update(['status' => AppointmentStatus::Completed]);

        return back()->with('success', 'Cita marcada como completada. Ya aparece en el historial del cliente.');
    }

    /**
     * Resolve the requested agenda date, defaulting to today.
     */
    private function resolveDate(Request $request): string
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ], [
            'date.date' => 'La fecha indicada no es válida.',
        ]);

        return isset($data['date'])
            ? Carbon::parse($data['date'])->toDateString()
            : today()->toDateString();
    }
}
