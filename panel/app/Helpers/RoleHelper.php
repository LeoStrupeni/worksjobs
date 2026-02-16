<?php

use Spatie\Permission\Models\Role;

if (!function_exists('get_special_role_ids')) {
    /**
     * Obtiene los IDs de los roles especiales del sistema.
     * Roles especiales son aquellos que tienen is_system_role = 1
     *
     * @return array
     */
    function get_special_role_ids()
    {
        static $roleIds = null;

        if ($roleIds === null) {
            $roleIds = Role::where('is_system_role', 1)->pluck('id')->toArray();
        }

        return $roleIds;
    }
}

if (!function_exists('get_role_id_by_name')) {
    /**
     * Obtiene el ID de un rol por su nombre.
     *
     * @param string $roleName
     * @return int|null
     */
    function get_role_id_by_name($roleName)
    {
        static $roleCache = [];

        if (!isset($roleCache[$roleName])) {
            $role = Role::where('name', $roleName)->first();
            $roleCache[$roleName] = $role ? $role->id : null;
        }

        return $roleCache[$roleName];
    }
}

if (!function_exists('is_special_role_id')) {
    /**
     * Verifica si un ID de rol es especial (sistema o admin).
     *
     * @param int $roleId
     * @return bool
     */
    function is_special_role_id($roleId)
    {
        return in_array($roleId, get_special_role_ids());
    }
}

if (!function_exists('user_has_special_role')) {
    /**
     * Verifica si el usuario actual tiene un rol especial.
     * Un rol es especial si tiene is_system_role = 1
     *
     * @return bool
     */
    function user_has_special_role()
    {
        // Usar el valor de sesión si está disponible
        if (Session::has('user.is_system_role')) {
            return Session::get('user.is_system_role') === true;
        }
        // Fallback: consulta a la base de datos
        $userId = Session::get('user')['id'] ?? null;
        if (!$userId) {
            return false;
        }
        $userRole = Role::join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->first();
        return $userRole && $userRole->is_system_role == 1;
    }
}
