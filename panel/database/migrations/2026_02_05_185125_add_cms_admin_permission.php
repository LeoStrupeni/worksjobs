<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCmsAdminPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear permisos CRUD para CMS (siguiendo la lógica del sistema)
        $actions = ['create', 'read', 'update', 'delete'];
        
        foreach ($actions as $action) {
            $permission = Permission::firstOrCreate([
                'name' => $action . ' cms',
                'guard_name' => 'web'
            ], [
                'general' => 'cms'
            ]);

            // Asignar permisos a roles admin y sistema
            $adminRole = Role::where('name', 'admin')->first();
            $sistemaRole = Role::where('name', 'sistema')->first();

            if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }

            if ($sistemaRole && !$sistemaRole->hasPermissionTo($permission)) {
                $sistemaRole->givePermissionTo($permission);
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
        // Eliminar todos los permisos CMS
        Permission::where('general', 'cms')->delete();
    }
}
