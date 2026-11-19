<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAuditLog extends BaseModel
{
    protected $table = 'crm_audit_logs';

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
