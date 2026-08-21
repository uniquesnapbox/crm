<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    private ?string $lastError = null;
    private ?array $lastResponseData = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastResponseData(): ?array
    {
        return $this->lastResponseData;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp_service.base_url'))
            && filled(config('services.whatsapp_service.api_key'));
    }

    public function sendMessage(string $mobile, string $message, ?string $sessionKey = null, ?array $attachment = null): bool
    {
        $this->lastResponseData = null;
        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $session = $this->resolveSessionKey($sessionKey);
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));
        $phone = preg_replace('/\D+/', '', $mobile);
        $payload = [
            'to' => $phone,
            'message' => trim($message),
            'channelKey' => $session,
            'idempotencyKey' => $this->createIdempotencyKey($phone, $session, $message),
        ];

        if (!empty($attachment)) {
            $payload['attachment'] = $attachment;
        }

        if ($baseUrl === '' || $apiKey === '') {
            $this->lastError = 'WhatsApp service is not configured.';
            return false;
        }

        if ($phone === '') {
            $this->lastError = 'Lead mobile number is invalid.';
            return false;
        }

        $attempts = 3;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->client($baseUrl, $apiKey, $timeout)
                    ->post('/messages/send', $payload);
            } catch (ConnectionException $exception) {
                $this->lastError = 'WhatsApp service connection failed: ' . $exception->getMessage();
                Log::warning('WhatsApp gateway connection failed.', [
                    'mobile' => $phone,
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                if ($attempt < $attempts && $this->isRetryableError($this->lastError)) {
                    usleep($this->retryDelayMicros($attempt));
                    continue;
                }

                return false;
            } catch (\Throwable $exception) {
                $this->lastError = $exception->getMessage();
                Log::warning('WhatsApp gateway send exception.', [
                    'mobile' => $phone,
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                if ($attempt < $attempts && $this->isRetryableError($this->lastError)) {
                    usleep($this->retryDelayMicros($attempt));
                    continue;
                }

                return false;
            }

            $json = $response->json();

            if ($response->successful() && ($json['success'] ?? false) === true) {
                $this->lastError = null;
                $this->lastResponseData = is_array($json['data'] ?? null) ? $json['data'] : null;
                return true;
            }

            $this->lastError = (string) ($json['error'] ?? ($response->body() ?: 'WhatsApp service send failed.'));

            Log::warning('WhatsApp gateway send failed.', [
                'mobile' => $phone,
                'attempt' => $attempt,
                'status' => $response->status(),
                'response' => $json ?: $response->body(),
            ]);

            if ($attempt < $attempts && $this->isRetryableError($this->lastError, $response)) {
                usleep($this->retryDelayMicros($attempt));
                continue;
            }

            return false;
        }

        return false;
    }

    public function getMessageHistory(string $mobile, ?string $sessionKey = null, int $limit = 100): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $session = $this->resolveSessionKey($sessionKey);
        $phone = preg_replace('/\D+/', '', $mobile);
        $timeout = max(10, min(60, (int) config('services.whatsapp_service.timeout', 30)));

        if ($baseUrl === '' || $apiKey === '' || $phone === '') {
            return ['success' => false, 'error' => 'WhatsApp history service is not configured.', 'data' => null];
        }

        try {
            $response = $this->client($baseUrl, $apiKey, $timeout)
                ->get('/messages/history', [
                    'to' => $phone,
                    'channelKey' => $session,
                    'limit' => max(1, min(200, $limit)),
                ]);

            return $this->mapJsonResponse($response, 'Unable to read WhatsApp message history.');
        } catch (ConnectionException $exception) {
            return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
        }
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

    public function getQr(?string $sessionKey = null, bool $forceRefresh = false): array
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
                    'refresh' => $forceRefresh ? 1 : 0,
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

    private function createIdempotencyKey(string $mobile, string $session, string $message): string
    {
        try {
            $nonce = bin2hex(random_bytes(16));
        } catch (\Throwable) {
            $nonce = uniqid('', true);
        }

        return hash('sha256', $mobile . '|' . $session . '|' . mb_substr(trim($message), 0, 512) . '|' . $nonce);
    }

    private function isRetryableError(string $error, ?Response $response = null): bool
    {
        $normalized = strtolower($error);

        if ($response && in_array($response->status(), [408, 425, 429, 500, 502, 503, 504], true)) {
            return true;
        }

        foreach ([
            'session not ready',
            'not ready',
            'timeout',
            'curl error 28',
            'connection failed',
            'connection reset',
            'temporarily unavailable',
            'cannot read properties of undefined',
            'no lid for user',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function retryDelayMicros(int $attempt): int
    {
        return match ($attempt) {
            1 => 500000,
            2 => 1500000,
            default => 2000000,
        };
    }
}
