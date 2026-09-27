<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed only the admin account.
     *
     * Es el único dato por defecto de la aplicación: el admin crea los
     * barberos desde el panel y los servicios desde el CRUD. El seeder
     * es idempotente, por lo que puede ejecutarse sin borrar datos.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@barbershop.test'],
            [
                'name' => 'Administrador',
                'phone' => '+58 412 4000-1000',
                'password' => 'password',
                'role' => UserRole::Admin,
            ],
        );
    }
}
