<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUp extends BaseModel
{
    protected $table = 'lead_follow_up';

    protected $casts = [
        'next_follow_up_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'whatsapp_reminder_sent_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
