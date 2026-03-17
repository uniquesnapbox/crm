<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentWorkflow extends BaseModel
{
    use HasCompany, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING_SIGNATURE = 'pending_signature';
    public const STATUS_PARTIALLY_SIGNED = 'partially_signed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const APPROVAL_NOT_REQUIRED = 'not_required';
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const SIGNATURE_NOT_REQUIRED = 'not_required';
    public const SIGNATURE_PENDING = 'pending';
    public const SIGNATURE_PARTIALLY_SIGNED = 'partially_signed';
    public const SIGNATURE_SIGNED = 'signed';

    protected $fillable = [
        'company_id',
        'template_id',
        'document_number',
        'original_document_number',
        'title',
        'subject',
        'category',
        'document_type',
        'module_context',
        'context_id',
        'owner_id',
        'client_id',
        'project_id',
        'status',
        'approval_status',
        'signature_status',
        'generated_html',
        'generated_pdf_path',
        'verification_hash',
        'expires_at',
        'sent_at',
        'completed_at',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function workflowData(): HasOne
    {
        return $this->hasOne(DocumentWorkflowData::class, 'document_workflow_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DocumentRecipient::class, 'document_workflow_id');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(DocumentAccessToken::class, 'document_workflow_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'document_workflow_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'document_workflow_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(DocumentAuditLog::class, 'document_workflow_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'document_workflow_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'document_workflow_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }
}
