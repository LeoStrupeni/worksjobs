<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    public $autoincrement = true;
    public $timestamps = true;
    public $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'stock_minimo' => 'decimal:2',
        'disponibilidad' => 'decimal:2',
        'costo_calculado' => 'decimal:2',
        'ultimo_precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'es_kit' => 'boolean',
        'is_from_colppy' => 'boolean',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'colppy_updated_at' => 'datetime',
    ];

    /**
     * Scope para obtener solo productos de Colppy
     */
    public function scopeFromColppy($query)
    {
        return $query->where('is_from_colppy', true);
    }

    /**
     * Scope para obtener solo productos (tipo P)
     */
    public function scopeProductos($query)
    {
        return $query->where('tipo_item', 'P');
    }

    /**
     * Scope para obtener productos activos (sin fecha de baja)
     */
    public function scopeActivos($query)
    {
        return $query->whereNull('fecha_baja');
    }

    /**
     * Accessor para compatibilidad con nombre antiguo idcolppy
     * Mapea idcolppy -> colppy_id (columna real en BD)
     */
    public function getIdcolppyAttribute()
    {
        return $this->colppy_id;
    }

    /**
     * Mutator para compatibilidad con nombre antiguo idcolppy
     */
    public function setIdcolppyAttribute($value)
    {
        $this->attributes['colppy_id'] = $value;
    }

    /**
     * MÉTODO CENTRALIZADO para buscar productos y/o servicios
     * Usado por ApiJobController y ApiBudgetController
     * 
     * @param string|null $search Texto a buscar en código o descripción
     * @param string|null $tipo 'P' para productos, 'S' para servicios, null para ambos
     * @param int $limit Límite de resultados
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function searchProductsAndServices($search = null, $tipo = null, $limit = 50)
    {
        $query = self::query()
            ->whereNull('deleted_at');

        // Filtrar por tipo si se especifica, sino mostrar productos Y servicios
        if ($tipo && in_array($tipo, ['P', 'S'])) {
            $query->where('tipo_item', $tipo);
        } else {
            // Mostrar productos Y servicios (no kits ni otros)
            $query->whereIn('tipo_item', ['P', 'S']);
        }

        // Búsqueda por código o descripción
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'LIKE', "%$search%")
                  ->orWhere('descripcion', 'LIKE', "%$search%");
            });
        }
        
        return $query->limit($limit)
            ->get(['id', 'codigo', 'descripcion', 'tipo_item', 'is_from_colppy', 'precio_venta']);
    }
}
