<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddColppyConfigsToConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insertar configuraciones de Colppy si no existen
        $configs = [
            [
                'name' => 'colppy_clientes_modo',
                'value' => 'local', // Valor por defecto: solo BD local
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($configs as $config) {
            $exists = DB::table('configs')
                ->where('name', $config['name'])
                ->whereNull('deleted_at')
                ->exists();
            
            if (!$exists) {
                DB::table('configs')->insert($config);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('configs')
            ->where('name', 'colppy_clientes_modo')
            ->delete();
    }
}
