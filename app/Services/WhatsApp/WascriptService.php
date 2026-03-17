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
            $this->recordFailure($setting, null, ['phone' => $normalizedPhone], null, $message, null, $normalizedPhone);

            throw new WascriptException($message, null, null, $normalizedPhone);
        }

        $baseUrl = rtrim((string) $setting->base_url, '/');
        $token = trim((string) $setting->api_token);

        if ($baseUrl === '' || $token === '') {
            $message = 'WhatsApp API configuration is incomplete.';
            $this->recordFailure($setting, null, ['phone' => $normalizedPhone], null, $message, null, $normalizedPhone);

            throw new WascriptException($message, null, null, $normalizedPhone);
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
            $this->recordFailure($setting, null, $payload, null, $readableMessage, $exception->getMessage(), $normalizedPhone);

            throw new WascriptException($readableMessage, null, null, $normalizedPhone);
        }

        $responseBody = $response->json();
        $responseData = is_array($responseBody) ? $responseBody : ['raw' => $response->body()];

        if ($response->failed()) {
            $readableMessage = $this->resolveReadableError($responseData, $response->status());
            $this->recordFailure($setting, $response->status(), $payload, $responseData, $readableMessage, null, $normalizedPhone);

            throw new WascriptException($readableMessage, $response->status(), $responseData, $normalizedPhone);
        }

        if (($responseData['success'] ?? null) !== true) {
            $readableMessage = $this->resolveReadableError($responseData, $response->status());
            $this->recordFailure($setting, $response->status(), $payload, $responseData, $readableMessage, null, $normalizedPhone);

            throw new WascriptException($readableMessage, $response->status(), $responseData, $normalizedPhone);
        }

        $deliveryStatus = $this->resolveDeliveryStatus($responseData);
        $responseMessage = $this->extractResponseMessage($responseData);
        $this->recordSuccess($setting, $response->status(), $payload, $responseData, $normalizedPhone, $responseMessage, $deliveryStatus);

        return array_merge($responseData ?: ['success' => true], [
            '_crm' => [
                'normalized_phone' => $normalizedPhone,
                'response_message' => $responseMessage,
                'delivery_status' => $deliveryStatus,
            ],
        ]);
    }

    public function normalizePhone(?string $phone, ?string $defaultCountryCode = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $countryCode = preg_replace('/\D+/', '', (string) $defaultCountryCode);

        if ($digits === '' || $countryCode === '') {
            return '';
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
        $message = $this->extractResponseMessage($responseData);
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
        array $responseData,
        string $normalizedPhone,
        ?string $responseMessage,
        string $deliveryStatus
    ): void {
        $this->logAttempt('info', $setting, $payload, $httpStatus, $responseData);

        $setting->forceFill([
            'last_send_status' => $deliveryStatus,
            'last_error_message' => null,
            'last_http_status' => $httpStatus,
            'last_response_body' => $this->encodeResponse($responseData),
            'last_sent_at' => now(),
            'last_normalized_phone' => $normalizedPhone,
            'last_response_message' => $responseMessage,
            'last_delivery_status' => $deliveryStatus,
        ])->save();
    }

    private function recordFailure(
        WhatsappNotificationSetting $setting,
        ?int $httpStatus,
        array $payload,
        mixed $responseData,
        string $readableMessage,
        ?string $transportMessage = null,
        ?string $normalizedPhone = null
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
            'last_normalized_phone' => $normalizedPhone,
            'last_response_message' => $this->extractResponseMessage($responseData),
            'last_delivery_status' => 'failed',
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

    private function extractResponseMessage(mixed $responseData): ?string
    {
        if (is_array($responseData)) {
            $message = $responseData['message'] ?? $responseData['error'] ?? $responseData['detail'] ?? null;

            return $message !== null ? trim((string) $message) : null;
        }

        if (is_string($responseData)) {
            return trim($responseData);
        }

        return null;
    }

    private function resolveDeliveryStatus(array $responseData): string
    {
        $status = mb_strtolower((string) ($responseData['status'] ?? $responseData['delivery_status'] ?? ''));

        if (in_array($status, ['sent', 'delivered', 'enviado', 'entregue'], true)) {
            return 'sent';
        }

        if (
            array_key_exists('delivered', $responseData) && $responseData['delivered'] === true
            || array_key_exists('sent', $responseData) && $responseData['sent'] === true
        ) {
            return 'sent';
        }

        if (
            array_key_exists('queue_id', $responseData)
            || array_key_exists('message_id', $responseData)
            || array_key_exists('id', $responseData)
        ) {
            return 'accepted';
        }

        return 'accepted';
    }
}
