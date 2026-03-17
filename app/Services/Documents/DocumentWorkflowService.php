<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentWorkflowService
{
    public function createDraft(array $payload = []): DocumentWorkflow
    {
        return new DocumentWorkflow($payload);
    }

    public function send(DocumentWorkflow $workflow): DocumentWorkflow
    {
        return $workflow;
    }

    public function cancel(DocumentWorkflow $workflow): DocumentWorkflow
    {
        return $workflow;
    }
}
