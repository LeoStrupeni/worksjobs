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
}
