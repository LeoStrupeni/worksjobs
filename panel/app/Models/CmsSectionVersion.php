<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSectionVersion extends Model
{
    const UPDATED_AT = null; // Solo tiene created_at

    protected $fillable = [
        'section_id',
        'config',
        'user_id',
        'change_notes',
    ];

    protected $casts = [
        'config' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con la sección
     */
    public function section()
    {
        return $this->belongsTo(CmsSection::class, 'section_id');
    }

    /**
     * Relación con el usuario que hizo el cambio
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Restaurar esta versión a la sección
     */
    public function restore()
    {
        $section = $this->section;
        
        // Crear versión actual antes de restaurar
        $section->createVersion(
            auth()->id(), 
            "Restaurado desde versión del " . $this->created_at->format('d/m/Y H:i')
        );
        
        // Restaurar configuración
        $section->config = $this->config;
        $section->save();
        
        return $section;
    }
}
