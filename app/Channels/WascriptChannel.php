<?php

namespace App\Channels;

use App\Models\WhatsappNotificationSetting;
use App\Services\WhatsApp\WascriptException;
use App\Services\WhatsApp\WascriptService;
use Illuminate\Support\Facades\Log;

class WascriptChannel
{
    public function __construct(private WascriptService $service)
    {
    }

    public function send($notifiable, $notification): ?array
    {
        if (!method_exists($notification, 'toWascript')) {
            return null;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $notifiable->company_id)->first();

        if (!$setting || $setting->status !== 'active') {
            return null;
        }

        $payload = $notification->toWascript($notifiable);

        if (empty($payload['message'])) {
            return null;
        }

        $phone = $payload['phone'] ?? $notifiable->routeNotificationForWascript($notification);

        if (empty($phone)) {
            return null;
        }

        try {
            return $this->service->sendText($phone, $payload['message'], $setting);
        } catch (WascriptException $exception) {
            Log::warning('Wascript notification delivery failed.', [
                'company_id' => $setting->company_id,
                'phone' => $phone,
                'message' => $exception->getMessage(),
                'http_status' => $exception->httpStatus(),
                'response_body' => $exception->responseBody(),
            ]);

            return null;
        }
    }
}
