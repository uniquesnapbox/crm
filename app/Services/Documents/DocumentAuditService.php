<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentAuditService
{
    public function log(DocumentWorkflow $workflow, string $action, array $meta = []): void
    {
    }
}
