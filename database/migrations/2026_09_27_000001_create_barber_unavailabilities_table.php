<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barber_unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained('users')->cascadeOnDelete();

            // Fecha bloqueada (Y-m-d).
            $table->date('date');

            // Hora bloqueada (H:i). NULL = el barbero no atiende todo el día.
            $table->string('time', 5)->nullable();

            $table->timestamps();

            $table->unique(['barber_id', 'date', 'time']);
            $table->index(['barber_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barber_unavailabilities');
    }
};
