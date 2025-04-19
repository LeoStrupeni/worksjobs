<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('country',20)->nullable();
            $table->string('state',50)->nullable();
            $table->string('city',50)->nullable();
            $table->string('address_street',100)->nullable();
            $table->string('address_nro',20)->nullable();
            $table->string('address_apartament',50)->nullable();
            $table->string('address_detail')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('clients_address', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients_address');
    }
}
