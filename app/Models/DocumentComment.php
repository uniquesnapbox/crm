<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentComment extends BaseModel
{
    use HasCompany;

    public const VISIBILITY_INTERNAL = 'internal';
    public const VISIBILITY_SHARED = 'shared';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'comment',
        'visibility',
        'added_by',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
