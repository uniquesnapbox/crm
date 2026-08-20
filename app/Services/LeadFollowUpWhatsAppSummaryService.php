<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LeadFollowUpWhatsAppSummaryService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendDailySummaryForCompany(int $companyId, bool $force = false): void
    {
        if (!config('services.lead_followup_summary.enabled', true)) {
            return;
        }

        $company = Company::query()
            ->select('id', 'company_name', 'timezone', 'date_format', 'time_format')
            ->find($companyId);

        if (!$company) {
            return;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $companyId)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isTaskDailyPendingMessageEnabled()) {
            return;
        }

        $timezone = $this->companyTimezone($company);
        $sendTime = $this->normalizedTime(config('services.lead_followup_summary.time', '09:00'));
        $now = now($timezone);

        if (!$force && $now->format('H:i') !== $sendTime) {
            return;
        }

        $cacheKey = sprintf(
            'daily_employee_lead_followup_whatsapp_summary:%d:%s:%s',
            $companyId,
            $now->toDateString(),
            $sendTime
        );

        if (!$force && !Cache::add($cacheKey, now()->timestamp, $now->copy()->endOfDay()->addHours(2))) {
            return;
        }

        $employees = User::allEmployees(null, true, null, $companyId)
            ->filter(function (User $user) {
                return $this->normalizeUserMobile($user) !== '';
            })
            ->values()
            ->keyBy('id');

        if ($employees->isEmpty()) {
            return;
        }

        $employeeIds = $employees->keys()->map(fn ($id) => (int) $id)->all();
        $followUpBuckets = $this->bucketFollowUpsByEmployee($companyId, $timezone, $employeeIds);

        foreach ($employees as $employee) {
            $mobile = $this->normalizeUserMobile($employee);

            if ($mobile === '') {
                continue;
            }

            $bucket = $followUpBuckets[$employee->id] ?? [
                'today' => collect(),
                'overdue' => collect(),
                'meetings' => collect(),
            ];

            $todayFollowUps = $this->sortFollowUps($bucket['today']);
            $overdueFollowUps = $this->sortFollowUps($bucket['overdue']);
            $meetingFollowUps = $this->sortFollowUps($bucket['meetings']);

            $message = $this->buildMessage(
                $company,
                $employee,
                $todayFollowUps,
                $overdueFollowUps,
                $meetingFollowUps
            );

            if ($message === '') {
                continue;
            }

            $sent = false;
            $sessionCandidates = $this->resolveSessionCandidates($setting);

            foreach ($sessionCandidates as $sessionKey) {
                $sent = $this->gatewayService->sendMessage($mobile, $message, $sessionKey);

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

            if (!$sent) {
                Log::warning('Daily lead follow-up summary WhatsApp failed.', [
                    'company_id' => $companyId,
                    'user_id' => $employee->id,
                    'mobile' => $mobile,
                    'error' => $this->gatewayService->getLastError(),
                ]);
            }
        }
    }

    /**
     * @param array<int> $employeeIds
     * @return array<int, array{today: Collection<int, LeadFollowUp>, overdue: Collection<int, LeadFollowUp>, meetings: Collection<int, LeadFollowUp>}>
     */
    private function bucketFollowUpsByEmployee(int $companyId, string $timezone, array $employeeIds): array
    {
        $buckets = [];

        foreach ($employeeIds as $employeeId) {
            $buckets[$employeeId] = [
                'today' => collect(),
                'overdue' => collect(),
                'meetings' => collect(),
            ];
        }

        if (empty($employeeIds)) {
            return $buckets;
        }

        $today = now($timezone)->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        $followUps = LeadFollowUp::with([
            'lead:id,client_name,assigned_to,added_by,company_id',
        ])
            ->where('status', 'pending')
            ->whereHas('lead', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('next_follow_up_date')
            ->get();

        foreach ($followUps as $followUp) {
            $recipientId = $this->resolveFollowUpRecipientId($followUp);

            if (!$recipientId || !array_key_exists($recipientId, $buckets)) {
                continue;
            }

            if (!$followUp->next_follow_up_date) {
                continue;
            }

            $followUpAt = Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timezone($timezone);

            if ($followUpAt->lt($today)) {
                $buckets[$recipientId]['overdue']->push($followUp);
            }

            if ($followUpAt->betweenIncluded($today, $todayEnd)) {
                $buckets[$recipientId]['today']->push($followUp);

                if ($this->looksLikeMeetingOrCall($followUp)) {
                    $buckets[$recipientId]['meetings']->push($followUp);
                }
            }
        }

        return $buckets;
    }

    private function buildMessage(
        Company $company,
        User $user,
        Collection $todayFollowUps,
        Collection $overdueFollowUps,
        Collection $meetingFollowUps
    ): string {
        $lines = [];
        $lines[] = '*📞 Daily Lead Follow-up Summary*';
        $lines[] = 'Hello ' . $user->name . ',';
        $lines[] = 'Date: ' . now($this->companyTimezone($company))->format($company->date_format . ' ' . $company->time_format);
        $lines[] = '';
        $lines[] = '*Counts*';
        $lines[] = "• Today's Follow-ups: " . $todayFollowUps->count();
        $lines[] = '• Overdue Follow-ups: ' . $overdueFollowUps->count();
        $lines[] = '• Meetings/Calls: ' . $meetingFollowUps->count();
        $lines[] = '';
        $lines[] = "*Today's Follow-ups*";
        $lines = array_merge($lines, $this->formatFollowUps($todayFollowUps, $company));
        $lines[] = '';
        $lines[] = '*Overdue Follow-ups*';
        $lines = array_merge($lines, $this->formatFollowUps($overdueFollowUps, $company));
        $lines[] = '';
        $lines[] = '*Meetings/Calls*';
        $lines = array_merge($lines, $this->formatFollowUps($meetingFollowUps, $company));

        return trim(implode("\n", $lines));
    }

    /**
     * @param Collection<int, LeadFollowUp> $followUps
     * @return array<int, string>
     */
    private function formatFollowUps(Collection $followUps, Company $company, int $limit = 10): array
    {
        if ($followUps->isEmpty()) {
            return ['• None'];
        }

        $rows = $followUps->take($limit)->map(function (LeadFollowUp $followUp, int $index) use ($company) {
            $leadName = (string) ($followUp->lead?->client_name ?: 'Unknown lead');
            $followUpAt = $followUp->next_follow_up_date
                ? Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timezone($this->companyTimezone($company))->format($company->date_format . ' ' . $company->time_format)
                : 'No date';
            $note = trim(strip_tags((string) $followUp->remark));
            $snippet = $note !== '' ? "\n" . '   Note: ' . mb_strimwidth($note, 0, 90, '...') : '';

            return ($index + 1) . '. ' . $leadName . "\n"
                . '   Time: ' . $followUpAt . $snippet;
        })->all();

        $remaining = $followUps->count() - count($rows);

        if ($remaining > 0) {
            $rows[] = '• + ' . $remaining . ' more';
        }

        return $rows;
    }

    /**
     * @param Collection<int, LeadFollowUp> $followUps
     * @return Collection<int, LeadFollowUp>
     */
    private function sortFollowUps(Collection $followUps): Collection
    {
        return $followUps->sortBy(function (LeadFollowUp $followUp) {
            return $followUp->next_follow_up_date
                ? Carbon::parse((string) $followUp->next_follow_up_date, 'UTC')->timestamp
                : PHP_INT_MAX;
        })->values();
    }

    private function resolveFollowUpRecipientId(LeadFollowUp $followUp): ?int
    {
        if (!is_null($followUp->lead?->assigned_to)) {
            return (int) $followUp->lead->assigned_to;
        }

        if (!is_null($followUp->lead?->added_by)) {
            return (int) $followUp->lead->added_by;
        }

        if (!is_null($followUp->assigned_to)) {
            return (int) $followUp->assigned_to;
        }

        if (!is_null($followUp->added_by)) {
            return (int) $followUp->added_by;
        }

        return null;
    }

    private function looksLikeMeetingOrCall(LeadFollowUp $followUp): bool
    {
        $text = strtolower(trim(
            (string) ($followUp->remark ?? '') . ' ' .
            (string) ($followUp->lead?->client_name ?? '')
        ));

        if ($text === '') {
            return false;
        }

        foreach (['meeting', 'meet', 'call', 'phone', 'zoom', 'demo', 'visit', 'appointment', 'discussion'] as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
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

    private function normalizedTime(string $time): string
    {
        $time = trim($time);

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return '09:00';
        }
    }
}
