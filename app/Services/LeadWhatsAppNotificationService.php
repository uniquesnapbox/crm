<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Support\Facades\Log;

class LeadWhatsAppNotificationService
{
    public function __construct(
        private WhatsAppGatewayService $gatewayService
    )
    {
    }

    public function sendLeadCreatedMessage(Lead $lead): void
    {
        $setting = WhatsappNotificationSetting::where('company_id', $lead->company_id)->first();

        if (!$setting) {
            return;
        }

        if (blank($lead->mobile)) {
            $lead->forceFill([
                'whatsapp_greeting_status' => 'failed',
                'whatsapp_greeting_error' => 'Lead mobile number is missing.',
                'whatsapp_greeting_sent_at' => null,
            ])->saveQuietly();

            return;
        }

        $message = $this->renderTemplate(
            $setting->lead_created_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE,
            $lead
        );

        if ($message === '') {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;
        $sent = $this->gatewayService->sendMessage($lead->mobile, $message, $senderNumber);
        $error = $this->gatewayService->getLastError();

        if ($sent) {
            $lead->forceFill([
                'whatsapp_greeting_status' => 'sent',
                'whatsapp_greeting_error' => null,
                'whatsapp_greeting_sent_at' => now(),
            ])->saveQuietly();
        } else {
            $lead->forceFill([
                'whatsapp_greeting_status' => 'failed',
                'whatsapp_greeting_error' => $error,
                'whatsapp_greeting_sent_at' => null,
            ])->saveQuietly();

            Log::warning('Lead WhatsApp notification failed.', [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
                'mobile' => $lead->mobile,
                'sender_number' => $senderNumber,
                'error' => $error,
            ]);
        }
    }

    private function renderTemplate(string $template, Lead $lead): string
    {
        $placeholders = [
            '{{client_name}}' => (string) $lead->client_name,
            '{{company_name}}' => (string) ($lead->company_name ?: optional($lead->company)->company_name ?: ''),
            '{{email}}' => (string) $lead->client_email,
            '{{mobile}}' => (string) $lead->mobile,
            '{{lead_id}}' => (string) $lead->id,
            '{{created_by}}' => (string) optional($lead->addedBy)->name,
        ];

        return trim(strtr($template, $placeholders));
    }
}
