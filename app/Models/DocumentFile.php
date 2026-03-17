<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFile extends BaseModel
{
    use HasCompany;

    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_GENERATED_PDF = 'generated_pdf';
    public const TYPE_SIGNATURE = 'signature';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'file_name',
        'hash_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
