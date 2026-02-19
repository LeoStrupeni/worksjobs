<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para domicilios de clientes externos (ej: Colppy API)
 * 
 * Esta tabla NO tiene FK a 'clients', permite guardar domicilios
 * de clientes que solo existen en sistemas externos.
 * 
 * El campo 'external_client_id' guarda identificadores como:
 * - 'colppy_123' para clientes de Colppy API
 * - Futuras integraciones pueden usar sus propios prefijos
 */
class ClientAddressExternal extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'clients_address_external';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    public $incrementing = true;
    public $timestamps = true;
    
    protected $fillable = [
        'external_client_id',
        'country',
        'state',
        'cp',
        'city',
        'address_street',
        'address_nro',
        'address_apartament',
        'address_detail'
    ];
}
