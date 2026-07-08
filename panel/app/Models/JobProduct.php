<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_products';
    protected $fillable = [
        'job_id',
        'product_id',
        'idcolppy',
        'codigo',
        'descripcion',
        'unit_type',
        'quantity'
    ];

    // Relación con Job
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Relación con Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        // Al crear: registramos quién lo hace
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Al actualizar: registramos quién edita
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Al eliminar (Soft Delete): registramos quién lo borra antes de que se oculte
        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save(); // Guardamos el cambio en la base de datos
            }
        });
    }
}
