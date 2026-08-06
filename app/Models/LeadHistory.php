<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadHistory extends BaseModel
{
    protected $fillable = [
        'company_id',
        'lead_id',
        'event_type',
        'title',
        'description',
        'field_key',
        'old_value',
        'new_value',
        'meta',
        'created_by',
        'event_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'event_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScope(ActiveScope::class);
    }
}

