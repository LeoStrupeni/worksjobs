<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFlutterThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_flutter_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre del tema');
            $table->json('config_json')->comment('Configuración JSON del tema (colores, fuentes, etc)');
            $table->boolean('is_active')->default(false)->comment('Tema actualmente activo');
            $table->string('version')->default('1.0.0')->comment('Versión del tema');
            $table->text('description')->nullable()->comment('Descripción del tema');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario que creó/editó el tema');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cms_flutter_themes');
    }
}
