<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPage extends Model
{
    protected $fillable = [
        'company_id',
        'added_by',
        'page_title',
        'slug',
        'content',
        'status',
    ];
}
