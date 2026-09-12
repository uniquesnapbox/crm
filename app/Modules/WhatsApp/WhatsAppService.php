<?php

namespace App\Modules\WhatsApp;

use App\Services\WhatsAppGatewayService;

class WhatsAppService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendMessage($number, $message): array
    {
        if (empty($number) || empty($message)) {
            return [
                'status' => false,
                'message' => 'WhatsApp configuration or payload missing.',
            ];
        }

        $sent = $this->gatewayService->sendMessage(
            (string) $number,
            (string) $message,
            config('services.whatsapp_service.session')
        );

        return [
            'status' => $sent,
            'success' => $sent,
            'message' => $sent
                ? 'WhatsApp message sent successfully.'
                : ($this->gatewayService->getLastError() ?: 'WhatsApp bridge send failed.'),
            'data' => $this->gatewayService->getLastResponseData(),
        ];
    }
}

