<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TaskWhatsAppNotificationService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendAssignedNotifications(Task $task, array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (empty($userIds)) {
            return;
        }

        $setting = WhatsappNotificationSetting::where('company_id', $task->company_id)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isTaskAssignedMessageEnabled()) {
            return;
        }

        $message = $this->renderTemplate(
            $setting->task_assigned_staff_template ?: WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE,
            $task
        );

        if ($message === '') {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;
        $users = User::withoutGlobalScopes()->whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $mobile = $this->userMobile($user);

            if ($mobile === '') {
                Log::warning('Task WhatsApp notification skipped due to missing user mobile.', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                ]);

                continue;
            }

            $sent = $this->gatewayService->sendMessage($mobile, $message, $senderNumber);

            if (!$sent) {
                Log::warning('Task WhatsApp notification failed.', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'sender_number' => $senderNumber,
                    'error' => $this->gatewayService->getLastError(),
                ]);
            }
        }
    }

    public function sendCompletedNotifications(Task $task): void
    {
        $setting = WhatsappNotificationSetting::where('company_id', $task->company_id)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isTaskCompletedMessageEnabled()) {
            return;
        }

        $userIds = $task->users()->pluck('users.id')->toArray();
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (empty($userIds)) {
            return;
        }

        $message = $this->renderTemplate(
            $setting->task_completed_template ?: WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE,
            $task
        );

        if ($message === '') {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;
        $users = User::withoutGlobalScopes()->whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $mobile = $this->userMobile($user);

            if ($mobile === '') {
                continue;
            }

            $sent = $this->gatewayService->sendMessage($mobile, $message, $senderNumber);

            if (!$sent) {
                Log::warning('Task completion WhatsApp notification failed.', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'sender_number' => $senderNumber,
                    'error' => $this->gatewayService->getLastError(),
                ]);
            }
        }
    }

    public function sendDailyPendingSummaryForCompany(int $companyId): void
    {
        $setting = WhatsappNotificationSetting::where('company_id', $companyId)->first();

        if (!$setting || $setting->status !== 'active' || !$setting->isTaskDailyPendingMessageEnabled()) {
            return;
        }

        $completedColumn = TaskboardColumn::where('company_id', $companyId)
            ->where('slug', 'completed')
            ->value('id');

        $users = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where('login', 'enable')
            ->get();

        foreach ($users as $user) {
            $mobile = $this->userMobile($user);

            if ($mobile === '') {
                continue;
            }

            $tasks = Task::with(['project:id,project_name'])
                ->where('tasks.company_id', $companyId)
                ->whereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->when($completedColumn, function ($query, $completedColumnId) {
                    $query->where(function ($inner) use ($completedColumnId) {
                        $inner->where('tasks.board_column_id', '!=', $completedColumnId)
                            ->orWhereNull('tasks.board_column_id');
                    });
                })
                ->orderByRaw('tasks.due_date IS NULL')
                ->orderBy('tasks.due_date')
                ->limit(20)
                ->get();

            if ($tasks->isEmpty()) {
                continue;
            }

            $message = $this->renderDailyPendingTemplate($setting, $user, $tasks);

            if ($message === '') {
                continue;
            }

            $sent = $this->gatewayService->sendMessage(
                $mobile,
                $message,
                $setting->resolved_lead_created_sender_number
            );

            if (!$sent) {
                Log::warning('Task pending-summary WhatsApp notification failed.', [
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                    'mobile' => $mobile,
                    'error' => $this->gatewayService->getLastError(),
                ]);
            }
        }
    }

    private function renderTemplate(string $template, Task $task): string
    {
        $placeholders = [
            '{{task_id}}' => (string) $task->id,
            '{{task_heading}}' => (string) $task->heading,
            '{{task_status}}' => (string) ($task->boardColumn?->column_name ?: $task->status ?: ''),
            '{{project_name}}' => (string) ($task->project?->project_name ?: ''),
            '{{due_date}}' => (string) ($task->due_date?->format('Y-m-d') ?: ''),
            '{{assigned_by}}' => (string) ($task->addedByUser?->name ?: optional(user())->name ?: ''),
            '{{completed_on}}' => (string) ($task->completed_on?->format('Y-m-d H:i') ?: now()->format('Y-m-d H:i')),
            '{{completed_by}}' => (string) (optional(user())->name ?: ''),
        ];

        return trim(strtr($template, $placeholders));
    }

    private function renderDailyPendingTemplate(
        WhatsappNotificationSetting $setting,
        User $user,
        Collection $tasks
    ): string {
        $taskList = $tasks->map(function (Task $task, int $index) {
            $dueDate = $task->due_date?->format('Y-m-d') ?: 'No due date';
            $projectName = $task->project?->project_name ?: 'No project';

            return ($index + 1) . '. ' . $task->heading . ' | Due: ' . $dueDate . ' | Project: ' . $projectName;
        })->implode("\n");

        $template = $setting->task_daily_pending_template ?: WhatsappNotificationSetting::DEFAULT_TASK_DAILY_PENDING_TEMPLATE;

        $placeholders = [
            '{{user_name}}' => (string) $user->name,
            '{{pending_count}}' => (string) $tasks->count(),
            '{{task_list}}' => $taskList,
        ];

        return trim(strtr($template, $placeholders));
    }

    private function userMobile(User $user): string
    {
        if (!empty($user->mobile) && !empty($user->country_phonecode)) {
            return preg_replace('/\D+/', '', $user->country_phonecode . $user->mobile);
        }

        return preg_replace('/\D+/', '', (string) $user->mobile);
    }
}
