<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class AddIsSystemRoleToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'is_system_role')) {
                $table->boolean('is_system_role')->default(0)->after('name');
            }
        });

        // Actualizar roles existentes que son de sistema
        $systemRoles = ['sistema', 'admin'];
        Role::whereIn('name', $systemRoles)->update(['is_system_role' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system_role');
        });
    }
}
