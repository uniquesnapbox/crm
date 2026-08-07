<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        if (!$setting || $setting->status !== 'active') {
            return;
        }

        $timezone = $this->companyTimezone($company);
        $now = now($timezone);
        $target = $now->copy()->addMinutes(10);
        $lockTtl = $target->copy()->addMinutes(5);

        $windowStart = $target->copy()->startOfMinute()->setTimezone('UTC');
        $windowEnd = $target->copy()->endOfMinute()->setTimezone('UTC');

        $followUps = LeadFollowUp::with([
            'lead:id,client_name,mobile,cell,office,assigned_to,added_by,company_id',
        ])
            ->where('status', 'pending')
            ->whereNull('whatsapp_reminder_sent_at')
            ->whereBetween('next_follow_up_date', [$windowStart, $windowEnd])
            ->whereHas('lead', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

        foreach ($followUps as $followUp) {
            $followUpLockKey = sprintf(
                'lead_followup_whatsapp_reminder:%d:%s',
                $followUp->id,
                $target->format('Y-m-d H:i')
            );

            if (!Cache::add($followUpLockKey, now()->timestamp, $lockTtl)) {
                continue;
            }

            $recipient = $this->resolveFollowUpRecipient($followUp);

            if (!$recipient) {
                Cache::forget($followUpLockKey);
                continue;
            }

            $mobile = $this->normalizeUserMobile($recipient);

            if ($mobile === '') {
                Cache::forget($followUpLockKey);
                continue;
            }

            $message = $this->buildReminderMessage($company, $followUp, $recipient);

            if ($message === '') {
                Cache::forget($followUpLockKey);
                continue;
            }

            $sent = $this->gatewayService->sendMessage(
                $mobile,
                $message,
                $setting->resolved_lead_created_sender_number
            );

            if ($sent) {
                $followUp->forceFill([
                    'whatsapp_reminder_sent_at' => now(),
                ])->save();

                continue;
            }

            Cache::forget($followUpLockKey);

            Log::warning('Lead follow-up WhatsApp reminder failed.', [
                'company_id' => $companyId,
                'follow_up_id' => $followUp->id,
                'user_id' => $recipient->id,
                'mobile' => $mobile,
                'error' => $this->gatewayService->getLastError(),
            ]);
        }
    }

    private function buildReminderMessage(Company $company, LeadFollowUp $followUp, User $recipient): string
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

        $lines = [];
        $lines[] = '*📞 Lead Follow-up Reminder*';
        $lines[] = '';
        $lines[] = '*Hello ' . $recipient->name . ',* ⏰ Your follow-up starts in *10 minutes*.';
        $lines[] = '';
        $lines[] = '👤 *Lead:* ' . $leadName;
        $lines[] = '🕒 *Time:* ' . $followUpAt;

        if ($contact !== '') {
            $lines[] = '📞 *Contact:* ' . $contact;
        }

        if ($remark !== '') {
            $lines[] = '📝 *Note:* ' . mb_strimwidth($remark, 0, 120, '...');
        }

        $lines[] = '';
        $lines[] = 'Please update the follow-up after the call.';
        $lines[] = '';
        $lines[] = '*UNIQUZ SNAPBOX CRM*';

        return trim(implode("\n", $lines));
    }

    private function resolveFollowUpRecipient(LeadFollowUp $followUp): ?User
    {
        $lead = $followUp->lead;

        if (!$lead) {
            return null;
        }

        $userId = null;

        if (!is_null($lead->assigned_to)) {
            $userId = (int) $lead->assigned_to;
        } elseif (!is_null($lead->added_by)) {
            $userId = (int) $lead->added_by;
        }

        if (!$userId) {
            return null;
        }

        return User::withoutGlobalScopes()->find($userId);
    }

    private function normalizeUserMobile(User $user): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $user->mobile);
        $countryCode = preg_replace('/\D+/', '', (string) $user->country_phonecode);

        if ($mobile === '') {
            return '';
        }

        if ($countryCode !== '') {
            $mobile = ltrim($mobile, '0');

            if (str_starts_with($mobile, $countryCode)) {
                return $mobile;
            }

            return $countryCode . $mobile;
        }

        return $mobile;
    }

    private function companyTimezone(Company $company): string
    {
        return $company->timezone ?: config('app.timezone', 'UTC');
    }
}
