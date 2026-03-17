<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;
use App\Models\User;

class DocumentPermissionService
{
    public function canView(User $user, DocumentWorkflow $workflow): bool
    {
        return true;
    }
}
