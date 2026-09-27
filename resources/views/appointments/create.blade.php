@extends('layouts.app')

@section('title', 'Reservar turno — BarberShop')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Reservar turno</h1>
        <p class="mt-1 text-sm text-neutral-600">
            Elige servicio, barbero y fecha para ver los horarios disponibles.
        </p>

        <form method="GET" action="{{ route('appointments.create') }}" class="card mt-6 grid gap-4 sm:grid-cols-3">
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

            <div>
                <label class="label" for="date">3 · Fecha</label>
                <input
                    class="input"
                    id="date"
                    type="date"
                    name="date"
                    min="{{ today()->toDateString() }}"
                    value="{{ old('date', $selectedDate) }}"
                    onchange="this.form.submit()"
                >
            </div>

            <div class="sm:col-span-3">
                <button type="submit" class="btn btn-secondary">Ver horarios disponibles</button>
            </div>
        </form>

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

                @if (empty(array_diff($slots, $takenTimes)))
                    <p class="mt-4 border-2 border-ink bg-paper px-4 py-3 text-sm font-bold">
                        No quedan turnos libres ese día. Prueba con otra fecha u otro barbero.
                    </p>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-5">
                        @foreach ($slots as $slot)
                            @php($taken = in_array($slot, $takenTimes, true))
                            <label
                                class="flex cursor-pointer items-center justify-center gap-2 border-2 border-ink px-2 py-3 text-center text-sm font-black transition has-[:checked]:bg-ink has-[:checked]:text-white {{ $taken ? 'cursor-not-allowed bg-neutral-200 text-neutral-400 line-through' : 'bg-white hover:bg-mustard' }}"
                            >
                                <input
                                    type="radio"
                                    name="time"
                                    value="{{ $slot }}"
                                    class="size-4 shrink-0 accent-ink"
                                    @checked(old('time') === $slot)
                                    @disabled($taken)
                                    @required(! $taken)
                                >
                                {{ $slot }}
                            </label>
                        @endforeach
                    </div>

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
