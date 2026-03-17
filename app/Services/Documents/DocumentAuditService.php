<?php

namespace App\Services\Documents;

use App\Models\DocumentAuditLog;
use App\Models\DocumentWorkflow;

class DocumentAuditService
{
    public function log(DocumentWorkflow $workflow, string $action, array $meta = []): void
    {
        DocumentAuditLog::create([
            'company_id' => company()->id,
            'document_workflow_id' => $workflow->id,
            'action' => $action,
            'actor_type' => 'user',
            'actor_id' => user()?->id,
            'actor_name' => user()?->name,
            'meta_json' => $meta ? json_encode($meta) : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
