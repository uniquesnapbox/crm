<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentApprovalService
{
    public function approve(DocumentWorkflow $workflow, array $payload = []): DocumentWorkflow
    {
        return $workflow;
    }

    public function reject(DocumentWorkflow $workflow, array $payload = []): DocumentWorkflow
    {
        return $workflow;
    }
}
