<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\CmsSectionVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    /**
     * Mostrar todas las secciones en el CMS
     */
    public function index()
    {
        $sections = CmsSection::withCount('versions')
            ->orderBy('order')
            ->get();
        
        return view('cms.sections.index', compact('sections'));
    }

    /**
     * Mostrar formulario de edición de una sección
     */
    public function edit($slug)
    {
        $section = CmsSection::where('slug', $slug)->firstOrFail();
        
        return view('cms.sections.edit', compact('section'));
    }

    /**
     * Actualizar configuración de una sección
     */
    public function update(Request $request, $slug)
    {
        $section = CmsSection::where('slug', $slug)->firstOrFail();
        
        // Validación básica
        $request->validate([
            'config' => 'required|array',
        ]);

        // Crear versión antes de actualizar
        $section->createVersion(
            Auth::id(),
            $request->input('change_notes', 'Actualización de configuración')
        );

        // Actualizar configuración
        $section->config = $request->input('config');
        $section->save();

        return response()->json([
            'success' => true,
            'message' => 'Sección actualizada correctamente',
            'section' => $section
        ]);
    }

    /**
     * Ver historial de versiones
     */
    public function versions($slug)
    {
        $section = CmsSection::where('slug', $slug)->firstOrFail();
        $versions = $section->versions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('cms.sections.versions', compact('section', 'versions'));
    }

    /**
     * Restaurar una versión anterior
     */
    public function restoreVersion($slug, $versionId)
    {
        $section = CmsSection::where('slug', $slug)->firstOrFail();
        $version = CmsSectionVersion::findOrFail($versionId);

        // Verificar que la versión pertenece a esta sección
        if ($version->section_id !== $section->id) {
            abort(403, 'Esta versión no pertenece a esta sección');
        }

        $version->restore();

        return redirect()
            ->route('cms.sections.edit', $slug)
            ->with('success', 'Versión restaurada correctamente');
    }

    /**
     * API: Obtener todas las secciones activas (para frontend)
     */
    public function getSections()
    {
        $sections = CmsSection::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->mapWithKeys(function ($section) {
                return [$section->slug => $section->config];
            });

        return response()->json($sections);
    }

    /**
     * API: Obtener una sección específica
     */
    public function getSection($slug)
    {
        $section = CmsSection::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'slug' => $section->slug,
            'name' => $section->name,
            'config' => $section->config,
            'is_active' => $section->is_active,
            'updated_at' => $section->updated_at->format('d/m/Y H:i'),
        ]);
    }
}
