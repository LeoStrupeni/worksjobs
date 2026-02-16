<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'slug',
        'title',
        'content',
        'draft_content',
        'meta_title',
        'meta_description',
        'og_image',
        'is_published',
        'published_at',
        'user_id'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime'
    ];

    /**
     * Relación con el usuario que editó
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con las versiones
     */
    public function versions()
    {
        return $this->hasMany(CmsPageVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Guardar versión actual antes de modificar
     */
    public function saveVersion($userId)
    {
        $lastVersion = $this->versions()->max('version_number') ?? 0;
        
        CmsPageVersion::create([
            'cms_page_id' => $this->id,
            'content' => $this->draft_content,
            'version_number' => $lastVersion + 1,
            'created_by' => $userId,
            'created_at' => now()
        ]);
    }

    /**
     * Obtener todas las versiones con información del creador
     */
    public function getVersions()
    {
        return $this->versions()->with('creator')->get();
    }

    /**
     * Restaurar una versión específica
     */
    public function restoreVersion($versionId)
    {
        $version = CmsPageVersion::findOrFail($versionId);
        
        if ($version->cms_page_id != $this->id) {
            throw new \Exception('La versión no pertenece a esta página');
        }
        
        $this->draft_content = $version->content;
        $this->save();
        
        return $this;
    }

    /**
     * Publicar el contenido borrador
     */
    public function publish()
    {
        $this->content = $this->draft_content;
        $this->is_published = true;
        $this->published_at = now();
        $this->save();
    }

    /**
     * Obtener solo páginas publicadas
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
