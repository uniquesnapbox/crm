<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentStatusResolver
{
    public function resolve(DocumentWorkflow $workflow): array
    {
        return [
            'status' => $workflow->status,
            'approval_status' => $workflow->approval_status,
            'signature_status' => $workflow->signature_status,
        ];
    }
}
