<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * List every service offered by the barbershop.
     */
    public function index(): View
    {
        return view('services.index', [
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form to create a service.
     */
    public function create(): View
    {
        return view('services.create', [
            'service' => new Service,
        ]);
    }

    /**
     * Persist a new service.
     */
    public function store(Request $request): RedirectResponse
    {
        Service::create($this->validated($request));

        return redirect()
            ->route('services.index')
            ->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Show the form to edit a service.
     */
    public function edit(Service $service): View
    {
        return view('services.edit', [
            'service' => $service,
        ]);
    }

    /**
     * Update an existing service.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));

        return redirect()
            ->route('services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Deactivate a service (it stops being bookable but keeps its history).
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->update(['is_active' => false]);

        return redirect()
            ->route('services.index')
            ->with('success', 'Servicio desactivado. Ya no se puede reservar.');
    }

    /**
     * Validate the service payload.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'duration' => ['required', 'integer', 'min:5', 'max:720'],
        ], [
            'name.required' => 'El nombre del servicio es obligatorio.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'duration.required' => 'La duración es obligatoria.',
            'duration.integer' => 'La duración debe expresarse en minutos.',
            'duration.min' => 'La duración mínima es de 5 minutos.',
            'duration.max' => 'La duración máxima es de 720 minutos.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
