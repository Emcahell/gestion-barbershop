@use('App\Enums\AppointmentStatus')

@extends('layouts.app')

@section('title', 'Agenda — BarberShop')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">Agenda</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-neutral-600">
                @if ($isToday)
                    <span class="badge bg-mustard">Hoy</span>
                @endif
                {{ $formattedDate }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <span class="badge bg-steel px-3 py-1.5 text-white">{{ $appointments->count() }} citas</span>
            <span class="badge bg-mustard px-3 py-1.5">${{ number_format($earnings, 0, ',', '.') }} estimados</span>
        </div>
    </div>

    <form method="GET" action="{{ route('panel.index') }}" class="mt-6 flex flex-wrap items-center gap-2">
        <input
            type="date"
            name="date"
            value="{{ $selectedDate }}"
            class="input w-auto"
            onchange="this.form.submit()"
        >
        <button type="submit" name="date" value="{{ $previousDate }}" class="btn btn-secondary">← Anterior</button>
        <button type="submit" name="date" value="{{ $nextDate }}" class="btn btn-secondary">Siguiente →</button>

        @if (! $isToday)
            <button type="submit" name="date" value="{{ today()->toDateString() }}" class="btn btn-primary">
                Ir a hoy
            </button>
        @endif
    </form>

    <div class="card mt-6 overflow-x-auto !p-0">
        <table class="w-full text-left text-sm">
            <thead class="border-b-2 border-ink bg-paper text-xs font-black tracking-widest uppercase">
                <tr>
                    <th class="px-4 py-3">Hora</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Servicio</th>
                    @if (auth()->user()->isAdmin())
                        <th class="px-4 py-3">Barbero</th>
                    @endif
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($appointments as $appointment)
                    @php($badgeClass = match ($appointment->status) {
                        AppointmentStatus::Scheduled => 'bg-steel text-white',
                        AppointmentStatus::Completed => 'bg-mustard text-ink',
                        default => 'bg-neutral-200 text-neutral-600',
                    })
                    <tr class="border-b border-neutral-200 last:border-b-0">
                        <td class="px-4 py-3 text-base font-black">{{ $appointment->startsAt()->format('H:i') }}</td>
                        <td class="px-4 py-3 font-bold">
                            {{ $appointment->client->name }}
                            <span class="block text-xs font-normal text-neutral-500">{{ $appointment->client->phone }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $appointment->service->name }}
                            <span class="block text-xs text-neutral-500">
                                ${{ number_format((float) $appointment->service->price, 0, ',', '.') }} ·
                                {{ $appointment->service->duration }} min
                            </span>
                        </td>
                        @if (auth()->user()->isAdmin())
                            <td class="px-4 py-3">{{ $appointment->barber->name }}</td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="badge {{ $badgeClass }}">{{ $appointment->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($appointment->status === AppointmentStatus::Scheduled)
                                <form
                                    method="POST"
                                    action="{{ route('panel.appointments.complete', $appointment) }}"
                                    onsubmit="return confirm('¿Marcar esta cita como completada?')"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-secondary">Completada</button>
                                </form>
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            class="px-4 py-8 text-center text-neutral-500"
                            colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}"
                        >
                            No hay citas agendadas para este día.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <section class="mt-10">
        <h2 class="section-title">Próximas citas</h2>
        <p class="-mt-3 mb-4 text-xs font-bold tracking-widest text-neutral-500 uppercase">
            Reservas con fecha posterior al día consultado
        </p>

        <div class="card overflow-x-auto !p-0">
            <table class="w-full text-left text-sm">
                <thead class="border-b-2 border-ink bg-paper text-xs font-black tracking-widest uppercase">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Hora</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Servicio</th>
                        @if (auth()->user()->isAdmin())
                            <th class="px-4 py-3">Barbero</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($upcoming as $appointment)
                        <tr class="border-b border-neutral-200 last:border-b-0">
                            <td class="px-4 py-3 font-black whitespace-nowrap">
                                {{ $appointment->startsAt()->locale('es')->isoFormat('dddd D [de] MMM') }}
                            </td>
                            <td class="px-4 py-3 font-black">{{ $appointment->startsAt()->format('H:i') }}</td>
                            <td class="px-4 py-3 font-bold">
                                {{ $appointment->client->name }}
                                <span class="block text-xs font-normal text-neutral-500">{{ $appointment->client->phone }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $appointment->service->name }}
                                <span class="block text-xs text-neutral-500">
                                    ${{ number_format((float) $appointment->service->price, 0, ',', '.') }} ·
                                    {{ $appointment->service->duration }} min
                                </span>
                            </td>
                            @if (auth()->user()->isAdmin())
                                <td class="px-4 py-3">{{ $appointment->barber->name }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td
                                class="px-4 py-8 text-center text-neutral-500"
                                colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}"
                            >
                                No hay reservas con fecha posterior al día consultado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
