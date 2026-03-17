<?php

namespace App\Models;

use App\Traits\HasCompany;

class WhatsappNotificationSetting extends BaseModel
{
    use HasCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function getDeliveryStatusLabelAttribute(): string
    {
        return match ($this->last_delivery_status) {
            'sent' => 'Sent confirmed',
            'accepted' => 'Accepted only',
            'failed' => 'Failed',
            default => 'Not sent yet',
        };
    }
}
