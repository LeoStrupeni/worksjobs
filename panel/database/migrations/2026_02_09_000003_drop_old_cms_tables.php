<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropOldCmsTables extends Migration
{
    public function up()
    {
        // Eliminar tablas del CMS anterior
        Schema::dropIfExists('cms_page_versions');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('cms_flutter_themes');
        Schema::dropIfExists('cms_configs');
    }

    public function down()
    {
        // No revertir - las tablas nuevas ya están creadas
        // Si necesitas revertir, restaura desde backup de base de datos
    }
}
