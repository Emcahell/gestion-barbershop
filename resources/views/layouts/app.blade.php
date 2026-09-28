<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BarberShop')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper pb-20 text-ink antialiased md:pb-0">
    <header class="border-b-2 border-ink bg-white">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="text-xl font-black tracking-tighter uppercase">
                Barber<span class="bg-mustard px-1.5">Shop</span>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                @auth
                    @php
                        $user = auth()->user();
                    @endphp

                    {{-- En vista mobile estos accesos por rol viven en la barra inferior fija --}}
                    <div class="hidden flex-wrap items-center gap-2 md:flex">
                        @if ($user->isClient())
                            <a href="{{ route('appointments.create') }}" class="btn btn-primary">Reservar</a>
                            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">Mis citas</a>
                        @endif

                        @if ($user->canManageBarbershop())
                            <a href="{{ route('panel.index') }}" class="btn btn-secondary">Agenda</a>
                            <a href="{{ route('services.index') }}" class="btn btn-secondary">Servicios</a>

                            @if ($user->isBarber())
                                <a href="{{ route('availability.index') }}" class="btn btn-secondary">Disponibilidad</a>
                            @endif
                        @endif

                        @if ($user->isAdmin())
                            <a href="{{ route('barbers.index') }}" class="btn btn-secondary">Barberos</a>
                        @endif
                    </div>

                    <span class="badge hidden bg-steel text-white md:inline-block">{{ $user->role->label() }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secondary">Ingresar</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Registrarme</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
        @if (session('success'))
            <div class="mb-6 border-2 border-ink bg-mustard px-4 py-3 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 border-2 border-ink bg-white px-4 py-3">
                <p class="text-xs font-black tracking-widest uppercase">Revisa estos datos:</p>
                <ul class="mt-1 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @auth
        @php
            $user = auth()->user();

            /**
             * Opciones de la barra inferior según el rol del usuario.
             *
             * @var array<int, array{label: string, route: string, active: string}>
             */
            $mobileNavItems = match (true) {
                $user->isAdmin() => [
                    ['label' => 'Agenda', 'route' => 'panel.index', 'active' => 'panel.*'],
                    ['label' => 'Servicios', 'route' => 'services.index', 'active' => 'services.*'],
                    ['label' => 'Barberos', 'route' => 'barbers.index', 'active' => 'barbers.*'],
                ],
                $user->isBarber() => [
                    ['label' => 'Agenda', 'route' => 'panel.index', 'active' => 'panel.*'],
                    ['label' => 'Servicios', 'route' => 'services.index', 'active' => 'services.*'],
                    ['label' => 'Disponibilidad', 'route' => 'availability.index', 'active' => 'availability.*'],
                ],
                default => [
                    ['label' => 'Reservar', 'route' => 'appointments.create', 'active' => 'appointments.create'],
                    ['label' => 'Mis citas', 'route' => 'appointments.index', 'active' => 'appointments.index'],
                ],
            };
        @endphp

        {{-- Barra inferior fija para vista mobile: componente blanco con botones dentro, como el header --}}
        <nav aria-label="Navegación principal"
             class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-ink bg-white pb-[env(safe-area-inset-bottom)] md:hidden">
            <div class="mx-auto flex max-w-6xl gap-2 px-3 py-3">
                @foreach ($mobileNavItems as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'btn flex-1 justify-center px-2 py-2.5 text-center text-[0.65rem] leading-tight tracking-wider break-words',
                           'bg-mustard text-ink' => request()->routeIs($item['active']),
                           'btn-secondary' => ! request()->routeIs($item['active']),
                       ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endauth
</body>
</html>
