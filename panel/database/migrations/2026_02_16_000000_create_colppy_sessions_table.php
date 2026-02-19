<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colppy_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('usuario');
            $table->text('clave_sesion');
            $table->string('id_empresa');
            $table->dateTime('se_vence_en')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['usuario', 'id_empresa']);
            $table->index('se_vence_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colppy_sessions');
    }
};
