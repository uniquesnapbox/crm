<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BulkWhatsAppTemplate extends BaseModel
{
    use HasCompany;

    protected $table = 'bulk_whatsapp_templates';

    protected $fillable = [
        'company_id',
        'created_by',
        'updated_by',
        'name',
        'message',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
