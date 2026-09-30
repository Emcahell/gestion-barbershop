@extends('layouts.app')

@section('title', 'BarberShop — Reserva tu turno')

{{-- La landing ocupa todo el ancho de la ventana (sin el contenedor gris por defecto). --}}
@section('wide', '1')

@php
    $cta = match (true) {
        auth()->check() && auth()->user()->canManageBarbershop() => [
            'label' => 'Ir a la agenda',
            'href' => route('panel.index'),
        ],
        auth()->check() => [
            'label' => 'Reservar un turno',
            'href' => route('appointments.create'),
        ],
        default => [
            'label' => 'Crea tu cuenta',
            'href' => route('register'),
        ],
    };
@endphp

@section('content')
    {{-- Hero: fondo negro de borde a borde. --}}
    <section class="relative overflow-hidden bg-ink text-white">
        {{-- Futuro fondo con foto del local y degradado a negro:
        <img src="{{ asset('images/local.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/85 to-black"></div>
        --}}
        <div class="mx-auto max-w-6xl px-4 py-16 text-center sm:py-24">
            <p class="text-xs font-black tracking-[0.3em] text-mustard uppercase">Estilo · Precisión · Actitud</p>

            <h1 class="mt-4 text-6xl font-black tracking-tighter uppercase sm:text-8xl">
                Barber<span class="text-steel">Shop</span>
            </h1>

            {{-- Espacio reservado para el logo del local (aún sin imagen). --}}
            <div class="mt-8 flex justify-center">
                <div class="flex h-32 w-64 items-center justify-center border-2 border-dashed border-neutral-700 text-[0.65rem] font-black tracking-widest text-neutral-500 uppercase">
                    Logo de la barbería
                </div>
            </div>

            <p class="mx-auto mt-8 max-w-xl text-sm text-neutral-300 sm:text-base">
                Reserva tu turno en segundos: elige el servicio, tu barbero preferido y el horario
                que mejor te quede. Sin llamadas, sin esperas.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ $cta['href'] }}" class="btn border-mustard bg-mustard text-ink hover:bg-white">
                    {{ $cta['label'] }}
                </a>

                @guest
                    <a href="{{ route('login') }}" class="btn border-white bg-transparent text-white hover:bg-white hover:text-ink">
                        Ya tengo cuenta
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- Servicios --}}
    <section class="border-b-2 border-ink bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight uppercase">Servicios</h2>
                    <p class="mt-1 text-sm text-neutral-600">Precios claros, sin sorpresas al pagar.</p>
                </div>

                <span class="badge bg-mustard px-3 py-1.5">
                    Horario: {{ config('appointments.open') }} a {{ config('appointments.close') }} ·
                    Turnos de {{ config('appointments.slot_minutes') }} min
                </span>
            </div>

            @if ($services->isEmpty())
                <p class="mt-6 text-sm text-neutral-500">No hay servicios publicados todavía.</p>
            @else
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

                <div class="mt-8 flex justify-center">
                    <a href="{{ $cta['href'] }}" class="btn btn-primary">{{ $cta['label'] }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- Ubicación --}}
    <section class="bg-paper">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <h2 class="text-2xl font-black tracking-tight uppercase">Ubicación</h2>
            <p class="mt-1 text-sm text-neutral-600">
                Te esperamos en el local; la reserva se hace en línea.
            </p>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="card">
                    <h3 class="text-sm font-black tracking-wide uppercase">Dónde estamos</h3>

                    {{-- TODO: reemplaza «Caracas, Venezuela» con la dirección exacta del local. --}}
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-black tracking-widest text-steel-deep uppercase">Dirección</dt>
                            <dd class="mt-1 font-bold">Caracas, Venezuela</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black tracking-widest text-steel-deep uppercase">Horario</dt>
                            <dd class="mt-1 font-bold">
                                De {{ config('appointments.open') }} a {{ config('appointments.close') }} ·
                                Turnos de {{ config('appointments.slot_minutes') }} min
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-black tracking-widest text-steel-deep uppercase">Reservas</dt>
                            <dd class="mt-1 font-bold">En línea, las 24 horas · Confirmación inmediata</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <a
                            href="https://www.openstreetmap.org/#map=15/10.4700/-66.9000"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary"
                        >
                            Cómo llegar
                        </a>
                    </div>
                </div>

                {{-- Mapa del local (centrado en Caracas; cambia el bbox/marker por la dirección real). --}}
                <div class="flex h-96 border-2 border-ink bg-white lg:h-auto lg:min-h-[24rem]">
                    <iframe
                        class="h-full w-full flex-1 border-0"
                        loading="lazy"
                        title="Mapa de la ubicación de la barbería"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=-66.93%2C10.44%2C-66.87%2C10.50&layer=mapnik&marker=10.47%2C-66.90"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- Galería --}}
    <section class="border-t-2 border-ink bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight uppercase">Galería</h2>
                    <p class="mt-1 text-sm text-neutral-600">Trabajos de la casa y el ambiente del local.</p>
                </div>

                <span class="badge bg-steel px-3 py-1.5 text-white">Próximamente</span>
            </div>

            {{-- Espacios reservados para las fotos del local (aún sin imágenes). --}}
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach (range(1, 6) as $index)
                    <div class="flex aspect-square items-center justify-center border-2 border-dashed border-neutral-300 bg-paper text-[0.65rem] font-black tracking-widest text-neutral-400 uppercase">
                        Imagen {{ $index }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-ink text-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-xl font-black tracking-tighter uppercase">
                    Barber<span class="bg-mustard px-1.5 text-ink">Shop</span>
                </p>
                <p class="mt-3 text-sm text-neutral-400">
                    Cortes con estilo, precisión y actitud. Reserva en línea y llega a tu hora:
                    sin llamadas y sin esperas.
                </p>
            </div>

            <div>
                <h3 class="text-xs font-black tracking-widest text-mustard uppercase">Horario</h3>
                <p class="mt-3 text-sm text-neutral-300">
                    De {{ config('appointments.open') }} a {{ config('appointments.close') }}<br>
                    Turnos de {{ config('appointments.slot_minutes') }} minutos<br>
                    Reservas en línea, las 24 horas
                </p>
            </div>

            <div>
                <h3 class="text-xs font-black tracking-widest text-mustard uppercase">Ubicación</h3>
                <p class="mt-3 text-sm text-neutral-300">
                    {{-- TODO: dirección exacta del local. --}}
                    Caracas, Venezuela<br>
                    Confirmación inmediata al reservar
                </p>
            </div>
        </div>

        <div class="border-t border-neutral-700">
            <p class="mx-auto max-w-6xl px-4 py-5 text-center text-xs text-neutral-400">
                © {{ now()->year }} BarberShop · Aplicación desarrollada por
                <a
                    href="https://emcahell.dev"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-black text-mustard underline hover:text-white"
                >Emcahell</a>
            </p>
        </div>
    </footer>
@endsection
