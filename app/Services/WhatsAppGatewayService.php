<?php

namespace App\Services;

use App\Models\WhatsappNotificationSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    private ?string $lastError = null;
    private ?array $lastResponseData = null;
    private ?int $lastHttpStatus = null;
    private ?int $companyId = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastResponseData(): ?array
    {
        return $this->lastResponseData;
    }

    public function getLastHttpStatus(): ?int
    {
        return $this->lastHttpStatus;
    }

    public function forCompany(?int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function shouldRetryLastFailure(): bool
    {
        if ($this->isPermanentRecipientError((string) $this->lastError)) {
            return false;
        }

        if (in_array($this->lastHttpStatus, [408, 425, 429, 500, 502, 503, 504], true)) {
            return true;
        }

        return $this->isRetryableError((string) $this->lastError);
    }

    public function isConfigured(): bool
    {
        $connection = $this->connectionConfig();
        $configured = filled($connection['base_url']);

        if ($this->usesWaha()) {
            return $configured && filled($connection['api_key']);
        }

        return $configured;
    }

    public function connectionDetails(): array
    {
        $connection = $this->connectionConfig();

        return [
            'driver' => $connection['driver'],
            'baseUrl' => $connection['base_url'],
            'session' => $connection['session'],
            'apiKeyConfigured' => filled($connection['api_key']),
        ];
    }

    public function sendMessage(
        string $mobile,
        string $message,
        ?string $sessionKey = null,
        ?array $attachment = null,
        ?string $idempotencySeed = null,
        ?int $companyId = null
    ): bool
    {
        $this->companyId = $companyId;

        if ($this->usesWaha()) {
            return $this->sendWahaMessage($mobile, $message, $sessionKey, $attachment);
        }

        $this->lastResponseData = null;
        $this->lastHttpStatus = null;
        $connection = $this->connectionConfig();
        $baseUrl = rtrim($connection['base_url'], '/');
        $apiKey = $connection['api_key'];
        $session = $this->resolveSessionKey($sessionKey);
        $timeout = $connection['timeout'];
        $timeout = max(10, min(60, $timeout));
        $phone = $this->normalizePhone($mobile);
        $payload = [
            'to' => $phone,
            'message' => trim($message),
            'channelKey' => $session,
            'idempotencyKey' => $this->createIdempotencyKey($phone, $session, $message, $idempotencySeed),
        ];

        if (!empty($attachment)) {
            $payload['attachment'] = $attachment;
        }

        if ($baseUrl === '') {
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
            $this->lastHttpStatus = $response->status();

            if ($response->successful() && ($json['success'] ?? false) === true) {
                $this->lastError = null;
                $this->lastResponseData = is_array($json['data'] ?? null) ? $json['data'] : null;
                return true;
            }

            $this->lastError = $this->errorMessage(
                $json['error'] ?? null,
                $response->body() ?: 'WhatsApp service send failed.'
            );

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

    /**
     * Send through the documented WAHA API. The API key remains on the
     * server because this method is only called by Laravel services/jobs.
     */
    private function sendWahaMessage(
        string $mobile,
        string $message,
        ?string $sessionKey = null,
        ?array $attachment = null
    ): bool {
        $this->lastError = null;
        $this->lastResponseData = null;
        $this->lastHttpStatus = null;

        $connection = $this->connectionConfig();
        $baseUrl = rtrim($connection['base_url'], '/');
        $apiKey = $connection['api_key'];
        $session = $this->resolveSessionKey($sessionKey);
        $phone = $this->normalizePhone($mobile);

        if ($baseUrl === '' || $apiKey === '') {
            $this->lastError = 'WAHA API URL or API key is not configured.';
            return false;
        }

        if ($phone === '') {
            $this->lastError = 'Lead mobile number is invalid.';
            return false;
        }

        $payload = [
            'session' => $session,
            'chatId' => $phone . '@c.us',
        ];
        $endpoint = '/api/sendText';

        if (!empty($attachment) && !empty($attachment['data'])) {
            $mimeType = (string) ($attachment['mimeType'] ?? 'application/octet-stream');
            $isImage = str_starts_with(strtolower($mimeType), 'image/');
            $endpoint = $isImage && empty($attachment['sendAsDocument'])
                ? '/api/sendImage'
                : '/api/sendFile';
            $payload['file'] = [
                'data' => (string) $attachment['data'],
                'mimetype' => $mimeType,
                'filename' => (string) ($attachment['fileName'] ?? 'attachment'),
            ];
            $payload['caption'] = trim($message);
        } else {
            $payload['text'] = trim($message);
        }

        try {
            $response = $this->client($baseUrl, $apiKey, $connection['timeout'])
                ->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            $this->lastError = 'WAHA connection failed: ' . $exception->getMessage();
            return false;
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }

        $this->lastHttpStatus = $response->status();
        $json = $response->json();
        $this->lastResponseData = is_array($json) ? $json : ['response' => $response->body()];

        if ($response->successful()) {
            return true;
        }

        $this->lastError = $this->errorMessage(
            $json['error'] ?? null,
            $response->body() ?: 'WAHA message send failed.'
        );
        return false;
    }

    public function getMessageHistory(string $mobile, ?string $sessionKey = null, int $limit = 100): array
    {
        if ($this->usesWaha()) {
            $connection = $this->connectionConfig();
            $baseUrl = rtrim($connection['base_url'], '/');
            $apiKey = $connection['api_key'];
            $session = $this->resolveSessionKey($sessionKey);
            $phone = $this->normalizePhone($mobile);

            if ($baseUrl === '' || $apiKey === '' || $phone === '') {
                return ['success' => false, 'error' => 'WAHA history service is not configured.', 'data' => null];
            }

            try {
                $response = $this->client($baseUrl, $apiKey, $connection['timeout'])
                    ->get('/api/' . rawurlencode($session) . '/chats/' . rawurlencode($phone . '@c.us') . '/messages', [
                        'limit' => max(1, min(200, $limit)),
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $messages = is_array($json) && array_is_list($json)
                        ? $json
                        : (is_array($json['messages'] ?? null)
                            ? $json['messages']
                            : (is_array(data_get($json, 'data.messages')) ? data_get($json, 'data.messages') : []));

                    return [
                        'success' => true,
                        'error' => null,
                        'data' => ['messages' => $messages],
                    ];
                }

                return $this->mapJsonResponse($response, 'Unable to read WAHA message history.');
            } catch (ConnectionException $exception) {
                return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
            } catch (\Throwable $exception) {
                return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
            }
        }

        $connection = $this->connectionConfig();
        $baseUrl = rtrim($connection['base_url'], '/');
        $apiKey = $connection['api_key'];
        $session = $this->resolveSessionKey($sessionKey);
        $phone = $this->normalizePhone($mobile);
        $timeout = $connection['timeout'];

        if ($baseUrl === '' || $phone === '') {
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
        if ($this->usesWaha()) {
            return $this->getWahaHealth();
        }

        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));

        if ($baseUrl === '') {
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

    /**
     * @param array<int, string> $sessionKeys
     */
    public function hasReadySession(array $sessionKeys): bool
    {
        if ($this->usesWaha()) {
            $health = $this->getWahaHealth();
            $wantedSessions = array_values(array_unique(array_filter(array_map(
                fn ($sessionKey) => $this->resolveSessionKey((string) $sessionKey),
                $sessionKeys
            ))));

            foreach (($health['data']['sessions'] ?? []) as $session) {
                if (is_array($session)
                    && in_array((string) ($session['sessionKey'] ?? ''), $wantedSessions, true)
                    && (string) ($session['status'] ?? '') === 'ready') {
                    $this->lastError = null;
                    return true;
                }
            }

            $this->lastError = $this->errorMessage($health['error'] ?? null, 'No configured WAHA session is ready.');
            return false;
        }

        $health = $this->getHealth();

        if (!($health['success'] ?? false)) {
            $this->lastError = $this->errorMessage($health['error'] ?? null, 'WhatsApp service is unavailable.');
            return false;
        }

        $wantedSessions = array_values(array_unique(array_filter(array_map(
            fn ($sessionKey) => $this->resolveSessionKey((string) $sessionKey),
            $sessionKeys
        ))));
        $sessions = $health['data']['sessions'] ?? [];

        foreach ($sessions as $session) {
            if (!is_array($session)) {
                continue;
            }

            $sessionKey = (string) ($session['sessionKey'] ?? '');
            $status = strtolower((string) ($session['status'] ?? ''));

            if (in_array($sessionKey, $wantedSessions, true)
                && $status === 'ready') {
                $this->lastError = null;
                return true;
            }
        }

        $this->lastError = 'No configured WhatsApp session is ready.';
        return false;
    }

    public function getQr(?string $sessionKey = null, bool $forceRefresh = false): array
    {
        if ($this->usesWaha()) {
            return $this->getWahaQr($sessionKey);
        }

        $baseUrl = rtrim((string) config('services.whatsapp_service.base_url'), '/');
        $apiKey = (string) config('services.whatsapp_service.api_key');
        $timeout = (int) config('services.whatsapp_service.timeout', 30);
        $timeout = max(10, min(60, $timeout));
        $session = $this->resolveSessionKey($sessionKey);

        if ($baseUrl === '') {
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

    private function connectionConfig(): array
    {
        $connection = [
            'driver' => (string) config('services.whatsapp_service.driver', 'bridge'),
            'base_url' => (string) config('services.whatsapp_service.base_url', ''),
            'api_key' => (string) config('services.whatsapp_service.api_key', ''),
            'session' => trim((string) config('services.whatsapp_service.session', 'default')) ?: 'default',
            'timeout' => max(10, min(60, (int) config('services.whatsapp_service.timeout', 30))),
        ];

        try {
            $company = function_exists('company') ? company() : null;
            $companyId = $this->companyId ?: $company?->id;

            if ($companyId) {
                $setting = WhatsappNotificationSetting::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->first();

                if ($setting) {
                    $connection['driver'] = trim((string) ($setting->api_driver ?? '')) ?: $connection['driver'];
                    $connection['base_url'] = trim((string) ($setting->base_url ?? '')) ?: $connection['base_url'];
                    $connection['api_key'] = trim((string) ($setting->api_token ?? '')) ?: $connection['api_key'];
                    $connection['session'] = trim((string) ($setting->api_session ?? '')) ?: $connection['session'];
                }
            }
        } catch (\Throwable) {
            // Environment configuration remains the safe fallback for jobs
            // running without a current company context.
        }

        return $connection;
    }

    private function usesWaha(): bool
    {
        return strtolower((string) $this->connectionConfig()['driver']) === 'waha';
    }

    private function getWahaHealth(): array
    {
        $connection = $this->connectionConfig();
        $baseUrl = rtrim($connection['base_url'], '/');
        $apiKey = $connection['api_key'];
        $session = $this->resolveSessionKey();
        $timeout = $connection['timeout'];

        if ($baseUrl === '' || $apiKey === '') {
            return ['success' => false, 'error' => 'WAHA API URL or API key is not configured.', 'data' => null];
        }

        try {
            $versionResponse = $this->client($baseUrl, $apiKey, $timeout)->get('/api/version');
            $sessionResponse = $this->ensureWahaSession($baseUrl, $apiKey, $timeout, $session);
        } catch (ConnectionException $exception) {
            return ['success' => false, 'error' => 'WAHA connection failed: ' . $exception->getMessage(), 'data' => null];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
        }

        $version = $versionResponse->json();
        $sessionData = $sessionResponse->json();
        $rawStatus = is_array($sessionData) ? (string) ($sessionData['status'] ?? '') : '';
        $status = $this->normalizeWahaStatus($rawStatus);

        if (!$versionResponse->successful() || !$sessionResponse->successful()) {
            $error = is_array($sessionData)
                ? ($sessionData['error'] ?? null)
                : null;
            $error = $this->errorMessage(
                $error,
                $sessionResponse->body() ?: $versionResponse->body() ?: 'Unable to read WAHA status.'
            );

            return [
                'success' => false,
                'error' => $error,
                'data' => [
                    'version' => $version,
                    'sessions' => [[
                        'sessionKey' => $session,
                        'status' => $status ?: 'unknown',
                    ]],
                ],
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'version' => $version,
                'sessions' => [[
                    'sessionKey' => $session,
                    'status' => $status ?: 'unknown',
                ]],
            ],
        ];
    }

    private function getWahaQr(?string $sessionKey = null): array
    {
        $connection = $this->connectionConfig();
        $baseUrl = rtrim($connection['base_url'], '/');
        $apiKey = $connection['api_key'];
        $session = $this->resolveSessionKey($sessionKey);
        $timeout = $connection['timeout'];

        if ($baseUrl === '' || $apiKey === '') {
            return ['success' => false, 'error' => 'WAHA API URL or API key is not configured.', 'data' => null];
        }

        try {
            $sessionResponse = $this->ensureWahaSession($baseUrl, $apiKey, $timeout, $session);
            $sessionJson = $sessionResponse->json();
            $sessionStatus = $this->normalizeWahaStatus((string) data_get($sessionJson, 'status', ''));

            if ($sessionResponse->successful() && $sessionStatus === 'ready') {
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'sessionKey' => $session,
                        'status' => 'ready',
                        'generatedAt' => null,
                        'qr' => null,
                    ],
                ];
            }

            $response = $this->client($baseUrl, $apiKey, $timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/' . rawurlencode($session) . '/auth/qr', ['format' => 'raw']);
            $json = $response->json();
            $value = is_array($json) ? (string) ($json['value'] ?? '') : '';

            return [
                'success' => $response->successful() && $value !== '',
                'error' => ($response->successful() && $value !== '')
                    ? null
                    : $this->errorMessage(
                        data_get($json, 'error'),
                        $response->body() ?: 'Unable to load WAHA QR.'
                    ),
                'data' => [
                    'sessionKey' => $session,
                    'status' => $sessionStatus ?: 'qr_required',
                    'generatedAt' => now()->toIso8601String(),
                    'qr' => $value !== '' ? $value : null,
                ],
            ];
        } catch (ConnectionException $exception) {
            return ['success' => false, 'error' => 'WAHA connection failed: ' . $exception->getMessage(), 'data' => null];
        } catch (\Throwable $exception) {
            return ['success' => false, 'error' => $exception->getMessage(), 'data' => null];
        }
    }

    private function normalizeWahaStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'WORKING' => 'ready',
            'SCAN_QR_CODE' => 'qr_required',
            'STARTING' => 'initializing',
            'STOPPED' => 'stopped',
            'FAILED' => 'failed',
            default => strtolower(trim($status)) ?: 'unknown',
        };
    }

    private function ensureWahaSession(string $baseUrl, string $apiKey, int $timeout, string $session): Response
    {
        $client = $this->client($baseUrl, $apiKey, $timeout);
        $response = $client->get('/api/sessions/' . rawurlencode($session));

        if ($response->status() === 404) {
            $client->post('/api/sessions', [
                'name' => $session,
            ]);
            $response = $client->get('/api/sessions/' . rawurlencode($session));
        }

        if ($response->successful() && strtoupper((string) data_get($response->json(), 'status', '')) === 'STOPPED') {
            $client->post('/api/sessions/' . rawurlencode($session) . '/start');
            $response = $client->get('/api/sessions/' . rawurlencode($session));
        }

        return $response;
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
            'error' => $success ? null : $this->errorMessage(
                $json['error'] ?? null,
                $response->body() ?: $fallbackError
            ),
            'data' => $json['data'] ?? null,
        ], $extra);
    }

    private function resolveSessionKey(?string $sessionKey = null): string
    {
        if ($this->usesWaha()) {
            $requested = trim((string) $sessionKey);
            $configured = $this->connectionConfig()['session'];

            if ($requested !== '' && !preg_match('/^\d+$/', $requested)) {
                return $requested;
            }

            return $configured !== '' ? $configured : 'default';
        }

        $normalized = preg_replace('/\D+/', '', (string) $sessionKey);

        if ($normalized !== '') {
            return $normalized;
        }

        $fallback = $this->connectionConfig()['session'];

        return $fallback !== '' ? $fallback : 'default';
    }

    private function isPermanentRecipientError(string $error): bool
    {
        $normalized = strtolower($error);

        foreach ([
            'no lid for user',
            'not on whatsapp',
            'invalid phone',
            'invalid recipient',
            'chat not found',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function errorMessage(mixed $error, string $fallback): string
    {
        if (is_string($error) && trim($error) !== '') {
            $message = trim($error);
            $decoded = json_decode($message, true);

            if (is_array($decoded)) {
                return $this->errorMessage($decoded, $fallback);
            }

            if (str_contains(strtolower($message), 'no lid for user')) {
                return 'WhatsApp recipient could not be found. Check that this number is registered on WhatsApp.';
            }

            return $message;
        }

        if (is_scalar($error) && (string) $error !== '') {
            return (string) $error;
        }

        if (is_array($error)) {
            $message = $error['message'] ?? $error['error'] ?? null;
            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }

            $encoded = json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }

        return trim($fallback) !== '' ? $fallback : 'WhatsApp service request failed.';
    }

    private function normalizePhone(string $mobile): string
    {
        $phone = preg_replace('/\D+/', '', $mobile);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        $defaultCountryCode = preg_replace(
            '/\D+/',
            '',
            (string) config('services.whatsapp_service.default_country_code', '91')
        );

        while ($defaultCountryCode !== '' && strlen($phone) > 10 && str_starts_with($phone, $defaultCountryCode)) {
            $phone = substr($phone, strlen($defaultCountryCode));
            $phone = ltrim($phone, '0');
        }

        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        if (strlen($phone) === 10 && $defaultCountryCode !== '') {
            $phone = $defaultCountryCode . $phone;
        }

        return $phone;
    }

    private function createIdempotencyKey(
        string $mobile,
        string $session,
        string $message,
        ?string $idempotencySeed = null
    ): string
    {
        if (filled($idempotencySeed)) {
            return hash('sha256', $mobile . '|' . $session . '|' . trim((string) $idempotencySeed));
        }

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
