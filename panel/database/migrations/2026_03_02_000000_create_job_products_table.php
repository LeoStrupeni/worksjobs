<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('product_id')->nullable(); // Puede ser null si el producto se elimina
            $table->string('idcolppy')->nullable(); // ID del producto en Colppy
            $table->string('codigo'); // Código del producto (guardado para histórico)
            $table->string('descripcion'); // Descripción del producto (guardado para histórico)
            $table->enum('unit_type', ['Unidad', 'Rollo', 'Metros'])->default('Unidad'); // Tipo de unidad
            $table->decimal('quantity', 10, 2)->default(1.00); // Cantidad
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_products');
    }
}
