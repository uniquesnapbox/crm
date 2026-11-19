<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CrmAuditService
{
    public function record(
        Request $request,
        string $action,
        string $entityType,
        int|string $entityId,
        ?array $before = null,
        ?array $after = null
    ): CrmAuditLog {
        $user = $request->user();

        return CrmAuditLog::create([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'before' => $before,
            'after' => $after,
            'source' => 'chatgpt_mcp',
            'request_id' => (string) ($request->header('X-Request-Id') ?: Str::uuid()),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }
}
