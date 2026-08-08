<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmployeeDailyWhatsAppSummaryService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendDailySummaryForCompany(int $companyId): void
    {
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
        $sendTime = $this->normalizedTime(config('services.whatsapp_service.task_summary_time', '09:00'));
        $now = now($timezone);

        if ($now->format('H:i') !== $sendTime) {
            return;
        }

        $cacheKey = sprintf(
            'daily_employee_task_whatsapp_summary:%d:%s:%s',
            $companyId,
            $now->toDateString(),
            $sendTime
        );

        if (!Cache::add($cacheKey, now()->timestamp, $now->copy()->endOfDay()->addHours(2))) {
            return;
        }

        $employees = User::allEmployees(null, true, null, $companyId)
            ->filter(function (User $user) {
                return $this->normalizeUserMobile($user) !== '';
            })
            ->values();

        if ($employees->isEmpty()) {
            return;
        }

        $employeeIds = $employees->pluck('id')->map(fn ($id) => (int) $id)->all();
        $taskBuckets = $this->bucketTasksByEmployee($companyId, $employeeIds);

        foreach ($employees as $employee) {
            $mobile = $this->normalizeUserMobile($employee);

            if ($mobile === '') {
                continue;
            }

            $tasks = $this->sortTasks($taskBuckets[$employee->id] ?? collect());
            $message = $this->buildMessage($company, $employee, $tasks);

            if ($message === '') {
                continue;
            }

            $sent = $this->gatewayService->sendMessage(
                $mobile,
                $message,
                $setting->resolved_lead_created_sender_number
            );

            if (!$sent) {
                Log::warning('Daily task summary WhatsApp failed.', [
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
     * @return array<int, Collection<int, Task>>
     */
    private function bucketTasksByEmployee(int $companyId, array $employeeIds): array
    {
        $buckets = [];

        foreach ($employeeIds as $employeeId) {
            $buckets[$employeeId] = collect();
        }

        if (empty($employeeIds)) {
            return $buckets;
        }

        $tasks = Task::with(['project:id,project_name', 'users:id,name,mobile,country_phonecode'])
            ->where('tasks.company_id', $companyId)
            ->pending()
            ->whereHas('users', function ($query) use ($employeeIds) {
                $query->whereIn('users.id', $employeeIds);
            })
            ->orderByRaw('tasks.due_date IS NULL')
            ->orderBy('tasks.due_date')
            ->get();

        foreach ($tasks as $task) {
            foreach ($task->users as $user) {
                if (!array_key_exists((int) $user->id, $buckets)) {
                    continue;
                }

                $buckets[(int) $user->id]->push($task);
            }
        }

        return $buckets;
    }

    private function buildMessage(Company $company, User $user, Collection $tasks): string
    {
        $lines = [];
        $lines[] = '*📋 Daily Task Summary*';
        $lines[] = 'Hello ' . $user->name . ',';
        $lines[] = 'Date: ' . now($this->companyTimezone($company))->format($company->date_format . ' ' . $company->time_format);
        $lines[] = '';
        $lines[] = '*Pending Tasks: ' . $tasks->count() . '*';
        $lines[] = '';
        $lines = array_merge($lines, $this->formatTasks($tasks, $company));

        return trim(implode("\n", $lines));
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return array<int, string>
     */
    private function formatTasks(Collection $tasks, Company $company, int $limit = 10): array
    {
        if ($tasks->isEmpty()) {
            return ['• None'];
        }

        $rows = $tasks->take($limit)->map(function (Task $task, int $index) use ($company) {
            $dueDate = $task->due_date
                ? Carbon::parse((string) $task->due_date, 'UTC')->timezone($this->companyTimezone($company))->format($company->date_format)
                : 'No due date';
            $projectName = $task->project?->project_name ?: 'No project';

            return ($index + 1) . '. ' . $task->heading . "\n"
                . '   Due: ' . $dueDate . "\n"
                . '   Project: ' . $projectName;
        })->all();

        $remaining = $tasks->count() - count($rows);
        if ($remaining > 0) {
            $rows[] = '• + ' . $remaining . ' more';
        }

        return $rows;
    }

    /**
     * @param Collection<int, Task> $tasks
     * @return Collection<int, Task>
     */
    private function sortTasks(Collection $tasks): Collection
    {
        return $tasks->sortBy(function (Task $task) {
            return $task->due_date ? Carbon::parse((string) $task->due_date, 'UTC')->timestamp : PHP_INT_MAX;
        })->values();
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
