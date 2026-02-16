<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateApiConfigParameters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insertar los parámetros de configuración de API si no existen
        $configParameters = [
            ['name' => 'url_api_login', 'value' => ''],
            ['name' => 'user_dev_api', 'value' => ''],
            ['name' => 'pass_dev_api', 'value' => ''],
            ['name' => 'user_api', 'value' => ''],
            ['name' => 'pass_api', 'value' => ''],
            ['name' => 'id_empresa_api', 'value' => ''],
            ['name' => 'google_api_key', 'value' => '']
        ];

        foreach ($configParameters as $param) {
            DB::table('configs')->updateOrInsert(
                ['name' => $param['name']],
                ['value' => $param['value']]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar los parámetros de API
        DB::table('configs')->whereIn('name', [
            'url_api_login',
            'user_dev_api',
            'pass_dev_api',
            'user_api',
            'pass_api',
            'id_empresa_api',
            'google_api_key'
        ])->delete();
    }
}
