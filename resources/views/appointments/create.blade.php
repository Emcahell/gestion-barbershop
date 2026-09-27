@extends('layouts.app')

@section('title', 'Reservar turno — BarberShop')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Reservar turno</h1>
        <p class="mt-1 text-sm text-neutral-600">
            Elige servicio, barbero y fecha para ver los horarios disponibles.
        </p>

        <form method="GET" action="{{ route('appointments.create') }}" class="card mt-6 grid gap-4 sm:grid-cols-3">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="month" value="{{ $calendar['month'] }}">

            <div>
                <label class="label" for="service_id">1 · Servicio</label>
                <select class="input" id="service_id" name="service_id" onchange="this.form.submit()">
                    <option value="">Elegir…</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected((int) old('service_id', $selectedServiceId) === $service->id)>
                            {{ $service->name }} — ${{ number_format((float) $service->price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="barber_id">2 · Barbero</label>
                <select class="input" id="barber_id" name="barber_id" onchange="this.form.submit()">
                    <option value="">Elegir…</option>
                    @foreach ($barbers as $barber)
                        <option value="{{ $barber->id }}" @selected((int) old('barber_id', $selectedBarberId) === $barber->id)>
                            {{ $barber->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn btn-secondary w-full">Ver disponibilidad</button>
            </div>
        </form>

        <div class="card mt-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="section-title mb-0">3 · Elige la fecha</h2>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('appointments.create', array_merge(array_filter(['service_id' => $selectedServiceId, 'barber_id' => $selectedBarberId], fn ($value) => filled($value)), ['month' => $calendar['previous']])) }}"
                        class="btn btn-secondary"
                    >← Anterior</a>
                    <span class="badge bg-steel px-3 py-1.5 text-white">{{ $calendar['label'] }}</span>
                    <a
                        href="{{ route('appointments.create', array_merge(array_filter(['service_id' => $selectedServiceId, 'barber_id' => $selectedBarberId], fn ($value) => filled($value)), ['month' => $calendar['next']])) }}"
                        class="btn btn-secondary"
                    >Siguiente →</a>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-black tracking-widest text-neutral-500 uppercase">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dayName)
                    <span class="py-1">{{ $dayName }}</span>
                @endforeach
            </div>

            @php($dayLinkParams = array_filter(['service_id' => $selectedServiceId, 'barber_id' => $selectedBarberId], fn ($value) => filled($value)))

            <div class="mt-1 grid grid-cols-7 gap-1">
                @foreach ($calendar['weeks'] as $week)
                    @foreach ($week as $day)
                        @if ($day === null)
                            <span class="aspect-square"></span>
                        @else
                            @php($isPast = $day->lt(today()))
                            @php($isSelected = $selectedDate === $day->toDateString())
                            @php($isBlocked = in_array($day->toDateString(), $blockedDays, true))

                            @if ($isPast || $isBlocked)
                                <span
                                    title="{{ $isPast ? 'Días pasados' : 'Ese barbero no atiende ese día' }}"
                                    class="flex aspect-square items-center justify-center border-2 text-sm font-black
                                        {{ $isPast
                                            ? 'cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-300'
                                            : 'cursor-not-allowed border-red-600 bg-red-600 text-white line-through' }}"
                                >
                                    {{ $day->day }}
                                </span>
                            @else
                                <a
                                    href="{{ route('appointments.create', array_merge($dayLinkParams, ['date' => $day->toDateString(), 'month' => $calendar['month']])) }}"
                                    class="flex aspect-square items-center justify-center border-2 border-ink text-sm font-black transition
                                        {{ $isSelected ? 'bg-ink text-white ring-2 ring-ink ring-offset-2' : 'bg-white hover:bg-mustard' }}"
                                >
                                    {{ $day->day }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-xs font-bold text-neutral-500">
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-red-600 bg-red-600"></span> No disponible con ese barbero</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-neutral-200 bg-neutral-50"></span> Pasados</span>
                <span class="flex items-center gap-1.5"><span class="inline-block size-3 border-2 border-ink ring-2 ring-ink ring-offset-1"></span> Fecha elegida</span>
            </div>

            @if (blank($selectedBarberId))
                <p class="mt-3 text-xs font-bold tracking-widest text-neutral-500 uppercase">
                    Elige un barbero para ver sus días y horas disponibles.
                </p>
            @endif
        </div>

        @if (filled($selectedServiceId) && filled($selectedBarberId))
            <form method="POST" action="{{ route('appointments.store') }}" class="card mt-6">
                @csrf

                <input type="hidden" name="service_id" value="{{ $selectedServiceId }}">
                <input type="hidden" name="barber_id" value="{{ $selectedBarberId }}">
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 class="section-title mb-0">Horarios disponibles</h2>
                    <p class="text-xs font-bold tracking-widest text-neutral-500 uppercase">
                        {{ \Illuminate\Support\Carbon::parse($selectedDate)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    </p>
                </div>

                @if ($dayOff)
                    <p class="mt-4 border-2 border-red-600 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                        {{ $selectedBarberName }} no atiende ese día. Elige otra fecha en el calendario.
                    </p>
                @elseif (empty(array_diff($slots, array_merge($takenTimes, $blockedTimes))))
                    <p class="mt-4 border-2 border-ink bg-paper px-4 py-3 text-sm font-bold">
                        No quedan turnos libres ese día. Prueba con otra fecha u otro barbero.
                    </p>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-5">
                        @foreach ($slots as $slot)
                            @php($taken = in_array($slot, $takenTimes, true))
                            @php($blocked = in_array($slot, $blockedTimes, true))
                            <label
                                title="{{ $blocked ? 'El barbero no atiende a esa hora' : ($taken ? 'Turno ya reservado' : '') }}"
                                class="flex items-center justify-center gap-2 border-2 px-2 py-3 text-center text-sm font-black transition has-[:checked]:bg-ink has-[:checked]:text-white
                                    {{ $taken
                                        ? 'cursor-not-allowed border-neutral-200 bg-neutral-200 text-neutral-400 line-through'
                                        : ($blocked
                                            ? 'cursor-not-allowed border-red-600 bg-red-50 text-red-600 line-through'
                                            : 'cursor-pointer border-ink bg-white hover:bg-mustard') }}"
                            >
                                <input
                                    type="radio"
                                    name="time"
                                    value="{{ $slot }}"
                                    class="size-4 shrink-0 accent-ink"
                                    @checked(old('time') === $slot)
                                    @disabled($taken || $blocked)
                                    @required(! $taken && ! $blocked)
                                >
                                {{ $slot }}
                            </label>
                        @endforeach
                    </div>

                    @if (! empty($blockedTimes))
                        <p class="mt-3 text-xs font-bold tracking-widest text-red-600 uppercase">
                            Las horas en rojo están bloqueadas por el barbero ese día.
                        </p>
                    @endif

                    <button type="submit" class="btn btn-primary mt-5">Confirmar reserva</button>
                @endif
            </form>
        @else
            <p class="card mt-6 text-sm font-bold">
                Selecciona un servicio, un barbero y una fecha para ver los horarios disponibles.
            </p>
        @endif
    </div>
@endsection
