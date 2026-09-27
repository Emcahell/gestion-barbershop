<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Rutas públicas de invitado (guest)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Rutas del cliente autenticado
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Reserva y citas: solo cliente y barbero (el admin no reserva)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:client,barber')->group(function () {
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
            ->name('appointments.cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Panel del barbero / administrador (role: barber o admin)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:barber,admin')->group(function () {
        Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');

        Route::patch('/panel/appointments/{appointment}/complete', [PanelController::class, 'complete'])
            ->name('panel.appointments.complete');

        Route::resource('/services', ServiceController::class)->except('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Solo el administrador gestiona las cuentas de los barberos
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {
        Route::resource('/barbers', BarberController::class)->only(['index', 'create', 'store']);
    });
});
