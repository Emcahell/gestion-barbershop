@extends('layouts.app')

@section('title', 'Ingresar — BarberShop')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Ingresar</h1>
        <p class="mt-1 text-sm text-neutral-600">Accede para ver y reservar tus turnos.</p>

        <form method="POST" action="{{ route('login') }}" class="card mt-6 space-y-4">
            @csrf

            <div>
                <label class="label" for="email">Correo electrónico</label>
                <input
                    class="input"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div>
                <label class="label" for="password">Contraseña</label>
                <x-password-input
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                />
            </div>

            <label class="flex items-center gap-2 text-sm font-bold">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-ink">
                Recordarme
            </label>

            <button type="submit" class="btn btn-primary w-full">Ingresar</button>
        </form>

        <p class="mt-4 text-sm">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="font-black tracking-wide uppercase underline">Regístrate</a>
        </p>
    </div>
@endsection
