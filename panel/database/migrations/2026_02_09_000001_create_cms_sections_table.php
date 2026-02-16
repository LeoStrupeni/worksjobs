<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsSectionsTable extends Migration
{
    public function up()
    {
        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre mostrado: "Header", "Carrusel de Imágenes"
            $table->string('slug')->unique(); // header, carousel, historia, servicios, banner, instagram, footer, general, flutter_theme
            $table->json('config'); // Configuración completa de la sección en JSON
            $table->integer('order')->default(0); // Orden de visualización
            $table->boolean('is_active')->default(true); // Si la sección está activa
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cms_sections');
    }
}
