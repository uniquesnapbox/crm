<?php

namespace App\Services\Documents;

use App\Models\DocumentWorkflow;

class DocumentNumberService
{
    public function nextNumber(): string
    {
        $nextId = (DocumentWorkflow::withTrashed()->max('id') ?? 0) + 1;

        return 'DOC-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
