<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsFlutterTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'config_json',
        'is_active',
        'version',
        'description',
        'user_id'
    ];

    protected $casts = [
        'config_json' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Relación con el usuario que creó/editó
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Activar este tema (desactiva los demás)
     */
    public function activate()
    {
        // Desactivar todos los demás temas
        self::where('is_active', true)->update(['is_active' => false]);
        
        // Activar este tema
        $this->is_active = true;
        $this->save();
    }

    /**
     * Obtener tema activo
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Obtener solo el tema activo
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
