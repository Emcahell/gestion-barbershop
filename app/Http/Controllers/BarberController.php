<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarberController extends Controller
{
    /**
     * List the barbershop's barbers.
     */
    public function index(): View
    {
        return view('barbers.index', [
            'barbers' => User::where('role', 'barber')->orderBy('name')->get(),
            'todayCounts' => Appointment::where('date', today()->toDateString())
                ->where('status', AppointmentStatus::Scheduled)
                ->groupBy('barber_id')
                ->selectRaw('barber_id, COUNT(*) as total')
                ->pluck('total', 'barber_id'),
        ]);
    }

    /**
     * Show the form to create a barber account.
     */
    public function create(): View
    {
        return view('barbers.create');
    }

    /**
     * Create a new barber account (only the admin can do this).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
            'phone.required' => 'El teléfono es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ]);

        User::create([
            ...$data,
            'role' => UserRole::Barber,
        ]);

        return redirect()
            ->route('barbers.index')
            ->with('success', 'Cuenta de barbero creada. Ya puede iniciar sesión con esa contraseña.');
    }
}
