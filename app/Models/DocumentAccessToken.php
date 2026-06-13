<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessToken extends BaseModel
{
    use HasCompany;

    public const PURPOSE_PUBLIC_ACTION = 'public_action';
    public const PURPOSE_APPROVAL = 'approval';
    public const PURPOSE_SIGNATURE = 'signature';
    public const PURPOSE_VIEW = 'view';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'recipient_id',
        'token',
        'purpose',
        'expires_at',
        'used_at',
        'is_revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DocumentRecipient::class, 'recipient_id');
    }
}
