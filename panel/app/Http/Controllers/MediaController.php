<?php

namespace App\Http\Controllers;

use App\Models\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class MediaController extends Controller
{
    /**
     * Vista principal de la biblioteca de medios
     */
    public function index(Request $request)
    {
        if (!in_array('read', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $type = $request->get('type', 'all');
        
        $mediaQuery = CmsMedia::with('uploader')->orderBy('created_at', 'desc');
        
        if ($type !== 'all') {
            $mediaQuery->where('type', $type);
        }
        
        $media = $mediaQuery->paginate(24);
        
        // Si es petición Ajax, devolver JSON
        if ($request->ajax() || $request->expectsJson()) {
            // Si solicita formato data (para modales/selectores), devolver JSON puro
            if ($request->get('format') === 'data') {
                return response()->json([
                    'media' => $media,
                    'hasMore' => $media->hasMorePages(),
                    'currentPage' => $media->currentPage(),
                    'total' => $media->total()
                ]);
            }
            
            // De lo contrario, devolver HTML renderizado (para paginación)
            $html = '';
            foreach ($media as $item) {
                $canEdit = in_array('update', Session::get('user')['permissions']['cms'] ?? []);
                $canDelete = in_array('delete', Session::get('user')['permissions']['cms'] ?? []);
                
                $html .= view('cms.media.partials.item', compact('item', 'canEdit', 'canDelete'))->render();
            }
            
            return response()->json([
                'html' => $html,
                'hasMore' => $media->hasMorePages(),
                'currentPage' => $media->currentPage(),
                'total' => $media->total()
            ]);
        }
        
        return view('cms.media.index', compact('media', 'type'));
    }

    /**
     * Upload de archivo (para CKEditor y biblioteca)
     */
    public function upload(Request $request)
    {
        if (!in_array('create', Session::get('user')['permissions']['cms'] ?? [])) {
            return response()->json(['error' => ['message' => 'No autorizado']], 403);
        }

        $request->validate([
            'upload' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg|max:5120', // 5MB
        ]);

        try {
            $file = $request->file('upload');
            
            // Generar nombre único
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Guardar en storage/app/public/cms-media (método Laravel estándar)
            $path = $file->storeAs('cms-media', $filename, 'public');
            
            // El path será: cms-media/filename.ext
            // Se accede vía: /storage/cms-media/filename.ext
            
            // Crear registro en base de datos
            $media = CmsMedia::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'display_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'disk' => 'local',
                'type' => $this->getFileType($file->getMimeType()),
                'uploaded_by' => Auth::id()
            ]);

            // Respuesta para CKEditor 5
            return response()->json([
                'url' => env('APP_URL') . '/storage/' . $path,
                'id' => $media->id,
                'default' => Storage::url($path)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'message' => 'Error al subir el archivo: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Subir múltiples archivos desde la biblioteca
     */
    public function uploadMultiple(Request $request)
    {
        if (!in_array('create', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,gif,webp,svg,bmp,tiff,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp4,avi,mov,wmv,flv,mkv,webm,mp3,wav,ogg,m4a,aac,flac|max:51200', // 50MB
        ]);

        $uploaded = [];

        foreach ($request->file('files') as $file) {
            try {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                
                // Guardar en storage/app/public/cms-media (método Laravel estándar)
                $path = $file->storeAs('cms-media', $filename, 'public');
                
                // El path será: cms-media/filename.ext
                
                $media = CmsMedia::create([
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'display_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'disk' => 'local',
                    'type' => $this->getFileType($file->getMimeType()),
                    'uploaded_by' => Auth::id()
                ]);

                $uploaded[] = $media;
                
            } catch (\Exception $e) {
                continue;
            }
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'uploaded' => count($uploaded),
                'message' => count($uploaded) . ' archivo(s) subido(s) exitosamente'
            ]);
        }

        return redirect()->back()->with('success', count($uploaded) . ' archivo(s) subido(s) exitosamente');
    }

    /**
     * Actualizar nombre para mostrar
     */
    public function updateName(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'display_name' => 'nullable|string|max:255',
        ]);

        $media = CmsMedia::findOrFail($id);
        $media->display_name = $request->input('display_name') ?: null;
        $media->save();

        return response()->json([
            'success' => true,
            'message' => 'Nombre actualizado correctamente'
        ]);
    }

    /**
     * Actualizar información del archivo
     */
    public function update(Request $request, $id)
    {
        if (!in_array('update', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        $media = CmsMedia::findOrFail($id);
        $media->update($request->only(['alt_text', 'caption']));

        return response()->json([
            'success' => true,
            'message' => 'Información actualizada'
        ]);
    }

    /**
     * Eliminar archivo
     */
    public function destroy($id)
    {
        if (!in_array('delete', Session::get('user')['permissions']['cms'] ?? [])) {
            abort(403);
        }

        $media = CmsMedia::findOrFail($id);
        $media->delete();

        return redirect()->back()->with('success', 'Archivo eliminado exitosamente');
    }

    /**
     * Determinar tipo de archivo basado en MIME type
     */
    private function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (str_contains($mimeType, 'pdf') || 
                  str_contains($mimeType, 'word') || 
                  str_contains($mimeType, 'document') ||
                  str_contains($mimeType, 'spreadsheet') ||
                  str_contains($mimeType, 'excel') ||
                  str_contains($mimeType, 'powerpoint') ||
                  str_contains($mimeType, 'presentation') ||
                  str_contains($mimeType, 'text/plain')) {
            return 'document';
        } else {
            return 'document'; // Por defecto, tratar como documento
        }
    }
}
