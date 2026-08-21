<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LeadFollowUp;
use App\Models\LeadFollowUpAttachment;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LeadFollowUpWhatsAppReminderService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendRemindersForCompany(int $companyId): void
    {
        $company = Company::query()
            ->select('id', 'company_name', 'timezone', 'date_format', 'time_format')
            ->find($companyId);

        if (!$company) {
            return;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $companyId)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isLeadFollowUpMessageEnabled()) {
            return;
        }

        $timezone = $this->companyTimezone($company);
        $now = now($timezone);
        $cutoffUtc = $now->copy()->addMinutes(10)->setTimezone('UTC');
        $lockTtl = $now->copy()->addMinutes(15);
        $sessionCandidates = $this->resolveSessionCandidates($setting);

        if (!$this->gatewayService->hasReadySession($sessionCandidates)) {
            Log::warning('Lead follow-up WhatsApp reminder scan deferred because no sender session is ready.', [
                'company_id' => $companyId,
                'sessions' => $sessionCandidates,
                'error' => $this->gatewayService->getLastError(),
            ]);

            return;
        }

        $activeEmployees = User::allEmployees(null, true, null, $companyId)->keyBy('id');

        $relations = [
            'lead:id,client_name,mobile,cell,office,assigned_to,added_by,company_id',
        ];

        if (Schema::hasTable('lead_follow_up_attachments')) {
            $relations[] = 'attachments:id,lead_follow_up_id,filename,hashname,mime_type,size';
        }

        $followUps = LeadFollowUp::with($relations)
            ->where('status', 'pending')
            ->whereNull('whatsapp_reminder_sent_at')
            ->whereNotNull('next_follow_up_date')
            ->where('next_follow_up_date', '<=', $cutoffUtc)
            ->whereHas('lead', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            // Recent reminders must not wait behind a large historical backlog.
            ->orderByDesc('next_follow_up_date')
            ->limit(100)
            ->get();

        foreach ($followUps as $followUp) {
            $this->sendReminderForLoadedFollowUp(
                $company,
                $setting,
                $followUp,
                $activeEmployees,
                $timezone,
                $lockTtl,
                null
            );
        }
    }

    public function sendReminderForFollowUp(int $companyId, int $followUpId, ?string $scheduledAtUtc = null): bool
    {
        $company = Company::query()
            ->select('id', 'company_name', 'timezone', 'date_format', 'time_format')
            ->find($companyId);

        if (!$company) {
            return false;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $companyId)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isLeadFollowUpMessageEnabled()) {
            return false;
        }

        $relations = [
            'lead:id,client_name,mobile,cell,office,assigned_to,added_by,company_id',
        ];

        if (Schema::hasTable('lead_follow_up_attachments')) {
            $relations[] = 'attachments:id,lead_follow_up_id,filename,hashname,mime_type,size';
        }

        $followUp = LeadFollowUp::with($relations)->find($followUpId);

        if (!$followUp || !$followUp->lead || (int) $followUp->lead->company_id !== (int) $companyId) {
            Log::info('Lead follow-up reminder job skipped because the record was not found for the company.', [
                'company_id' => $companyId,
                'follow_up_id' => $followUpId,
            ]);

            return false;
        }

        if (!$followUp->next_follow_up_date || $followUp->status !== 'pending' || !is_null($followUp->whatsapp_reminder_sent_at)) {
            Log::info('Lead follow-up reminder job skipped because it is no longer pending.', [
                'company_id' => $companyId,
                'follow_up_id' => $followUpId,
                'status' => $followUp->status,
                'sent_at' => $followUp->whatsapp_reminder_sent_at,
            ]);

            return false;
        }

        if ($scheduledAtUtc !== null) {
            $scheduledAtTimestamp = Carbon::parse($scheduledAtUtc, 'UTC')->timestamp;
            $currentTimestamp = Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timestamp;

            if ($scheduledAtTimestamp !== $currentTimestamp) {
                Log::info('Lead follow-up reminder job skipped because the scheduled time changed.', [
                    'company_id' => $companyId,
                    'follow_up_id' => $followUpId,
                    'scheduled_at_utc' => $scheduledAtUtc,
                    'current_follow_up_date_utc' => Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->toIso8601String(),
                ]);

                return false;
            }
        }

        $timezone = $this->companyTimezone($company);
        $activeEmployees = User::allEmployees(null, true, null, $companyId)->keyBy('id');
        $lockTtl = now($timezone)->addMinutes(15);

        return $this->sendReminderForLoadedFollowUp(
            $company,
            $setting,
            $followUp,
            $activeEmployees,
            $timezone,
            $lockTtl,
            $scheduledAtUtc
        );
    }

    /**
     * @param \Illuminate\Support\Collection<int, User> $activeEmployees
     */
    private function sendReminderForLoadedFollowUp(
        Company $company,
        WhatsappNotificationSetting $setting,
        LeadFollowUp $followUp,
        $activeEmployees,
        string $timezone,
        Carbon $lockTtl,
        ?string $scheduledAtUtc
    ): bool {
        if (!$followUp->next_follow_up_date) {
            return false;
        }

        $scheduledFor = Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')
            ->timezone($timezone)
            ->toDateTimeString();
        $followUpDueAt = Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')
            ->timezone($timezone)
            ->subMinutes(10);
        $isCatchUp = now($timezone)->greaterThan($followUpDueAt);
        $followUpLockKey = sprintf(
            'lead_followup_whatsapp_reminder:%d:%s',
            $followUp->id,
            Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timestamp
        );

        if (!Cache::add($followUpLockKey, now()->timestamp, $lockTtl)) {
            Log::debug('Lead follow-up WhatsApp reminder skipped because another worker is processing it.', [
                'company_id' => $company->id,
                'follow_up_id' => $followUp->id,
                'next_follow_up_date' => $scheduledFor,
            ]);

            return false;
        }

        $recipient = $this->resolveFollowUpRecipient($followUp, $activeEmployees);

        if (!$recipient) {
            Cache::forget($followUpLockKey);
            Log::info('Lead follow-up WhatsApp reminder skipped because no active recipient was found.', [
                'company_id' => $company->id,
                'follow_up_id' => $followUp->id,
                'lead_id' => $followUp->lead_id,
                'next_follow_up_date' => $scheduledFor,
            ]);

            return false;
        }

        $mobile = $this->normalizeUserMobile($recipient);

        if ($mobile === '') {
            Cache::forget($followUpLockKey);
            Log::info('Lead follow-up WhatsApp reminder skipped because recipient mobile was missing.', [
                'company_id' => $company->id,
                'follow_up_id' => $followUp->id,
                'user_id' => $recipient->id,
                'lead_id' => $followUp->lead_id,
            ]);

            return false;
        }

        $message = $this->buildReminderMessage(
            $company,
            $followUp,
            $recipient,
            $setting->lead_followup_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_FOLLOWUP_TEMPLATE
        );

        if ($message === '') {
            Cache::forget($followUpLockKey);
            Log::info('Lead follow-up WhatsApp reminder skipped because message body resolved empty.', [
                'company_id' => $company->id,
                'follow_up_id' => $followUp->id,
                'user_id' => $recipient->id,
                'lead_id' => $followUp->lead_id,
            ]);

            return false;
        }

        $attachments = $this->buildReminderAttachments($followUp);
        $idempotencySeed = sprintf(
            'lead-follow-up:%d:%d',
            $followUp->id,
            Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timestamp
        );
        $sent = false;
        $sessionCandidates = $this->resolveSessionCandidates($setting);
        $lastSession = null;

        foreach ($sessionCandidates as $sessionKey) {
            $lastSession = $sessionKey;
            $sent = $this->sendReminderMessages(
                $mobile,
                $message,
                $sessionKey,
                $attachments,
                $idempotencySeed
            );

            if ($sent) {
                break;
            }

            $error = strtolower((string) $this->gatewayService->getLastError());
            if (!str_contains($error, 'session not ready')
                && !str_contains($error, 'not ready')
                && !str_contains($error, 'disconnected')
                && !str_contains($error, 'unknown')) {
                break;
            }
        }

        if ($sent) {
            $followUp->forceFill([
                'whatsapp_reminder_sent_at' => now(),
            ])->save();

            Log::info('Lead follow-up WhatsApp reminder sent.', [
                'company_id' => $company->id,
                'follow_up_id' => $followUp->id,
                'lead_id' => $followUp->lead_id,
                'user_id' => $recipient->id,
                'mobile' => $mobile,
                'session' => $lastSession,
                'scheduled_for' => $scheduledFor,
                'due_at' => $followUpDueAt->toDateTimeString(),
                'sent_at' => now($timezone)->toDateTimeString(),
                'catch_up' => $isCatchUp,
                'scheduled_at_utc' => $scheduledAtUtc,
            ]);

            return true;
        }

        Cache::forget($followUpLockKey);

        Log::warning('Lead follow-up WhatsApp reminder failed.', [
            'company_id' => $company->id,
            'follow_up_id' => $followUp->id,
            'user_id' => $recipient->id,
            'mobile' => $mobile,
            'session' => $lastSession,
            'scheduled_for' => $scheduledFor,
            'due_at' => $followUpDueAt->toDateTimeString(),
            'catch_up' => $isCatchUp,
            'scheduled_at_utc' => $scheduledAtUtc,
            'error' => $this->gatewayService->getLastError(),
        ]);

        return false;
    }

    private function buildReminderMessage(Company $company, LeadFollowUp $followUp, User $recipient, string $template): string
    {
        if (!$followUp->next_follow_up_date) {
            return '';
        }

        $lead = $followUp->lead;
        $leadId = $lead?->id;

        if (!$leadId) {
            return '';
        }

        $leadName = (string) ($lead?->client_name ?: 'Unknown lead');
        $followUpAt = Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')
            ->timezone($this->companyTimezone($company))
            ->format($company->date_format . ' ' . $company->time_format);
        $remark = trim(strip_tags((string) $followUp->remark));
        $contact = trim((string) ($lead?->mobile ?: $lead?->cell ?: $lead?->office ?: ''));

        if ($remark === '') {
            $remark = 'No remarks added';
        }

        if ($contact === '') {
            $contact = 'N/A';
        }

        return trim(strtr($template, [
            '{{user_name}}' => (string) $recipient->name,
            '{{lead_name}}' => $leadName,
            '{{client_name}}' => $leadName,
            '{{follow_up_time}}' => $followUpAt,
            '{{call_time}}' => $followUpAt,
            '{{contact}}' => $contact,
            '{{lead_mobile}}' => $contact,
            '{{note}}' => mb_strimwidth($remark, 0, 120, '...'),
            '{{remarks}}' => mb_strimwidth($remark, 0, 120, '...'),
            '{{company_name}}' => (string) $company->company_name,
        ]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildReminderAttachments(LeadFollowUp $followUp): array
    {
        if (!Schema::hasTable('lead_follow_up_attachments')) {
            return [];
        }

        $attachments = $followUp->relationLoaded('attachments')
            ? $followUp->attachments
            : $followUp->attachments()->get();

        $payloads = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof LeadFollowUpAttachment) {
                continue;
            }

            $path = LeadFollowUpAttachment::FILE_PATH . '/' . $followUp->id . '/' . $attachment->hashname;

            if (!Storage::disk(config('filesystems.default'))->exists($path)) {
                continue;
            }

            $contents = Storage::disk(config('filesystems.default'))->get($path);
            if ($contents === '') {
                continue;
            }

            $payloads[] = [
                'data' => 'data:' . ($attachment->mime_type ?: Storage::disk(config('filesystems.default'))->mimeType($path) ?: 'application/octet-stream') . ';base64,' . base64_encode($contents),
                'mimeType' => $attachment->mime_type ?: Storage::disk(config('filesystems.default'))->mimeType($path) ?: 'application/octet-stream',
                'fileName' => $attachment->filename ?: $attachment->hashname,
                'sendAsDocument' => false,
            ];
        }

        return $payloads;
    }

    /**
     * @param array<int, array<string, mixed>> $attachments
     */
    private function sendReminderMessages(
        string $mobile,
        string $message,
        string $sessionKey,
        array $attachments,
        string $idempotencySeed
    ): bool
    {
        if (empty($attachments)) {
            return $this->gatewayService->sendMessage($mobile, $message, $sessionKey, null, $idempotencySeed . ':message');
        }

        $primaryAttachment = array_shift($attachments);
        $messageSent = $this->gatewayService->sendMessage(
            $mobile,
            $message,
            $sessionKey,
            $primaryAttachment,
            $idempotencySeed . ':attachment:0'
        );

        if (!$messageSent) {
            return false;
        }

        foreach ($attachments as $index => $attachment) {
            if (!$this->gatewayService->sendMessage(
                $mobile,
                '',
                $sessionKey,
                $attachment,
                $idempotencySeed . ':attachment:' . ($index + 1)
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Illuminate\Support\Collection<int, User> $activeEmployees
     */
    private function resolveFollowUpRecipient(LeadFollowUp $followUp, $activeEmployees): ?User
    {
        $lead = $followUp->lead;

        $recipientIds = array_filter([
            $lead?->assigned_to,
            $lead?->added_by,
            $followUp->added_by,
        ], fn ($value) => !is_null($value));

        foreach ($recipientIds as $recipientId) {
            $recipient = $activeEmployees->get((int) $recipientId);

            if ($recipient instanceof User) {
                return $recipient;
            }
        }

        return null;
    }

    private function normalizeUserMobile(User $user): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $user->mobile);
        $countryCode = preg_replace('/\D+/', '', (string) $user->country_phonecode);
        $fallbackCountryCode = preg_replace('/\D+/', '', (string) config('services.whatsapp_service.default_country_code', '91'));

        if ($mobile === '') {
            return '';
        }

        $mobile = ltrim($mobile, '0');

        if ($countryCode !== '') {
            if (str_starts_with($mobile, $countryCode)) {
                return $mobile;
            }

            return $countryCode . $mobile;
        }

        if ($fallbackCountryCode !== '' && strlen($mobile) === 10) {
            return $fallbackCountryCode . $mobile;
        }

        return $mobile;
    }

    /**
     * @return array<int, string>
     */
    private function resolveSessionCandidates(WhatsappNotificationSetting $setting): array
    {
        $preferred = preg_replace('/\D+/', '', (string) $setting->lead_created_sender_number);
        $fallback = trim((string) config('services.whatsapp_service.session', 'default'));
        $fallback = $fallback !== '' ? $fallback : 'default';

        return array_values(array_unique(array_filter([$preferred, $fallback], fn ($value) => $value !== '')));
    }

    private function companyTimezone(Company $company): string
    {
        return $company->timezone ?: config('app.timezone', 'Asia/Kolkata');
    }
}
