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
}
