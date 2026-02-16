<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Identificador único de la página (ej: home_hero, about_us)');
            $table->string('title')->comment('Título descriptivo de la sección');
            $table->longText('content')->nullable()->comment('Contenido HTML publicado');
            $table->longText('draft_content')->nullable()->comment('Contenido borrador para preview');
            $table->boolean('is_published')->default(false)->comment('Si el contenido está publicado');
            $table->timestamp('published_at')->nullable()->comment('Fecha de última publicación');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario que hizo la última edición');
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
        Schema::dropIfExists('cms_pages');
    }
}
