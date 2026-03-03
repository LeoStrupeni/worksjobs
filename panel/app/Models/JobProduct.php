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
}
