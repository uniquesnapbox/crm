<?php

namespace App\Jobs;

use App\Http\Controllers\LeadContactController;
use App\Models\Lead;
use App\Services\WhatsAppGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncLeadChatHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public int $leadId)
    {
    }

    public function handle(LeadContactController $leadContactController, WhatsAppGatewayService $gatewayService): void
    {
        $lead = Lead::withoutGlobalScopes()->find($this->leadId);

        if (!$lead || !$gatewayService->isConfigured()) {
            return;
        }

        $leadContactController->syncLeadChatHistory($lead, $gatewayService);
    }
}
