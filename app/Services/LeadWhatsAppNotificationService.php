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
        $setting = $this->settings($lead);

        if (!$setting || !$setting->isLeadCreatedMessageEnabled()) {
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

        $sent = $this->sendUsingTemplate(
            $lead,
            $setting,
            $setting->lead_created_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE
        );
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
                'sender_number' => $setting->resolved_lead_created_sender_number,
                'error' => $error,
            ]);
        }
    }

    public function sendLeadProductInterestMessage(Lead $lead): void
    {
        $setting = $this->settings($lead);

        if (!$setting || !$setting->isLeadInterestMessageEnabled() || blank($lead->products_services) || blank($lead->mobile)) {
            return;
        }

        $sent = $this->sendUsingTemplate(
            $lead,
            $setting,
            $setting->lead_interest_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_INTEREST_TEMPLATE
        );

        if (!$sent) {
            Log::warning('Lead product-interest WhatsApp notification failed.', [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
                'mobile' => $lead->mobile,
                'sender_number' => $setting->resolved_lead_created_sender_number,
                'error' => $this->gatewayService->getLastError(),
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
            '{{products_services}}' => (string) ($lead->products_services ?: ''),
        ];

        return trim(strtr($template, $placeholders));
    }

    private function settings(Lead $lead): ?WhatsappNotificationSetting
    {
        $setting = WhatsappNotificationSetting::where('company_id', $lead->company_id)->first();

        if (!$setting || $setting->status !== 'active') {
            return null;
        }

        return $setting;
    }

    private function sendUsingTemplate(Lead $lead, WhatsappNotificationSetting $setting, string $template): bool
    {
        $message = $this->renderTemplate($template, $lead);

        if ($message === '') {
            return false;
        }

        return $this->gatewayService->sendMessage(
            (string) $lead->mobile,
            $message,
            $setting->resolved_lead_created_sender_number
        );
    }
}
