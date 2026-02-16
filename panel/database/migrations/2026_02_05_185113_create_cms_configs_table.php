<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clave de configuración (ej: site_title, primary_color)');
            $table->text('value')->nullable()->comment('Valor de la configuración');
            $table->enum('type', ['text', 'color', 'number', 'json', 'boolean', 'image'])->default('text')->comment('Tipo de dato');
            $table->string('group')->default('general')->comment('Agrupación (general, colors, seo, etc)');
            $table->string('description')->nullable()->comment('Descripción de la configuración');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cms_configs');
    }
}
