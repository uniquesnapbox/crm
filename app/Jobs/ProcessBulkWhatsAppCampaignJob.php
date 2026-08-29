<?php

namespace App\Jobs;

use App\Models\BulkWhatsAppCampaign;
use App\Services\BulkWhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBulkWhatsAppCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $campaignId)
    {
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping('bulk-whatsapp-campaign:' . $this->campaignId),
        ];
    }

    public function handle(BulkWhatsAppService $bulkService): void
    {
        $campaign = BulkWhatsAppCampaign::with(['template', 'recipients' => function ($query) {
            $query->orderBy('id');
        }])->find($this->campaignId);

        if (!$campaign) {
            return;
        }

        if (in_array($campaign->status, ['paused', 'stopped', 'completed', 'failed'], true)) {
            return;
        }

        $pendingRecipient = $campaign->recipients()
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if (!$pendingRecipient) {
            $campaign->refreshProgress();
            return;
        }

        if (blank($campaign->started_at)) {
            $campaign->forceFill(['started_at' => now()])->saveQuietly();
        }

        $campaign->forceFill(['status' => 'running'])->saveQuietly();

        try {
            SendBulkWhatsAppRecipientJob::dispatchSync((int) $pendingRecipient->id);
        } catch (Throwable $exception) {
            $campaign->forceFill([
                'last_error' => $exception->getMessage(),
            ])->saveQuietly();
        }

        $campaign->refreshProgress();
        $campaign->refresh();

        if (in_array($campaign->status, ['paused', 'stopped', 'completed', 'failed'], true)) {
            return;
        }

        $morePending = $campaign->recipients()->where('status', 'pending')->exists();
        if (!$morePending) {
            return;
        }

        $delay = $bulkService->normalizeDelayForCampaign($campaign);
        self::dispatch($campaign->id)->delay(now()->addSeconds($delay));
    }
}
