<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadWhatsAppMessage;
use App\Models\WhatsappNotificationSetting;
use App\Scopes\CompanyScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappWebhookController extends Controller
{
    public function incoming(Request $request): JsonResponse
    {
        $expectedToken = trim((string) config('services.whatsapp_service.inbound_webhook_token'));
        $providedToken = trim((string) $request->header('X-WhatsApp-Webhook-Token'));

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        $payload = $request->validate([
            'sessionKey' => ['nullable', 'string', 'max:100'],
            'messageId' => ['nullable', 'string', 'max:191'],
            'from' => ['nullable', 'string', 'max:100'],
            'to' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:10000'],
            'contentType' => ['nullable', 'string', 'max:40'],
            'timestamp' => ['nullable', 'numeric'],
            'fromMe' => ['nullable', 'boolean'],
            'chatId' => ['nullable', 'string', 'max:191'],
            'hasMedia' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array'],
            'media.data' => ['nullable', 'string', 'max:10000000'],
            'media.mimeType' => ['nullable', 'string', 'max:100'],
            'media.fileName' => ['nullable', 'string', 'max:191'],
        ]);

        $fromMe = (bool) ($payload['fromMe'] ?? false);
        $peerAddress = $fromMe ? (string) ($payload['to'] ?? '') : $payload['from'];
        $phone = $this->normalizePhone($peerAddress);
        $message = trim((string) ($payload['body'] ?? ''));
        $mediaPayload = $payload['media'] ?? null;

        if ($phone === '' || ($message === '' && !$this->isPhotoPayload($mediaPayload))) {
            return response()->json(['success' => true, 'ignored' => 'empty_or_invalid']);
        }

        $providerMessageId = trim((string) ($payload['messageId'] ?? ''));
        if ($providerMessageId !== '' && LeadWhatsAppMessage::withoutGlobalScopes()
            ->where('provider_message_id', $providerMessageId)
            ->exists()) {
            return response()->json(['success' => true, 'duplicate' => true]);
        }

        $lead = $this->findLead($phone, (string) ($payload['sessionKey'] ?? ''));

        if (!$lead) {
            Log::info('Incoming WhatsApp message did not match a CRM lead.', [
                'phone' => $phone,
                'session_key' => $payload['sessionKey'] ?? null,
            ]);

            return response()->json(['success' => true, 'ignored' => 'lead_not_found']);
        }

        $media = $this->storePhoto($mediaPayload);

        if ($message === '' && !$media) {
            return response()->json(['success' => true, 'ignored' => 'unsupported_media']);
        }

        $messageAt = $this->messageTime($payload['timestamp'] ?? null);

        $direction = $fromMe ? 'outbound' : 'inbound';
        $chatMessage = LeadWhatsAppMessage::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('direction', $direction)
            ->where('message', $message)
            ->whereBetween('message_at', [$messageAt->copy()->subSeconds(2), $messageAt->copy()->addSeconds(2)])
            ->oldest('id')
            ->first();

        $chatMessage ??= new LeadWhatsAppMessage();
        $chatMessage->fill([
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'direction' => $direction,
            'phone' => $phone,
            'provider_message_id' => $providerMessageId !== '' ? $providerMessageId : $chatMessage->provider_message_id,
            'content_type' => $media ? 'image' : (string) ($payload['contentType'] ?? 'text'),
            'message' => $message,
            'status' => $fromMe ? 'sent' : 'received',
            'metadata' => [
                'session_key' => $payload['sessionKey'] ?? null,
                'chat_id' => $payload['chatId'] ?? null,
                'has_media' => (bool) ($payload['hasMedia'] ?? false),
                ...($media ?: []),
            ],
            'message_at' => $messageAt,
        ])->save();

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
        ], 201);
    }

    private function storePhoto(?array $media): ?array
    {
        $data = trim((string) data_get($media, 'data'));
        $mime = strtolower(trim((string) data_get($media, 'mimeType')));

        if ($data === '' || !str_starts_with($mime, 'image/')) {
            return null;
        }

        $data = preg_replace('/^data:.*?;base64,/i', '', $data);
        $binary = base64_decode($data, true);

        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        $extension = strtolower(pathinfo((string) data_get($media, 'fileName', 'photo.jpg'), PATHINFO_EXTENSION)) ?: 'jpg';
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $path = 'lead-chat/incoming/' . Str::uuid() . '.' . $extension;
        Storage::disk('local')->put($path, $binary);

        return [
            'media_path' => $path,
            'media_mime' => $mime,
            'media_name' => (string) data_get($media, 'fileName', 'photo.' . $extension),
            'media_size' => strlen($binary),
        ];
    }

    private function isPhotoPayload(?array $media): bool
    {
        return filled(data_get($media, 'data'))
            && str_starts_with(strtolower((string) data_get($media, 'mimeType')), 'image/');
    }

    private function findLead(string $phone, string $sessionKey): ?Lead
    {
        $lastTenDigits = strlen($phone) > 10 ? substr($phone, -10) : $phone;
        $companyId = $this->companyForSession($sessionKey);

        return Lead::withoutGlobalScopes()
            ->where(function ($query) use ($lastTenDigits) {
                foreach (['mobile', 'cell', 'office'] as $column) {
                    $query->orWhere($column, 'like', '%' . $lastTenDigits);
                }
            })
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('updated_at')
            ->first();
    }

    private function companyForSession(string $sessionKey): ?int
    {
        $normalizedSession = preg_replace('/\D+/', '', $sessionKey);
        if ($normalizedSession === '') {
            return null;
        }

        return WhatsappNotificationSetting::withoutGlobalScope(CompanyScope::class)
            ->whereRaw("REPLACE(REPLACE(REPLACE(lead_created_sender_number, '+', ''), ' ', ''), '-', '') = ?", [$normalizedSession])
            ->value('company_id');
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }

    private function messageTime($timestamp): \Illuminate\Support\Carbon
    {
        if (is_numeric($timestamp) && (int) $timestamp > 0) {
            $value = (int) $timestamp;
            if ($value > 20000000000) {
                $value = (int) floor($value / 1000);
            }

            return now()->setTimestamp($value);
        }

        return now();
    }
}
