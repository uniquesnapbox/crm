<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;
use App\Models\DocumentWorkflow;

class DocumentStatusResolver
{
    public function initialStatuses(?DocumentTemplate $template): array
    {
        if (!$template) {
            return [
                'status' => DocumentWorkflow::STATUS_DRAFT,
                'approval_status' => DocumentWorkflow::APPROVAL_NOT_REQUIRED,
                'signature_status' => DocumentWorkflow::SIGNATURE_NOT_REQUIRED,
            ];
        }

        return [
            'status' => DocumentWorkflow::STATUS_DRAFT,
            'approval_status' => $template->requires_approval
                ? DocumentWorkflow::APPROVAL_PENDING
                : DocumentWorkflow::APPROVAL_NOT_REQUIRED,
            'signature_status' => $template->requires_signature
                ? DocumentWorkflow::SIGNATURE_PENDING
                : DocumentWorkflow::SIGNATURE_NOT_REQUIRED,
        ];
    }

    public function sentStatuses(DocumentWorkflow $workflow): array
    {
        if ($workflow->approval_status === DocumentWorkflow::APPROVAL_PENDING) {
            return ['status' => DocumentWorkflow::STATUS_PENDING_APPROVAL];
        }

        if ($workflow->signature_status === DocumentWorkflow::SIGNATURE_PENDING) {
            return ['status' => DocumentWorkflow::STATUS_PENDING_SIGNATURE];
        }

        return [
            'status' => DocumentWorkflow::STATUS_APPROVED,
            'approval_status' => DocumentWorkflow::APPROVAL_NOT_REQUIRED,
            'signature_status' => DocumentWorkflow::SIGNATURE_NOT_REQUIRED,
        ];
    }
}
