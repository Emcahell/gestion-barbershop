@extends('layouts.app')

@section('title', 'Editar servicio — BarberShop')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-3xl font-black tracking-tighter uppercase">Editar servicio</h1>
        <p class="mt-1 text-sm text-neutral-600">Modifica los datos de «{{ $service->name }}».</p>

        <form method="POST" action="{{ route('services.update', $service) }}" class="card mt-6 space-y-4">
            @csrf
            @method('PUT')

            @include('services._form')
        </form>
    </div>
@endsection
