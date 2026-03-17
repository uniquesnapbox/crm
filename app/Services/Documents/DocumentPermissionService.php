<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentPermissionService
{
    public function canAccessModule(): bool
    {
        return !in_array('client', user_roles());
    }

    public function canManageWorkflow(DocumentWorkflow $workflow): bool
    {
        if (in_array('admin', user_roles())) {
            return true;
        }

        return $workflow->created_by === user()->id || $workflow->owner_id === user()->id;
    }
}
