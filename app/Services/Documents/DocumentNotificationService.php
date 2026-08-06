<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentNotificationService
{
    public function notifyCreated(DocumentWorkflow $workflow): void
    {
    }

    public function notifyApprovalPending(DocumentWorkflow $workflow): void
    {
    }

    public function notifySignaturePending(DocumentWorkflow $workflow): void
    {
    }
}
