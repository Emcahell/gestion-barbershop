@extends('layouts.app')

@section('title', 'BarberShop — Reserva tu turno')

@section('content')
    <section class="border-2 border-ink bg-white">
        <div class="border-b-2 border-ink bg-ink px-6 py-12 text-white">
            <p class="text-xs font-black tracking-[0.3em] text-mustard uppercase">Estilo · Precisión · Actitud</p>
            <h1 class="mt-3 text-5xl font-black tracking-tighter uppercase sm:text-7xl">
                Barber<span class="text-steel">Shop</span>
            </h1>
            <p class="mt-4 max-w-xl text-sm text-neutral-300">
                Reserva tu turno en segundos: elige el servicio, tu barbero preferido y el horario
                que mejor te quede. Sin llamadas, sin esperas.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @guest
                    <a href="{{ route('register') }}" class="btn border-mustard bg-mustard text-ink hover:bg-white">
                        Crea tu cuenta
                    </a>
                    <a href="{{ route('login') }}" class="btn border-white bg-transparent text-white hover:bg-white hover:text-ink">
                        Ya tengo cuenta
                    </a>
                @else
                    @if (auth()->user()->canManageBarbershop())
                        <a href="{{ route('panel.index') }}" class="btn border-mustard bg-mustard text-ink hover:bg-white">
                            Ir a la agenda
                        </a>
                    @else
                        <a href="{{ route('appointments.create') }}" class="btn border-mustard bg-mustard text-ink hover:bg-white">
                            Reservar un turno
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        <div class="p-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="section-title mb-0">Servicios</h2>
                <span class="text-xs font-bold tracking-widest text-neutral-500 uppercase">
                    Horario: {{ config('appointments.open') }} a {{ config('appointments.close') }} ·
                    Turnos de {{ config('appointments.slot_minutes') }} min
                </span>
            </div>

            @if ($services->isEmpty())
                <p class="mt-4 text-sm text-neutral-500">No hay servicios publicados todavía.</p>
            @else
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <li class="card">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-sm font-black tracking-wide uppercase">{{ $service->name }}</h3>
                                <span class="badge bg-mustard">
                                    ${{ number_format((float) $service->price, 0, ',', '.') }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-neutral-600">{{ $service->description }}</p>
                            <p class="mt-3 text-xs font-bold tracking-widest text-steel-deep uppercase">
                                {{ $service->duration }} minutos
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
@endsection
