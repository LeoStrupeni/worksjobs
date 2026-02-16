<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsSectionVersionsTable extends Migration
{
    public function up()
    {
        Schema::create('cms_section_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('cms_sections')->onDelete('cascade');
            $table->json('config'); // Snapshot del JSON de configuración
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Quién hizo el cambio
            $table->text('change_notes')->nullable(); // Notas opcionales del cambio
            $table->timestamp('created_at'); // Solo created_at, no updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('cms_section_versions');
    }
}
