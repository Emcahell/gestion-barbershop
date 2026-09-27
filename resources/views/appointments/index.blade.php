@use('App\Enums\AppointmentStatus')

@extends('layouts.app')

@section('title', 'Mis citas — BarberShop')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">Mis citas</h1>
            <p class="mt-1 text-sm text-neutral-600">Historial completo de tus turnos, pendientes y pasados.</p>
        </div>
        <a href="{{ route('appointments.create') }}" class="btn btn-primary">Reservar un turno</a>
    </div>

    <section class="mt-8">
        <h2 class="section-title">Próximas citas</h2>

        @if ($upcoming->isEmpty())
            <p class="card text-sm text-neutral-600">
                No tienes turnos reservados.
                <a class="font-black uppercase underline" href="{{ route('appointments.create') }}">Reserva uno</a>.
            </p>
        @else
            <ul class="space-y-4">
                @foreach ($upcoming as $appointment)
                    @php($badgeClass = match ($appointment->status) {
                        AppointmentStatus::Scheduled => 'bg-steel text-white',
                        AppointmentStatus::Completed => 'bg-mustard text-ink',
                        default => 'bg-neutral-200 text-neutral-600',
                    })
                    <li class="card flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-lg font-black tracking-tight">
                                {{ $appointment->startsAt()->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                · {{ $appointment->startsAt()->format('H:i') }}
                            </p>
                            <p class="text-sm text-neutral-600">
                                {{ $appointment->service->name }} con {{ $appointment->barber->name }}
                                — <span class="font-bold text-ink">${{ number_format((float) $appointment->service->price, 0, ',', '.') }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="badge {{ $badgeClass }}">{{ $appointment->status->label() }}</span>

                            @if ($appointment->canBeCancelled())
                                <form
                                    method="POST"
                                    action="{{ route('appointments.cancel', $appointment) }}"
                                    onsubmit="return confirm('¿Quieres cancelar este turno?')"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-secondary">Cancelar cita</button>
                                </form>
                            @else
                                <p class="text-xs font-bold tracking-widest text-neutral-400 uppercase">
                                    Cancelación no disponible
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-10">
        <h2 class="section-title">Historial</h2>

        @if ($past->isEmpty())
            <p class="card text-sm text-neutral-600">Todavía no tienes citas pasadas.</p>
        @else
            <ul class="space-y-4">
                @foreach ($past as $appointment)
                    @php($badgeClass = match ($appointment->status) {
                        AppointmentStatus::Scheduled => 'bg-steel text-white',
                        AppointmentStatus::Completed => 'bg-mustard text-ink',
                        default => 'bg-neutral-200 text-neutral-600',
                    })
                    <li class="card flex flex-wrap items-center justify-between gap-4 opacity-80">
                        <div>
                            <p class="text-lg font-black tracking-tight">
                                {{ $appointment->startsAt()->locale('es')->isoFormat('dddd D [de] MMMM') }}
                                · {{ $appointment->startsAt()->format('H:i') }}
                            </p>
                            <p class="text-sm text-neutral-600">
                                {{ $appointment->service->name }} con {{ $appointment->barber->name }}
                                — <span class="font-bold text-ink">${{ number_format((float) $appointment->service->price, 0, ',', '.') }}</span>
                            </p>
                        </div>

                        <span class="badge {{ $badgeClass }}">{{ $appointment->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
