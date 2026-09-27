@extends('layouts.app')

@section('title', 'Nuevo servicio — BarberShop')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Nuevo servicio</h1>
        <p class="mt-1 text-sm text-neutral-600">Carga los datos del servicio que ofrece la barbería.</p>

        <form method="POST" action="{{ route('services.store') }}" class="card mt-6 space-y-4">
            @csrf

            @include('services._form')
        </form>
    </div>
@endsection
