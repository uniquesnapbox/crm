<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAuditLog extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'meta_json',
        'ip_address',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }
}
