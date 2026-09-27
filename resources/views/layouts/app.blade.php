<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BarberShop')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink antialiased">
    <header class="border-b-2 border-ink bg-white">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="text-xl font-black tracking-tighter uppercase">
                Barber<span class="bg-mustard px-1.5">Shop</span>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                @auth
                    @php($user = auth()->user())

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

                    <span class="badge bg-steel text-white">{{ $user->role->label() }}</span>

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

    <footer class="border-t-2 border-ink bg-white px-4 py-5 text-center text-xs font-bold tracking-widest text-neutral-500 uppercase">
        BarberShop — Sistema de gestión de citas
    </footer>
</body>
</html>
