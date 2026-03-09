<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    public $autoincrement = true;
    public $timestamps = true;
    public $guarded = [];

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
}
