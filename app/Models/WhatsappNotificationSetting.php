<?php

namespace App\Models;

use App\Traits\HasCompany;

class WhatsappNotificationSetting extends BaseModel
{
    use HasCompany;

    public const DEFAULT_LEAD_CREATED_TEMPLATE = "Hello {{client_name}}, thank you for your interest. We have received your lead and our team will contact you soon.";
    public const DEFAULT_TASK_ASSIGNED_TEMPLATE = 'A new task has been assigned to you. Task: {{task_heading}}';

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function getResolvedLeadCreatedSenderNumberAttribute(): string
    {
        return (string) ($this->lead_created_sender_number ?: config('app.admin_whatsapp', ''));
    }

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
