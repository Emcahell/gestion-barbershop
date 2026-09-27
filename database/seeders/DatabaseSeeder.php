<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the admin account from environment variables.
     *
     * Las credenciales nunca viven en el código: se leen del archivo .env
     * (ignorado por git) mediante las variables ADMIN_*. Si no está
     * definido ADMIN_EMAIL, el seeder no crea nada, de modo que el
     * repositorio público no expone ninguna credencial.
     *
     * Es idempotente: el admin se identifica por su correo, por lo que
     * ejecutarlo de nuevo nunca duplica cuentas ni toca datos existentes.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');

        if (! $email) {
            $this->command?->warn(
                'Sin ADMIN_EMAIL en .env: no se creó ninguna cuenta de administrador.',
            );

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: str()->password(16);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn(
                'ADMIN_PASSWORD no definida: se generó una contraseña aleatoria. Define ADMIN_PASSWORD en .env y promuévela con tinker.',
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'phone' => env('ADMIN_PHONE', ''),
                'password' => $password,
                'role' => UserRole::Admin,
            ],
        );
    }
}
