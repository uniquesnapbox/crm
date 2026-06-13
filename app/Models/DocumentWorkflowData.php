<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowData extends BaseModel
{
    use HasCompany;

    protected $table = 'document_workflow_data';

    protected $fillable = [
        'company_id',
        'document_workflow_id',
        'data_json',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }
}
