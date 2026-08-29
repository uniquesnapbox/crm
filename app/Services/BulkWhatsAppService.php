<?php

namespace App\Services;

use App\Models\BulkWhatsAppCampaign;
use App\Models\BulkWhatsAppCampaignRecipient;
use App\Models\BulkWhatsAppTemplate;
use App\Models\Lead;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
        $attachment = $this->templateAttachmentPayload($template);

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
                'has_attachment' => !empty($attachment),
                'attachment_url' => $attachment['url'] ?? null,
                'attachment_name' => $attachment['fileName'] ?? null,
                'attachment_mime' => $attachment['mimeType'] ?? null,
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
        array $filters = [],
        ?array $attachment = null,
        int $delayMinSeconds = 8,
        int $delayMaxSeconds = 20
    ): BulkWhatsAppCampaign {
        $meta = $this->resolveCampaignMeta($template, $message, $leads->count());
        $attachmentMeta = $this->normalizeAttachmentMeta($attachment ?: $this->templateAttachmentMeta($template));
        [$delayMinSeconds, $delayMaxSeconds] = $this->normalizeDelayWindow($delayMinSeconds, $delayMaxSeconds);

        $campaign = BulkWhatsAppCampaign::create([
            'company_id' => company()->id,
            'created_by' => user()->id,
            'template_id' => $template?->id,
            'name' => trim($name) !== '' ? trim($name) : 'Bulk WhatsApp Campaign',
            'session_key' => $meta['session_key'] !== '' ? $meta['session_key'] : null,
            'message_body' => $meta['message'],
            'attachment_path' => $attachmentMeta['path'] ?? null,
            'attachment_name' => $attachmentMeta['name'] ?? null,
            'attachment_mime' => $attachmentMeta['mime'] ?? null,
            'attachment_size' => $attachmentMeta['size'] ?? null,
            'delay_min_seconds' => $delayMinSeconds,
            'delay_max_seconds' => $delayMaxSeconds,
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

    public function storeUploadedAttachment(UploadedFile $file, string $directory): array
    {
        $directory = trim($directory, '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $path = Storage::disk('public')->putFileAs($directory, $file, $fileName);

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'url' => Storage::disk('public')->url($path),
        ];
    }

    public function templateAttachmentMeta(?BulkWhatsAppTemplate $template): array
    {
        if (!$template || blank($template->attachment_path)) {
            return [];
        }

        return [
            'path' => $template->attachment_path,
            'name' => $template->attachment_name ?: basename($template->attachment_path),
            'mime' => $template->attachment_mime ?: 'application/octet-stream',
            'size' => (int) ($template->attachment_size ?: 0),
            'url' => $template->attachment_url,
        ];
    }

    public function buildGatewayAttachmentFromCampaign(BulkWhatsAppCampaign $campaign): ?array
    {
        if (blank($campaign->attachment_path)) {
            return null;
        }

        if (!Storage::disk('public')->exists($campaign->attachment_path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($campaign->attachment_path);

        return [
            'data' => base64_encode($binary),
            'mimeType' => $campaign->attachment_mime ?: 'image/jpeg',
            'fileName' => $campaign->attachment_name ?: basename($campaign->attachment_path),
            'sendAsDocument' => false,
        ];
    }

    public function normalizeDelayForCampaign(BulkWhatsAppCampaign $campaign): int
    {
        $minSeconds = (int) ($campaign->delay_min_seconds ?: 8);
        $maxSeconds = (int) ($campaign->delay_max_seconds ?: 20);
        [$minSeconds, $maxSeconds] = $this->normalizeDelayWindow($minSeconds, $maxSeconds);

        return random_int($minSeconds, $maxSeconds);
    }

    private function normalizeAttachmentMeta(array $attachment = []): array
    {
        $path = trim((string) ($attachment['path'] ?? ''));
        if ($path === '') {
            return [];
        }

        return [
            'path' => $path,
            'name' => trim((string) ($attachment['name'] ?? '')) ?: basename($path),
            'mime' => trim((string) ($attachment['mime'] ?? '')) ?: 'application/octet-stream',
            'size' => max(0, (int) ($attachment['size'] ?? 0)),
            'url' => trim((string) ($attachment['url'] ?? '')) ?: Storage::disk('public')->url($path),
        ];
    }

    private function templateAttachmentPayload(?BulkWhatsAppTemplate $template): ?array
    {
        $meta = $this->templateAttachmentMeta($template);

        if ($meta === []) {
            return null;
        }

        return [
            'url' => $meta['url'],
            'fileName' => $meta['name'],
            'mimeType' => $meta['mime'],
        ];
    }

    private function normalizeDelayWindow(int $minSeconds, int $maxSeconds): array
    {
        $minSeconds = max(1, min(300, $minSeconds));
        $maxSeconds = max($minSeconds, min(600, $maxSeconds));

        return [$minSeconds, $maxSeconds];
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
