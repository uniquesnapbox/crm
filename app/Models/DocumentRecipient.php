<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRecipient extends BaseModel
{
    use HasCompany;

    public const ROLE_VIEWER = 'viewer';
    public const ROLE_APPROVER = 'approver';
    public const ROLE_SIGNER = 'signer';
    public const ROLE_CC = 'cc';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'recipient_type',
        'recipient_id',
        'name',
        'email',
        'phone',
        'role',
        'sequence_no',
        'status',
        'acted_at',
        'is_external',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'is_external' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(DocumentAccessToken::class, 'recipient_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'recipient_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'recipient_id');
    }
}
