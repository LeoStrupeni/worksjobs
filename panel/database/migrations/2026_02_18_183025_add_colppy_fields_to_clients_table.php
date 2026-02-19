<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColppyFieldsToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Campo para guardar el ID de Colppy (idCliente)
            $table->string('colppy_id')->nullable()->unique()->after('id');
            
            // Campo para nombre fantasia
            $table->string('nombre_fantasia')->nullable()->after('last_name');
            
            // Campo para fax
            $table->string('fax')->nullable()->after('phone2');
            
            // Índice para búsquedas rápidas por colppy_id
            $table->index('colppy_id');
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
            $table->dropIndex(['colppy_id']);
            $table->dropColumn(['colppy_id', 'nombre_fantasia', 'fax']);
        });
    }
}
