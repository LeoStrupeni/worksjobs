<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role_Has_Permission extends Model
{
    use HasFactory;
    protected $table = 'role_has_permissions';
    public $timestamps = false;
    public $guarded = [];
}
