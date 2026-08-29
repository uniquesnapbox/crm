<?php

namespace App\Jobs;

use App\Models\BulkWhatsAppCampaign;
use App\Models\BulkWhatsAppCampaignRecipient;
use App\Models\LeadWhatsAppMessage;
use App\Services\BulkWhatsAppService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class SendBulkWhatsAppRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $recipientId)
    {
    }

    public function handle(
        BulkWhatsAppService $bulkService,
        WhatsAppGatewayService $gatewayService
    ): void {
        $recipient = BulkWhatsAppCampaignRecipient::with([
            'campaign',
            'lead.category',
            'lead.leadSource',
            'lead.leadStatus',
            'lead.addedBy',
            'lead.assignedTo',
        ])->find($this->recipientId);

        if (!$recipient || !$recipient->campaign) {
            return;
        }

        $campaign = $recipient->campaign;

        if ($recipient->status === 'sent') {
            $campaign->refreshProgress();
            return;
        }

        if (blank($recipient->phone)) {
            $error = $recipient->error_message ?: 'Lead mobile number is missing or invalid.';
            $bulkService->markRecipientFailed($recipient, $error);
            $this->storeLeadLog($recipient, null, 'failed', $error);
            $campaign->refreshProgress();
            return;
        }

        $recipient->forceFill([
            'attempt_count' => ((int) $recipient->attempt_count) + 1,
            'status' => 'pending',
        ])->saveQuietly();

        try {
            $sessionKey = $campaign->session_key ?: $bulkService->resolveSessionKey();
            $sent = $gatewayService->sendMessage(
                (string) $recipient->phone,
                (string) $recipient->rendered_message,
                $sessionKey
            );

            $responseData = (array) ($gatewayService->getLastResponseData() ?? []);

            if (!$sent) {
                $error = (string) ($gatewayService->getLastError() ?: 'Unable to send WhatsApp message.');
                $bulkService->markRecipientFailed($recipient, $error, $responseData);
                $this->storeLeadLog($recipient, null, 'failed', $error);
                $campaign->refreshProgress();
                return;
            }

            $providerMessageId = trim((string) data_get($responseData, 'id', ''));
            if ($providerMessageId === '') {
                $providerMessageId = 'crm:bulk:' . $campaign->id . ':' . $recipient->id;
            }

            $responseData['id'] = $providerMessageId;
            $bulkService->markRecipientSent($recipient, $responseData);
            $this->storeLeadLog($recipient, $providerMessageId, 'sent', null, $responseData);

            $campaign->refreshProgress();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $bulkService->markRecipientFailed($recipient, $error);
            $this->storeLeadLog($recipient, null, 'failed', $error);
            $campaign->refreshProgress();
        }
    }

    private function storeLeadLog(
        BulkWhatsAppCampaignRecipient $recipient,
        ?string $providerMessageId,
        string $status,
        ?string $error,
        array $responseData = []
    ): void {
        $messageId = $providerMessageId ?: 'crm:bulk:' . $recipient->campaign_id . ':' . $recipient->id;

        LeadWhatsAppMessage::withoutGlobalScopes()->updateOrCreate([
            'provider_message_id' => Str::limit($messageId, 191, ''),
        ], [
            'company_id' => $recipient->company_id,
            'lead_id' => $recipient->lead_id,
            'direction' => 'outbound',
            'phone' => (string) $recipient->phone,
            'content_type' => 'text',
            'message' => (string) $recipient->rendered_message,
            'status' => $status,
            'metadata' => array_filter([
                'bulk_campaign_id' => $recipient->campaign_id,
                'bulk_recipient_id' => $recipient->id,
                'session_key' => $recipient->campaign?->session_key,
                'response' => $responseData ?: null,
                'error' => $error,
            ], fn ($value) => $value !== null && $value !== ''),
            'message_at' => now(),
        ]);
    }
}
