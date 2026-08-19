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

        if (!$setting || $setting->status !== 'active' || !$setting->isLeadFollowUpMessageEnabled()) {
            return;
        }

        $timezone = $this->companyTimezone($company);
        $now = now($timezone);
        $target = $now->copy()->addMinutes(10);
        $lockTtl = $target->copy()->addMinutes(5);

        $windowStart = $target->copy()->startOfMinute()->setTimezone('UTC');
        $windowEnd = $target->copy()->endOfMinute()->setTimezone('UTC');
        $activeEmployees = User::allEmployees(null, true, null, $companyId)->keyBy('id');

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

            $recipient = $this->resolveFollowUpRecipient($followUp, $activeEmployees);

            if (!$recipient) {
                Cache::forget($followUpLockKey);
                continue;
            }

            $mobile = $this->normalizeUserMobile($recipient);

            if ($mobile === '') {
                Cache::forget($followUpLockKey);
                continue;
            }

            $message = $this->buildReminderMessage(
                $company,
                $followUp,
                $recipient,
                $setting->lead_followup_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_FOLLOWUP_TEMPLATE
            );

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
     * @param \Illuminate\Support\Collection<int, User> $activeEmployees
     */
    private function resolveFollowUpRecipient(LeadFollowUp $followUp, $activeEmployees): ?User
    {
        if (is_null($followUp->added_by)) {
            return null;
        }

        $recipient = $activeEmployees->get((int) $followUp->added_by);

        if (!$recipient instanceof User) {
            return null;
        }

        return $recipient;
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

    private function companyTimezone(Company $company): string
    {
        return $company->timezone ?: config('app.timezone', 'UTC');
    }
}
