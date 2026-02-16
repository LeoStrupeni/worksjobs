<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CmsMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_name',
        'display_name',
        'mime_type',
        'size',
        'path',
        'disk',
        'type',
        'alt_text',
        'caption',
        'uploaded_by'
    ];

    /**
     * Relación con el usuario que subió el archivo
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtener la URL completa del archivo
     */
    public function getUrlAttribute()
    {
        // Generar URL relativa para que funcione en cualquier entorno
        return '/' . str_replace('\\', '/', $this->path);
    }

    /**
     * Obtener tamaño formateado
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Eliminar archivo físico al borrar el registro
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($media) {
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }
}
