@extends('layouts.app')

@section('title', 'Crear cuenta — BarberShop')

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Crear cuenta</h1>
        <p class="mt-1 text-sm text-neutral-600">Regístrate para reservar turnos y llevar el historial de tus citas.</p>

        <form method="POST" action="{{ route('register') }}" class="card mt-6 space-y-4">
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
                <input
                    class="input"
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                >
            </div>

            <div>
                <label class="label" for="password_confirmation">Confirmar contraseña</label>
                <input
                    class="input"
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary w-full">Registrarme</button>
        </form>

        <p class="mt-4 text-sm">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-black tracking-wide uppercase underline">Ingresa</a>
        </p>
    </div>
@endsection
