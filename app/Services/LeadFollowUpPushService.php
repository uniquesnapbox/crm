<?php

namespace App\Services;

use App\Models\LeadFollowUp;
use App\Models\PushNotificationSetting;
use App\Models\User;
use App\Notifications\FollowUpSyncNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class LeadFollowUpPushService
{
    public function send(LeadFollowUp $followUp, string $action = 'sync'): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $followUp->loadMissing([
            'lead.leadAgent.user',
            'lead.addedBy',
            'addedBy',
        ]);

        $lead = $followUp->lead;
        $recipients = collect([
            $lead?->leadAgent?->user,
            $lead?->addedBy,
            $followUp->addedBy,
        ])->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::sendNow(
                $recipients,
                new FollowUpSyncNotification($this->payload($followUp, $action)),
            );
        } catch (\Throwable $exception) {
            // Push delivery must never roll back a successful CRM update.
            Log::warning('Lead follow-up push delivery failed.', [
                'follow_up_id' => $followUp->id,
                'action' => $action,
                'exception' => $exception::class,
            ]);
        }
    }

    private function isConfigured(): bool
    {
        $setting = PushNotificationSetting::query()
            ->where('status', 'active')
            ->first();

        $appId = trim((string) ($setting?->onesignal_app_id ?? ''));
        $restKey = trim((string) ($setting?->onesignal_rest_api_key ?? ''));

        return $appId !== ''
            && $restKey !== ''
            && !str_contains(strtolower($appId), 'your-')
            && !str_contains(strtolower($restKey), 'your-');
    }

    private function payload(LeadFollowUp $followUp, string $action): array
    {
        $lead = $followUp->lead;
        $scheduledAt = $followUp->next_follow_up_date?->toIso8601String();

        return [
            'id' => (int) $followUp->id,
            'lead_id' => (int) $followUp->lead_id,
            'lead_name' => (string) ($lead?->client_name ?? 'Lead'),
            'client_number' => (string) ($lead?->mobile ?: ($lead?->cell ?: ($lead?->office ?? ''))),
            'remarks' => (string) ($lead?->contact_status_reason ?? ''),
            'note' => (string) ($followUp->remark ?? ''),
            'scheduled_at' => $scheduledAt,
            'trigger_at' => $followUp->next_follow_up_date?->getTimestampMs() ?? 0,
            'status' => (string) ($followUp->status ?: 'pending'),
            'action' => $action,
        ];
    }
}
