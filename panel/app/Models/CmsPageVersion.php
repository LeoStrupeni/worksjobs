<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPageVersion extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'cms_page_id',
        'content',
        'version_number',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function page()
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
