<?php

namespace App\Channels;

use App\Models\WhatsappNotificationSetting;
use App\Services\WhatsAppGatewayService;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function send($notifiable, $notification): ?array
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return null;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $notifiable->company_id)->first();

        if (!$setting || $setting->status !== 'active') {
            return null;
        }

        $payload = $notification->toWhatsApp($notifiable);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return null;
        }

        $phone = $payload['phone'] ?? null;
        if (blank($phone) && method_exists($notifiable, 'routeNotificationForWhatsApp')) {
            $phone = $notifiable->routeNotificationForWhatsApp($notification);
        }

        if (blank($phone)) {
            return null;
        }

        $sessionKey = $setting->resolved_whatsapp_session_key;
        $sent = $this->gatewayService->sendMessage($phone, $message, $sessionKey);

        if (!$sent) {
            $error = (string) ($this->gatewayService->getLastError() ?: 'WhatsApp bridge send failed.');
            $this->recordFailure($setting, $error, $phone);

            Log::warning('WhatsApp bridge notification delivery failed.', [
                'company_id' => $setting->company_id,
                'phone' => $phone,
                'session_key' => $sessionKey,
                'error' => $error,
            ]);

            return null;
        }

        $this->recordSuccess($setting, $phone);

        return [
            'success' => true,
            'data' => $this->gatewayService->getLastResponseData(),
        ];
    }

    private function recordSuccess(WhatsappNotificationSetting $setting, string $phone): void
    {
        $setting->forceFill([
            'last_send_status' => 'sent',
            'last_error_message' => null,
            'last_http_status' => $this->gatewayService->getLastHttpStatus() ?: 200,
            'last_response_body' => $this->encodeResponse($this->gatewayService->getLastResponseData()),
            'last_sent_at' => now(),
            'last_normalized_phone' => preg_replace('/\D+/', '', $phone),
            'last_response_message' => null,
            'last_delivery_status' => 'sent',
        ])->saveQuietly();
    }

    private function recordFailure(WhatsappNotificationSetting $setting, string $error, string $phone): void
    {
        $setting->forceFill([
            'last_send_status' => 'failed',
            'last_error_message' => $error,
            'last_http_status' => $this->gatewayService->getLastHttpStatus(),
            'last_response_body' => $this->encodeResponse($this->gatewayService->getLastResponseData()),
            'last_sent_at' => now(),
            'last_normalized_phone' => preg_replace('/\D+/', '', $phone),
            'last_response_message' => $error,
            'last_delivery_status' => 'failed',
        ])->saveQuietly();
    }

    private function encodeResponse(?array $responseData): ?string
    {
        if ($responseData === null) {
            return null;
        }

        return json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
