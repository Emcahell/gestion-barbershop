@extends('layouts.app')

@section('title', 'Servicios — BarberShop')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">Servicios</h1>
            <p class="mt-1 text-sm text-neutral-600">Crea, edita o desactiva los servicios de la barbería.</p>
        </div>
        <a href="{{ route('services.create') }}" class="btn btn-primary">Nuevo servicio</a>
    </div>

    <div class="card mt-6 overflow-x-auto !p-0">
        <table class="w-full text-left text-sm">
            <thead class="border-b-2 border-ink bg-paper text-xs font-black tracking-widest uppercase">
                <tr>
                    <th class="px-4 py-3">Servicio</th>
                    <th class="px-4 py-3">Precio</th>
                    <th class="px-4 py-3">Duración</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr class="border-b border-neutral-200 last:border-b-0">
                        <td class="px-4 py-3">
                            <span class="font-bold">{{ $service->name }}</span>
                            <span class="block max-w-md text-xs text-neutral-500">{{ $service->description }}</span>
                        </td>
                        <td class="px-4 py-3 font-black">${{ number_format((float) $service->price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $service->duration }} min</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $service->is_active ? 'bg-mustard' : 'bg-neutral-200 text-neutral-500' }}">
                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('services.edit', $service) }}" class="btn btn-secondary">Editar</a>

                                @if ($service->is_active)
                                    <form
                                        method="POST"
                                        action="{{ route('services.destroy', $service) }}"
                                        onsubmit="return confirm('¿Desactivar este servicio?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary">Desactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-neutral-500" colspan="5">
                            No hay servicios cargados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
