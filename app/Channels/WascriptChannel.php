<?php

namespace App\Channels;

use App\Models\WhatsappNotificationSetting;
use App\Services\WhatsApp\WascriptService;

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

        return $this->service->sendText($phone, $payload['message'], $setting);
    }
}
