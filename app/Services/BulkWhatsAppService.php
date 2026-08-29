<?php

namespace App\Services;

use App\Models\BulkWhatsAppCampaign;
use App\Models\BulkWhatsAppCampaignRecipient;
use App\Models\BulkWhatsAppTemplate;
use App\Models\Lead;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BulkWhatsAppService
{
    public function __construct(
        private WhatsAppGatewayService $gatewayService
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function previewRecipients(Collection $leads, string $message, ?BulkWhatsAppTemplate $template = null): array
    {
        return $leads->map(function (Lead $lead) use ($message, $template) {
            $phone = $this->normalizePhone((string) ($lead->mobile ?: $lead->cell ?: $lead->office));
            $renderedMessage = $this->renderMessage($message, $lead, $template);

            return [
                'lead_id' => $lead->id,
                'lead_name' => $lead->client_name,
                'company_name' => $lead->company_name,
                'phone' => $phone ?: null,
                'status' => $phone === '' ? 'missing_phone' : 'ready',
                'preview_message' => $renderedMessage,
            ];
        })->values()->all();
    }

    /**
     * @return array{session_key:string,template_name:?string,message:string,recipient_count:int}
     */
    public function resolveCampaignMeta(?BulkWhatsAppTemplate $template, string $message, int $recipientCount): array
    {
        $setting = WhatsappNotificationSetting::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->first();

        return [
            'session_key' => $setting?->resolved_whatsapp_session_key ?: (string) config('services.whatsapp_service.session', ''),
            'template_name' => $template?->name,
            'message' => $message,
            'recipient_count' => $recipientCount,
        ];
    }

    public function createCampaign(
        string $name,
        string $message,
        Collection $leads,
        ?BulkWhatsAppTemplate $template = null,
        array $filters = []
    ): BulkWhatsAppCampaign {
        $meta = $this->resolveCampaignMeta($template, $message, $leads->count());

        $campaign = BulkWhatsAppCampaign::create([
            'company_id' => company()->id,
            'created_by' => user()->id,
            'template_id' => $template?->id,
            'name' => trim($name) !== '' ? trim($name) : 'Bulk WhatsApp Campaign',
            'session_key' => $meta['session_key'] !== '' ? $meta['session_key'] : null,
            'message_body' => $meta['message'],
            'lead_filters' => $filters,
            'recipient_count' => $meta['recipient_count'],
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => 'queued',
            'started_at' => now(),
        ]);

        foreach ($leads as $lead) {
            $phone = $this->normalizePhone((string) ($lead->mobile ?: $lead->cell ?: $lead->office));
            $campaign->recipients()->create([
                'company_id' => $campaign->company_id,
                'lead_id' => $lead->id,
                'lead_name' => (string) $lead->client_name,
                'phone' => $phone !== '' ? $phone : null,
                'rendered_message' => $this->renderMessage($message, $lead, $template),
                'status' => $phone === '' ? 'failed' : 'pending',
                'error_message' => $phone === '' ? 'Lead mobile number is missing or invalid.' : null,
                'attempt_count' => 0,
            ]);
        }

        return $campaign;
    }

    public function renderMessage(string $message, Lead $lead, ?BulkWhatsAppTemplate $template = null): string
    {
        $templateSource = trim($message);

        if ($template && trim($templateSource) === '') {
            $templateSource = trim((string) $template->message);
        }

        $placeholders = [
            '{{name}}' => (string) $lead->client_name,
            '{{client_name}}' => (string) $lead->client_name,
            '{{company}}' => (string) ($lead->company_name ?: optional($lead->company)->company_name ?: ''),
            '{{company_name}}' => (string) ($lead->company_name ?: optional($lead->company)->company_name ?: ''),
            '{{mobile}}' => (string) $lead->mobile,
            '{{email}}' => (string) $lead->client_email,
            '{{lead_id}}' => (string) $lead->id,
            '{{status}}' => (string) ($lead->leadStatus?->type ?? $lead->contact_status ?? ''),
            '{{source}}' => (string) ($lead->leadSource?->type ?? ''),
            '{{category}}' => (string) ($lead->category?->category_name ?? ''),
            '{{interest_level}}' => (string) $lead->interest_level,
            '{{product}}' => (string) ($lead->products_services ?: ''),
            '{{products_services}}' => (string) ($lead->products_services ?: ''),
            '{{assigned_to}}' => (string) optional($lead->assignedTo)->name,
            '{{added_by}}' => (string) optional($lead->addedBy)->name,
        ];

        return trim(strtr($templateSource, $placeholders));
    }

    public function normalizePhone(string $mobile): string
    {
        $phone = preg_replace('/\D+/', '', $mobile);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        $defaultCountryCode = preg_replace(
            '/\D+/',
            '',
            (string) config('services.whatsapp_service.default_country_code', '91')
        );

        if (strlen($phone) === 10 && $defaultCountryCode !== '') {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    public function resolveSessionKey(): string
    {
        $setting = WhatsappNotificationSetting::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->first();

        if ($setting && $setting->resolved_whatsapp_session_key !== '') {
            return $setting->resolved_whatsapp_session_key;
        }

        $fallback = preg_replace('/\D+/', '', (string) config('services.whatsapp_service.session', ''));

        return $fallback !== '' ? $fallback : 'default';
    }

    public function markRecipientSent(BulkWhatsAppCampaignRecipient $recipient, array $responseData): void
    {
        $recipient->forceFill([
            'status' => 'sent',
            'provider_message_id' => Str::limit((string) data_get($responseData, 'id', ''), 191, ''),
            'response_data' => $responseData,
            'sent_at' => now(),
            'error_message' => null,
        ])->saveQuietly();
    }

    public function markRecipientFailed(BulkWhatsAppCampaignRecipient $recipient, string $error, array $responseData = []): void
    {
        $recipient->forceFill([
            'status' => 'failed',
            'error_message' => $error,
            'response_data' => $responseData ?: null,
            'sent_at' => $recipient->sent_at,
        ])->saveQuietly();
    }
}
