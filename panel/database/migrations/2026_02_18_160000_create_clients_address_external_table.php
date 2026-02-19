<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsAddressExternalTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla separada para domicilios de clientes externos (ej: Colppy API)
     * NO tiene FK a tabla clients, permite guardar domicilios de clientes
     * que solo existen en APIs externas
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients_address_external', function (Blueprint $table) {
            $table->id();
            // Campo para guardar ID externo (ej: 'colppy_123')
            $table->string('external_client_id', 100);
            // Índice para búsquedas rápidas por cliente externo
            $table->index('external_client_id');
            
            // Misma estructura de campos que clients_address
            $table->string('country',20)->nullable();
            $table->string('state',50)->nullable();
            $table->string('cp',20)->nullable();
            $table->string('city',50)->nullable();
            $table->string('address_street',100)->nullable();
            $table->string('address_nro',20)->nullable();
            $table->string('address_apartament',50)->nullable();
            $table->string('address_detail')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients_address_external');
    }
}
