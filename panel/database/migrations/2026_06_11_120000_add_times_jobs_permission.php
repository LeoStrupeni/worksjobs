<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddTimesJobsPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::firstOrCreate([
            'name' => 'times jobs',
            'guard_name' => 'web',
        ], [
            'general' => 'jobs',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $systemRole = Role::where('name', 'sistema')->first();

        if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        if ($systemRole && !$systemRole->hasPermissionTo($permission)) {
            $systemRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'times jobs')->delete();
    }
}
