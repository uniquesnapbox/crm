<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends BaseModel
{
    use HasCompany;

    public const TYPE_DRAWN = 'drawn';
    public const TYPE_TYPED = 'typed';
    public const TYPE_UPLOAD = 'upload';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'recipient_id',
        'signer_name',
        'signer_email',
        'signature_type',
        'signature_file',
        'signature_text',
        'signed_at',
        'ip_address',
        'user_agent',
        'location_meta',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
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
