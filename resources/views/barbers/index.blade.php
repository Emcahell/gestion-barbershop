@extends('layouts.app')

@section('title', 'Barberos — BarberShop')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">Barberos</h1>
            <p class="mt-1 text-sm text-neutral-600">Cuentas con acceso al panel y a su agenda diaria.</p>
        </div>
        <a href="{{ route('barbers.create') }}" class="btn btn-primary">Nuevo barbero</a>
    </div>

    <div class="card mt-6 overflow-x-auto !p-0">
        <table class="w-full text-left text-sm">
            <thead class="border-b-2 border-ink bg-paper text-xs font-black tracking-widest uppercase">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3">Teléfono</th>
                    <th class="px-4 py-3 text-right">Citas hoy</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($barbers as $barber)
                    <tr class="border-b border-neutral-200 last:border-b-0">
                        <td class="px-4 py-3 font-bold">{{ $barber->name }}</td>
                        <td class="px-4 py-3">{{ $barber->email }}</td>
                        <td class="px-4 py-3">{{ $barber->phone }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="badge bg-steel text-white">
                                {{ $todayCounts[$barber->id] ?? 0 }} citas
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-neutral-500" colspan="4">
                            Todavía no hay barberos. Crea el primero con «Nuevo barbero».
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
