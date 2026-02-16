<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSection extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'config',
        'order',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array', // Automáticamente convierte JSON a array
        'is_active' => 'boolean',
    ];

    /**
     * Relación con versiones
     */
    public function versions()
    {
        return $this->hasMany(CmsSectionVersion::class, 'section_id');
    }

    /**
     * Última versión
     */
    public function latestVersion()
    {
        return $this->hasOne(CmsSectionVersion::class, 'section_id')->latestOfMany();
    }

    /**
     * Crear versión antes de actualizar
     */
    public function createVersion($userId = null, $notes = null)
    {
        return $this->versions()->create([
            'config' => $this->config,
            'user_id' => $userId,
            'change_notes' => $notes,
            'created_at' => now(),
        ]);
    }

    /**
     * Obtener valor de configuración por clave
     */
    public function getConfigValue($key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Actualizar valor de configuración por clave
     */
    public function setConfigValue($key, $value)
    {
        $config = $this->config;
        data_set($config, $key, $value);
        $this->config = $config;
        return $this;
    }
}
