<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp_service.base_url'))
            && filled(config('services.whatsapp_service.api_key'));
    }

    public function sendMessage(string $mobile, string $message, ?string $sessionKey = null): bool
    {
        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $session = $this->resolveSessionKey($sessionKey);
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));
        $phone = preg_replace('/\D+/', '', $mobile);

        if ($baseUrl === '' || $apiKey === '') {
            $this->lastError = 'WhatsApp service is not configured.';
            return false;
        }

        if ($phone === '') {
            $this->lastError = 'Lead mobile number is invalid.';
            return false;
        }

        try {
            $response = $this->client($baseUrl, $apiKey, $timeout)
                ->post('/messages/send', [
                    'to' => $phone,
                    'message' => trim($message),
                    'channelKey' => $session,
                ]);
        } catch (ConnectionException $exception) {
            $this->lastError = 'WhatsApp service connection failed: ' . $exception->getMessage();
            Log::error('WhatsApp gateway connection failed.', [
                'mobile' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::error('WhatsApp gateway send exception.', [
                'mobile' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $json = $response->json();

        if ($response->successful() && ($json['success'] ?? false) === true) {
            $this->lastError = null;
            return true;
        }

        $this->lastError = (string) ($json['error'] ?? ($response->body() ?: 'WhatsApp service send failed.'));

        Log::error('WhatsApp gateway send failed.', [
            'mobile' => $phone,
            'status' => $response->status(),
            'response' => $json ?: $response->body(),
        ]);

        return false;
    }

    public function getHealth(): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));

        if ($baseUrl === '' || $apiKey === '') {
            return [
                'success' => false,
                'error' => 'WhatsApp service is not configured.',
                'data' => null,
            ];
        }

        try {
            $response = $this->client($baseUrl, $apiKey, $timeout, false)->get('/health');

            return $this->mapJsonResponse($response, 'Unable to read WhatsApp service health.');
        } catch (ConnectionException $exception) {
            return [
                'success' => false,
                'error' => 'WhatsApp service connection failed: ' . $exception->getMessage(),
                'data' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    public function getQr(?string $sessionKey = null): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));
        $session = $this->resolveSessionKey($sessionKey);

        if ($baseUrl === '' || $apiKey === '') {
            return [
                'success' => false,
                'error' => 'WhatsApp service is not configured.',
                'data' => null,
            ];
        }

        try {
            $response = $this->client($baseUrl, $apiKey, $timeout)
                ->get('/qr', [
                    'sessionKey' => $session,
                ]);

            return $this->mapJsonResponse($response, 'Unable to load WhatsApp QR.', [
                'requested_session' => $session,
            ]);
        } catch (ConnectionException $exception) {
            return [
                'success' => false,
                'error' => 'WhatsApp service connection failed: ' . $exception->getMessage(),
                'data' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    private function client(string $baseUrl, string $apiKey, int $timeout, bool $withApiKey = true)
    {
        $client = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);

        if ($withApiKey) {
            $client = $client->withHeaders([
                'x-api-key' => $apiKey,
            ]);
        }

        return $client;
    }

    private function mapJsonResponse(Response $response, string $fallbackError, array $extra = []): array
    {
        $json = $response->json();
        $success = $response->successful() && ($json['success'] ?? false) === true;

        return array_merge([
            'success' => $success,
            'error' => $success ? null : (string) ($json['error'] ?? ($response->body() ?: $fallbackError)),
            'data' => $json['data'] ?? null,
        ], $extra);
    }

    private function resolveSessionKey(?string $sessionKey = null): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $sessionKey);

        if ($normalized !== '') {
            return $normalized;
        }

        $fallback = trim((string) config('services.whatsapp_service.session', 'default'));

        return $fallback !== '' ? $fallback : 'default';
    }
}
