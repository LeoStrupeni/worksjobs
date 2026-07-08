<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jobs_file extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'jobs_files';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    public $autoincrement = true;
    public $timestamps = true;
    public $guarded = [];
    
    // Relación con el trabajo (la columna en jobs_files es job_id)
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
    
    /**
     * Relación con el modelo User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
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
