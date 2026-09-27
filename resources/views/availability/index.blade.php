@extends('layouts.app')

@section('title', 'Disponibilidad — BarberShop')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Disponibilidad</h1>
        <p class="mt-1 text-sm text-neutral-600">
            Marca los días y las horas en los que no atenderás: los clientes no podrán elegirlos al reservar.
        </p>

        <div class="card mt-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="section-title mb-0">Calendario</h2>
                <div class="flex items-center gap-2">
                    <a href="{{ route('availability.index', ['month' => $calendar['previous']]) }}" class="btn btn-secondary">← Anterior</a>
                    <span class="badge bg-steel px-3 py-1.5 text-white">{{ $calendar['label'] }}</span>
                    <a href="{{ route('availability.index', ['month' => $calendar['next']]) }}" class="btn btn-secondary">Siguiente →</a>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-black tracking-widest text-neutral-500 uppercase">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dayName)
                    <span class="py-1">{{ $dayName }}</span>
                @endforeach
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                @foreach ($calendar['weeks'] as $week)
                    @foreach ($week as $day)
                        @if ($day === null)
                            <span class="aspect-square"></span>
                        @else
                            @php($isPast = $day->lt(today()))
                            @php($isSelected = $selectedDate === $day->toDateString())
                            @php($isDayOffCell = in_array($day->toDateString(), $dayOffs, true))
                            @php($hasBlockedHours = in_array($day->toDateString(), $partialDays, true))

                            @if ($isPast)
                                <span
                                    class="flex aspect-square cursor-not-allowed items-center justify-center border-2 border-neutral-200 bg-neutral-50 text-sm font-black text-neutral-300"
                                >
                                    {{ $day->day }}
                                </span>
                            @else
                                <a
                                    href="{{ route('availability.index', ['month' => $calendar['month'], 'date' => $day->toDateString()]) }}"
                                    title="{{ $isDayOffCell ? 'Día no disponible' : ($hasBlockedHours ? 'Con horas bloqueadas' : 'Disponible') }}"
                                    class="flex aspect-square items-center justify-center border-2 text-sm font-black transition
                                        {{ $isDayOffCell
                                            ? 'border-red-600 bg-red-600 text-white line-through'
                                            : ($hasBlockedHours
                                                ? 'border-ink bg-mustard text-ink hover:bg-white'
                                                : 'border-ink bg-white hover:bg-paper') }}
                                        {{ $isSelected ? 'ring-2 ring-ink ring-offset-2' : '' }}"
                                >
                                    {{ $day->day }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-xs font-bold text-neutral-500">
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-red-600 bg-red-600"></span> Día no disponible</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-ink bg-mustard"></span> Con horas bloqueadas</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-ink ring-2 ring-ink ring-offset-1"></span> Día seleccionado</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-neutral-200 bg-neutral-50"></span> Pasados</span>
            </div>
        </div>

        @if ($selectedDate === null)
            <p class="card mt-6 text-sm font-bold">
                Selecciona un día del calendario para ver o modificar su disponibilidad.
            </p>
        @else
            @php($formattedDate = \Illuminate\Support\Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'))

            <div class="card mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black tracking-widest text-neutral-500 uppercase">Día seleccionado</p>
                        <h2 class="mt-1 text-lg font-black uppercase">{{ $formattedDate }}</h2>
                    </div>

                    @if ($isDayOff)
                        <span class="badge bg-red-600 text-white">No disponible</span>
                    @elseif (! empty($blockedTimes))
                        <span class="badge bg-mustard">Horas bloqueadas</span>
                    @else
                        <span class="badge bg-steel text-white">Disponible</span>
                    @endif
                </div>

                @if ($existingAppointments->isNotEmpty())
                    <div class="mt-4 border-2 border-ink bg-paper px-4 py-3 text-sm font-bold">
                        Ya tienes citas agendadas ese día ({{ $existingAppointments->count() }}).
                        Marcarlo como no disponible no las cancela: siguen en tu agenda y puedes
                        cancelarlas desde ahí si es necesario.
                    </div>
                @endif

                @if (! $canEdit)
                    <div class="mt-4 border-2 border-ink bg-paper px-4 py-3 text-sm font-bold">
                        Solo puedes modificar la disponibilidad de días de hoy en adelante.
                    </div>
                @else
                    <form method="POST" action="{{ route('availability.day-off') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">

                        @if ($isDayOff)
                            <div class="border-2 border-red-600 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                                Este día está marcado como no disponible completo: los clientes no pueden reservarlo.
                            </div>
                            <button type="submit" class="btn btn-secondary mt-3">
                                Habilitar este día para reservas
                            </button>
                        @else
                            <button
                                type="submit"
                                class="btn btn-secondary border-red-600 text-red-600 hover:bg-red-600 hover:text-white"
                                onclick="return confirm('¿Marcar este día como no disponible? Los clientes no podrán reservarlo.')"
                            >
                                Marcar el día completo como no disponible
                            </button>
                        @endif
                    </form>

                    @unless ($isDayOff)
                        <div class="my-6 border-t-2 border-neutral-200"></div>

                        <form method="POST" action="{{ route('availability.hours') }}">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selectedDate }}">

                            <h3 class="text-sm font-black tracking-wide uppercase">Horas no disponibles</h3>
                            <p class="mt-1 text-sm text-neutral-600">
                                Marca las horas en las que no atenderás ese día. Por ejemplo, si ese día
                                empiezas a las 11:00, marca 09:00 y 10:00 para que el cliente no pueda elegirlas.
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">
                                @foreach ($slots as $slot)
                                    <label
                                        class="flex cursor-pointer items-center justify-center gap-2 border-2 border-ink px-2 py-3 text-center text-sm font-black transition has-[:checked]:border-red-600 has-[:checked]:bg-red-600 has-[:checked]:text-white {{ in_array($slot, $blockedTimes, true) ? 'bg-red-50 text-red-700' : 'bg-white hover:bg-paper' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="times[]"
                                            value="{{ $slot }}"
                                            class="size-4 shrink-0 accent-red-600"
                                            @checked(in_array($slot, $blockedTimes, true))
                                        >
                                        {{ $slot }}
                                    </label>
                                @endforeach
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">Guardar horas no disponibles</button>

                            @if (empty($blockedTimes))
                                <p class="mt-2 text-xs font-bold tracking-widest text-neutral-500 uppercase">
                                    Sin horas bloqueadas: atiendes de {{ config('appointments.open') }} a {{ config('appointments.close') }}.
                                </p>
                            @endif
                        </form>
                    @endunless
                @endif
            </div>
        @endif
    </div>
@endsection
