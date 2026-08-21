<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadWhatsAppMessage extends BaseModel
{
    use HasCompany;

    protected $table = 'lead_whatsapp_messages';

    protected $fillable = [
        'company_id',
        'lead_id',
        'direction',
        'phone',
        'provider_message_id',
        'content_type',
        'message',
        'status',
        'metadata',
        'message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'message_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
