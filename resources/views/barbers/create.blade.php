@extends('layouts.app')

@section('title', 'Nuevo barbero — BarberShop')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Nuevo barbero</h1>
        <p class="mt-1 text-sm text-neutral-600">
            La cuenta podrá iniciar sesión para ver su agenda diaria.
        </p>

        <form method="POST" action="{{ route('barbers.store') }}" class="card mt-6 space-y-4">
            @csrf

            <div>
                <label class="label" for="name">Nombre</label>
                <input
                    class="input"
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                >
            </div>

            <div>
                <label class="label" for="email">Correo electrónico</label>
                <input
                    class="input"
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                >
            </div>

            <div>
                <label class="label" for="phone">Teléfono</label>
                <input
                    class="input"
                    id="phone"
                    type="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    placeholder="+58 412 123-4567"
                    required
                    autocomplete="tel"
                >
            </div>

            <div>
                <label class="label" for="password">Contraseña</label>
                <x-password-input
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    required
                />
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirmar contraseña</label>
                <x-password-input
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                />
            </div>

            <button type="submit" class="btn btn-primary w-full">Crear cuenta de barbero</button>
        </form>

        <p class="mt-4 text-sm">
            <a href="{{ route('barbers.index') }}" class="font-black tracking-wide uppercase underline">
                Volver a la lista
            </a>
        </p>
    </div>
@endsection
