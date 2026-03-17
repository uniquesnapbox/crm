<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappNotificationSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WascriptService
{
    public function sendText(string $phone, string $message, WhatsappNotificationSetting $setting): array
    {
        $normalizedPhone = $this->normalizePhone($phone, $setting->default_country_code);

        if ($normalizedPhone === '') {
            $message = 'WhatsApp phone number is invalid. Please use a valid number with country code.';
            $this->recordFailure($setting, null, ['phone' => $normalizedPhone], null, $message);

            throw new WascriptException($message);
        }

        $baseUrl = rtrim((string) $setting->base_url, '/');
        $token = trim((string) $setting->api_token);

        if ($baseUrl === '' || $token === '') {
            $message = 'WhatsApp API configuration is incomplete.';
            $this->recordFailure($setting, null, ['phone' => $normalizedPhone], null, $message);

            throw new WascriptException($message);
        }

        $payload = [
            'phone' => $normalizedPhone,
            'message' => trim($message),
        ];

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post('/api/enviar-texto/' . $token, $payload);
        } catch (ConnectionException $exception) {
            $readableMessage = 'WhatsApp request failed. Please check the Wascript server connection.';
            $this->recordFailure($setting, null, $payload, null, $readableMessage, $exception->getMessage());

            throw new WascriptException($readableMessage);
        }

        $responseBody = $response->json();
        $responseData = is_array($responseBody) ? $responseBody : ['raw' => $response->body()];

        if ($response->failed()) {
            $readableMessage = $this->resolveReadableError($responseData, $response->status());
            $this->recordFailure($setting, $response->status(), $payload, $responseData, $readableMessage);

            throw new WascriptException($readableMessage, $response->status(), $responseData);
        }

        if (array_key_exists('success', $responseData) && $responseData['success'] === false) {
            $readableMessage = $this->resolveReadableError($responseData, $response->status());
            $this->recordFailure($setting, $response->status(), $payload, $responseData, $readableMessage);

            throw new WascriptException($readableMessage, $response->status(), $responseData);
        }

        $this->recordSuccess($setting, $response->status(), $payload, $responseData);

        return $responseData ?: ['success' => true];
    }

    public function normalizePhone(?string $phone, ?string $defaultCountryCode = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $countryCode = preg_replace('/\D+/', '', (string) $defaultCountryCode);

        if ($digits === '') {
            return '';
        }

        if ($countryCode === '') {
            return $digits;
        }

        $localNumber = ltrim($digits, '0');

        if ($localNumber === '') {
            return '';
        }

        if (str_starts_with($localNumber, $countryCode)) {
            return $localNumber;
        }

        return $countryCode . $localNumber;
    }

    private function resolveReadableError(array $responseData, ?int $httpStatus = null): string
    {
        $message = trim((string) ($responseData['message'] ?? $responseData['error'] ?? ''));
        $normalizedMessage = mb_strtolower($message);

        if ($normalizedMessage !== '') {
            if (str_contains($normalizedMessage, 'token') && (str_contains($normalizedMessage, 'não cadastrado') || str_contains($normalizedMessage, 'nao cadastrado') || str_contains($normalizedMessage, 'invalid'))) {
                return 'Token invalid or not registered in Wascript.';
            }

            if (str_contains($normalizedMessage, 'telefone') || str_contains($normalizedMessage, 'phone') || str_contains($normalizedMessage, 'número') || str_contains($normalizedMessage, 'numero')) {
                return 'Phone number is invalid. Please use digits only with country code.';
            }

            if (str_contains($normalizedMessage, 'desconect') || str_contains($normalizedMessage, 'not connected') || str_contains($normalizedMessage, 'disconnected')) {
                return 'WhatsApp is disconnected in Wascript. Please reconnect the session and try again.';
            }

            return $message;
        }

        if ($httpStatus === 401 || $httpStatus === 403 || $httpStatus === 404) {
            return 'WhatsApp API authentication failed. Please check the Wascript token.';
        }

        return 'WhatsApp request failed. Please review the API configuration and try again.';
    }

    private function recordSuccess(
        WhatsappNotificationSetting $setting,
        ?int $httpStatus,
        array $payload,
        array $responseData
    ): void {
        $this->logAttempt('info', $setting, $payload, $httpStatus, $responseData);

        $setting->forceFill([
            'last_send_status' => 'success',
            'last_error_message' => null,
            'last_http_status' => $httpStatus,
            'last_response_body' => $this->encodeResponse($responseData),
            'last_sent_at' => now(),
        ])->save();
    }

    private function recordFailure(
        WhatsappNotificationSetting $setting,
        ?int $httpStatus,
        array $payload,
        mixed $responseData,
        string $readableMessage,
        ?string $transportMessage = null
    ): void {
        $context = [
            'readable_error' => $readableMessage,
        ];

        if ($transportMessage) {
            $context['transport_error'] = $transportMessage;
        }

        $this->logAttempt('error', $setting, $payload, $httpStatus, $responseData, $context);

        $setting->forceFill([
            'last_send_status' => 'failed',
            'last_error_message' => $readableMessage,
            'last_http_status' => $httpStatus,
            'last_response_body' => $this->encodeResponse($responseData),
            'last_sent_at' => now(),
        ])->save();
    }

    private function logAttempt(
        string $level,
        WhatsappNotificationSetting $setting,
        array $payload,
        ?int $httpStatus,
        mixed $responseData,
        array $extraContext = []
    ): void {
        Log::$level('Wascript WhatsApp request completed.', array_merge([
            'company_id' => $setting->company_id,
            'base_url' => rtrim((string) $setting->base_url, '/'),
            'request_payload' => $payload,
            'http_status' => $httpStatus,
            'response_body' => $responseData,
        ], $extraContext));
    }

    private function encodeResponse(mixed $responseData): ?string
    {
        if ($responseData === null || $responseData === '') {
            return null;
        }

        if (is_string($responseData)) {
            return $responseData;
        }

        return json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
