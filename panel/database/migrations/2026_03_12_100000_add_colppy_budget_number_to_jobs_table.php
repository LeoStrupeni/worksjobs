<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColppyBudgetNumberToJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Agregar columna para almacenar el número visible de factura de Colppy
            // Ejemplo: "0002-00000123"
            $table->string('colppy_budget_number', 50)->nullable()->after('colppy_budget_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('colppy_budget_number');
        });
    }
}
