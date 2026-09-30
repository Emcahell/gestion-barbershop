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
            // Momento exacto en el que la cita pasó a «Completada».
            $table->dateTime('completed_at')->nullable()->after('status');

            // Precio congelado al completar: si luego cambia el precio del
            // servicio, el histórico de ingresos no se altera.
            $table->decimal('price', 10, 2)->nullable()->after('completed_at');
        });

        // Relleno de citas ya completadas (portable entre SQLite y MySQL).
        foreach (DB::table('appointments')->where('status', 'completed')->get() as $appointment) {
            DB::table('appointments')->where('id', $appointment->id)->update([
                'completed_at' => $appointment->updated_at,
                'price' => DB::table('services')->where('id', $appointment->service_id)->value('price'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'price']);
        });
    }
};
