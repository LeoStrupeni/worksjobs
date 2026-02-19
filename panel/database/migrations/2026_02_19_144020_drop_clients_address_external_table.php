<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropClientsAddressExternalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('clients_address_external');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('clients_address_external', function (Blueprint $table) {
            $table->id();
            $table->string('external_client_id')->comment('ID del cliente externo (ej: colppy_123)');
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('cp')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_nro')->nullable();
            $table->string('address_apartament')->nullable();
            $table->text('address_detail')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('external_client_id');
        });
    }
}
