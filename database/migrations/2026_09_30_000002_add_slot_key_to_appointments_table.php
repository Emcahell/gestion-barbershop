<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Clave única del turno. Solo existe mientras la cita está
            // «Agendada»; es NULL en citas canceladas o completadas, de modo
            // que esas filas nunca bloquean volver a usar el turno.
            $table->string('slot_key', 64)->nullable()->after('time');
        });

        // Relleno de las citas agendadas existentes.
        foreach (DB::table('appointments')->where('status', 'scheduled')->get() as $appointment) {
            DB::table('appointments')->where('id', $appointment->id)->update([
                'slot_key' => sprintf('%d|%s|%s', $appointment->barber_id, $appointment->date, $appointment->time),
            ]);
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Aquí está la garantía real: si dos solicitudes intentan insertar
            // el mismo turno a la vez, la segunda falla en la base de datos.
            $table->unique('slot_key', 'appointments_slot_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_slot_key_unique');
            $table->dropColumn('slot_key');
        });
    }
};
