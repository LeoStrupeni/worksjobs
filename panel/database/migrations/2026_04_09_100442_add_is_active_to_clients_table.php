<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Campo is_active para marcar clientes activos/inactivos
            // Sincronizado desde Colppy (campo "Activo": "1" o "0")
            // Default 1 (true) para clientes creados localmente
            $table->boolean('is_active')->default(1)->after('is_from_colppy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
