<?php

namespace App\Modules\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;

    public function __construct()
    {
        $this->apiUrl = (string) config('whatsapp.api_url');
        $this->token = (string) config('whatsapp.token');
    }

    public function sendMessage($number, $message): array
    {
        if (empty($this->apiUrl) || empty($this->token) || empty($number) || empty($message)) {
            return [
                'status' => false,
                'message' => 'WhatsApp configuration or payload missing.',
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(20)
                ->post(rtrim($this->apiUrl, '/') . '/send-message', [
                    'number' => $number,
                    'message' => $message,
                ]);

            return $response->json() ?? [
                'status' => $response->successful(),
                'message' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

