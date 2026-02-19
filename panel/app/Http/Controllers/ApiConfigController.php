<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ApiConfigController extends Controller
{
    /**
     * Mostrar la página de configuración de API
     */
    public function index()
    {
        // Verificar permiso de lectura CMS
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403, 'No tienes permiso para acceder a la configuración de API');
        }

        $apiConfigs = Config::whereIn('name', [
            'url_api_login',
            'user_dev_api',
            'pass_dev_api',
            'user_api',
            'pass_api',
            'id_empresa_api',
            'google_api_key',
            'colppy_clientes_modo'
        ])->get();

        // Crear array con valores por defecto si no existen
        $configArray = [
            'url_api_login' => $apiConfigs->where('name', 'url_api_login')->first()->value ?? '',
            'user_dev_api' => $apiConfigs->where('name', 'user_dev_api')->first()->value ?? '',
            'pass_dev_api' => $apiConfigs->where('name', 'pass_dev_api')->first()->value ?? '',
            'user_api' => $apiConfigs->where('name', 'user_api')->first()->value ?? '',
            'pass_api' => $apiConfigs->where('name', 'pass_api')->first()->value ?? '',
            'id_empresa_api' => $apiConfigs->where('name', 'id_empresa_api')->first()->value ?? '',
            'google_api_key' => $apiConfigs->where('name', 'google_api_key')->first()->value ?? '',
            'colppy_clientes_modo' => $apiConfigs->where('name', 'colppy_clientes_modo')->first()->value ?? 'local'
        ];

        return view('cms.api-config.index', compact('configArray'));
    }

    /**
     * Actualizar configuración de API
     */
    public function update(Request $request)
    {
        // Verificar permiso de edición CMS
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403, 'No tienes permiso para actualizar la configuración de API');
        }

        $validated = $request->validate([
            'url_api_login' => 'nullable|string|max:255',
            'user_dev_api' => 'nullable|string|max:255',
            'pass_dev_api' => 'nullable|string|max:255',
            'user_api' => 'nullable|string|max:255',
            'pass_api' => 'nullable|string|max:255',
            'id_empresa_api' => 'nullable|string|max:255',
            'google_api_key' => 'nullable|string|max:255',
            'colppy_clientes_modo' => 'required|in:local,api,hibrido'
        ]);

        // Actualizar o crear cada configuración
        foreach ($validated as $name => $value) {
            Config::updateOrCreate(
                ['name' => $name],
                ['value' => $value]
            );
        }

        return redirect()->route('cms.api-config.index')
            ->with('success', 'Configuración de API actualizada correctamente');
    }
}
