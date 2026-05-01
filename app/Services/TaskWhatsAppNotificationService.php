<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
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

        if (!$setting) {
            return;
        }

        $message = $this->renderTemplate(
            $setting->task_assigned_staff_template ?: 'A new task has been assigned to you. Task: {{task_heading}}',
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

    private function renderTemplate(string $template, Task $task): string
    {
        $placeholders = [
            '{{task_id}}' => (string) $task->id,
            '{{task_heading}}' => (string) $task->heading,
            '{{task_status}}' => (string) ($task->boardColumn?->column_name ?: $task->status ?: ''),
            '{{project_name}}' => (string) ($task->project?->project_name ?: ''),
            '{{due_date}}' => (string) ($task->due_date?->format('Y-m-d') ?: ''),
            '{{assigned_by}}' => (string) ($task->addedByUser?->name ?: optional(user())->name ?: ''),
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
