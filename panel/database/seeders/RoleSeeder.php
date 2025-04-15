<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_admin = Role::create(['name' => 'admin']);
        $role_technical = Role::create(['name' => 'tecnico']);
        $role_system = Role::create(['name' => 'sistema']);

        $permission_create_user = Permission::create(['name'=>'create users']);
        $permission_read_user = Permission::create(['name'=>'read users']);
        $permission_update_user = Permission::create(['name'=>'update users']);
        $permission_delete_user = Permission::create(['name'=>'delete users']);

        $permission_create_role = Permission::create(['name'=>'create roles']);
        $permission_read_role = Permission::create(['name'=>'read roles']);
        $permission_update_role = Permission::create(['name'=>'update roles']);
        $permission_delete_role = Permission::create(['name'=>'delete roles']);

        $permission_create_client = Permission::create(['name'=>'create clients']);
        $permission_read_client = Permission::create(['name'=>'read clients']);
        $permission_update_client = Permission::create(['name'=>'update clients']);
        $permission_delete_client = Permission::create(['name'=>'delete clients']);

        $permission_create_job = Permission::create(['name'=>'create jobs']);
        $permission_read_job = Permission::create(['name'=>'read jobs']);
        $permission_update_job = Permission::create(['name'=>'update jobs']);
        $permission_delete_job = Permission::create(['name'=>'delete jobs']);

        $permission_create_permission = Permission::create(['name'=>'create permissions']);
        $permission_read_permission = Permission::create(['name'=>'read permissions']);
        $permission_update_permission = Permission::create(['name'=>'update permissions']);
        $permission_delete_permission = Permission::create(['name'=>'delete permissions']);

        $permissions_admin = [
            $permission_create_user,
            $permission_read_user,
            $permission_update_user,
            $permission_delete_user,
            $permission_create_role,
            $permission_read_role,
            $permission_update_role,
            $permission_delete_role,
            $permission_create_client,
            $permission_read_client,
            $permission_update_client,
            $permission_delete_client,
            $permission_create_job,
            $permission_read_job,
            $permission_update_job,
            $permission_delete_job,
            $permission_create_permission,
            $permission_read_permission,
            $permission_update_permission,
            $permission_delete_permission
        ];

        $permissions_technical = [
            $permission_read_client,
            $permission_update_client,
            $permission_create_job,
            $permission_read_job,
            $permission_update_job,
        ];

        $role_admin->syncPermissions($permissions_admin);
        $role_system->syncPermissions($permissions_admin);
        $role_technical->syncPermissions($permissions_technical);

        // $role_admin->givePermissionTo($permission_create_client);
    } 
}
