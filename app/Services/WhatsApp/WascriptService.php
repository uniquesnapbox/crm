<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappNotificationSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WascriptService
{
    public function sendText(string $phone, string $message, WhatsappNotificationSetting $setting): array
    {
        $normalizedPhone = $this->normalizePhone($phone, $setting->default_country_code);

        if ($normalizedPhone === '') {
            throw new \InvalidArgumentException('WhatsApp phone number is invalid.');
        }

        $baseUrl = rtrim((string) $setting->base_url, '/');
        $token = trim((string) $setting->api_token);

        if ($baseUrl === '' || $token === '') {
            throw new \InvalidArgumentException('WhatsApp API configuration is incomplete.');
        }

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('/api/enviar-texto/' . $token, [
                'phone' => $normalizedPhone,
                'message' => $message,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json() ?: ['success' => true];
    }

    public function normalizePhone(?string $phone, ?string $defaultCountryCode = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $countryCode = preg_replace('/\D+/', '', (string) $defaultCountryCode);

        if ($digits === '') {
            return '';
        }

        if ($countryCode !== '' && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;
        }

        return $digits;
    }
}
