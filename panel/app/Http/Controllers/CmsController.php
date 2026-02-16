<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsConfig;
use App\Models\CmsFlutterTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CmsController extends Controller
{
    /**
     * Panel principal del CMS
     */
    public function index()
    {
        // Verificar permiso de lectura CMS
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403, 'No tienes permiso para acceder al CMS');
        }

        $pages = CmsPage::orderBy('key')->get();
        $configs = CmsConfig::orderBy('group')->orderBy('key')->get();
        $themes = CmsFlutterTheme::orderBy('created_at', 'desc')->get();

        return view('cms.index', compact('pages', 'configs', 'themes'));
    }

    /**
     * ============= PÁGINAS CMS =============
     */

    /**
     * Mostrar formulario de edición de página
     */
    public function editPage($id)
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $page = CmsPage::findOrFail($id);
        return view('cms.pages.edit', compact('page'));
    }

    /**
     * Actualizar borrador de página
     */
    public function updatePageDraft(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'draft_content' => 'required'
        ]);

        $page = CmsPage::findOrFail($id);
        
        // Guardar versión antes de actualizar
        if ($page->draft_content && $page->draft_content !== $request->draft_content) {
            $page->saveVersion(Auth::id());
        }
        
        $page->title = $request->title;
        $page->draft_content = $request->draft_content;
        $page->user_id = Auth::id();
        $page->save();

        return redirect()->back()->with('success', 'Borrador guardado exitosamente');
    }

    /**
     * Preview del borrador
     */
    public function previewPage($id)
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $page = CmsPage::findOrFail($id);
        return view('cms.pages.preview', compact('page'));
    }

    /**
     * Publicar página (mover borrador a contenido publicado)
     */
    public function publishPage($id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $page = CmsPage::findOrFail($id);
        $page->publish();

        return redirect()->route('cms.index')->with('success', 'Página publicada exitosamente');
    }

    /**
     * Crear nueva página
     */
    public function createPage(Request $request)
    {
        if (!in_array('create', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'key' => 'required|string|unique:cms_pages,key|max:255',
            'title' => 'required|string|max:255',
            'draft_content' => 'nullable'
        ]);

        $page = CmsPage::create([
            'key' => $request->key,
            'title' => $request->title,
            'draft_content' => $request->draft_content ?? '',
            'content' => '',
            'is_published' => false,
            'user_id' => Auth::id()
        ]);

        return redirect()->route('cms.pages.edit', $page->id)->with('success', 'Página creada exitosamente');
    }

    /**
     * ============= CONFIGURACIONES =============
     */

    /**
     * Listar configuraciones
     */
    public function configs()
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $configs = CmsConfig::orderBy('group')->orderBy('key')->get();
        return view('cms.configs.index', compact('configs'));
    }

    /**
     * Actualizar configuración
     */
    public function updateConfig(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'value' => 'required'
        ]);

        $config = CmsConfig::findOrFail($id);
        $config->value = $request->value;
        $config->save();

        return redirect()->back()->with('success', 'Configuración actualizada');
    }

    /**
     * Crear nueva configuración
     */
    public function createConfig(Request $request)
    {
        if (!in_array('create', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'key' => 'required|string|unique:cms_configs,key|max:255',
            'value' => 'required',
            'type' => 'required|in:text,color,number,json,boolean,image',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        CmsConfig::create($request->all());

        return redirect()->back()->with('success', 'Configuración creada exitosamente');
    }

    /**
     * ============= TEMAS FLUTTER =============
     */

    /**
     * Listar temas Flutter
     */
    public function themes()
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $themes = CmsFlutterTheme::orderBy('created_at', 'desc')->get();
        return view('cms.themes.index', compact('themes'));
    }

    /**
     * Editar tema Flutter
     */
    public function editTheme($id)
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $theme = CmsFlutterTheme::findOrFail($id);
        return view('cms.themes.edit', compact('theme'));
    }

    /**
     * Actualizar tema Flutter
     */
    public function updateTheme(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'config_json' => 'required|json',
            'version' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $theme = CmsFlutterTheme::findOrFail($id);
        $theme->name = $request->name;
        $theme->config_json = $request->config_json;
        $theme->version = $request->version;
        $theme->description = $request->description;
        $theme->user_id = Auth::id();
        $theme->save();

        return redirect()->back()->with('success', 'Tema actualizado exitosamente');
    }

    /**
     * Activar tema Flutter
     */
    public function activateTheme($id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $theme = CmsFlutterTheme::findOrFail($id);
        $theme->activate();

        return redirect()->back()->with('success', 'Tema activado exitosamente');
    }

    /**
     * Crear nuevo tema Flutter
     */
    public function createTheme(Request $request)
    {
        if (!in_array('create', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'config_json' => 'required|json',
            'version' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        CmsFlutterTheme::create([
            'name' => $request->name,
            'config_json' => $request->config_json,
            'version' => $request->version,
            'description' => $request->description,
            'is_active' => false,
            'user_id' => Auth::id()
        ]);

        return redirect()->route('cms.themes')->with('success', 'Tema creado exitosamente');
    }

    /**
     * Ver historial de versiones de una página
     */
    public function viewVersions($id)
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $page = CmsPage::with('versions.creator')->findOrFail($id);
        return view('cms.pages.versions', compact('page'));
    }

    /**
     * Restaurar una versión específica
     */
    public function restoreVersion(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $page = CmsPage::findOrFail($id);
        $page->restoreVersion($request->version_id);

        return redirect()->route('cms.pages.edit', $id)->with('success', 'Versión restaurada exitosamente en el borrador');
    }

    /**
     * API: Obtener tema activo (para Flutter app)
     */
    public function getActiveTheme()
    {
        $theme = CmsFlutterTheme::getActive();
        
        if (!$theme) {
            return response()->json([
                'error' => 'No hay tema activo configurado'
            ], 404);
        }

        return response()->json([
            'name' => $theme->name,
            'version' => $theme->version,
            'config' => $theme->config_json
        ]);
    }
}
