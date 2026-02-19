<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColppySession extends Model
{
    use HasFactory;

    protected $table = 'colppy_sessions';

    protected $fillable = [
        'usuario',
        'clave_sesion',
        'id_empresa',
        'se_vence_en',
        'activa'
    ];

    protected $casts = [
        'se_vence_en' => 'datetime',
        'activa' => 'boolean'
    ];

    /**
     * Verificar si la sesión sigue siendo válida
     */
    public function esValida(): bool
    {
        return $this->activa && $this->se_vence_en > now();
    }

    /**
     * Obtener una sesión válida o null
     */
    public static function obtenerValida($usuario, $idEmpresa)
    {
        return self::where('usuario', $usuario)
            ->where('id_empresa', $idEmpresa)
            ->whereRaw('se_vence_en > NOW()')
            ->where('activa', true)
            ->first();
    }

    /**
     * Invalidar la sesión
     */
    public function invalidar()
    {
        $this->activa = false;
        $this->save();
    }
}
