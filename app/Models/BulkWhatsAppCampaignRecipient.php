<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkWhatsAppCampaignRecipient extends BaseModel
{
    use HasCompany;

    protected $table = 'bulk_whatsapp_campaign_recipients';

    protected $fillable = [
        'company_id',
        'campaign_id',
        'lead_id',
        'lead_name',
        'phone',
        'rendered_message',
        'status',
        'provider_message_id',
        'error_message',
        'response_data',
        'attempt_count',
        'sent_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BulkWhatsAppCampaign::class, 'campaign_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
