<?php

namespace App\Jobs;

use App\Services\LeadFollowUpWhatsAppSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDailyLeadFollowUpSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $companyId, public bool $force = false)
    {
    }

    public function handle(LeadFollowUpWhatsAppSummaryService $service): void
    {
        try {
            $service->sendDailySummaryForCompany($this->companyId, $this->force);
        } catch (\Throwable $exception) {
            Log::warning('Daily lead follow-up WhatsApp summary job failed.', [
                'company_id' => $this->companyId,
                'force' => $this->force,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
