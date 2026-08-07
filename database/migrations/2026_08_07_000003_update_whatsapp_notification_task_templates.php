<?php

use App\Models\WhatsappNotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_notification_settings')
            ->where(function ($query) {
                $query->whereNull('task_assigned_staff_template')
                    ->orWhere('task_assigned_staff_template', '')
                    ->orWhere('task_assigned_staff_template', 'A new task has been assigned to you. Task: {{task_heading}}');
            })
            ->update([
                'task_assigned_staff_template' => WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE,
            ]);

        DB::table('whatsapp_notification_settings')
            ->where(function ($query) {
                $query->whereNull('task_completed_template')
                    ->orWhere('task_completed_template', '')
                    ->orWhere(
                        'task_completed_template',
                        "Task completed: {{task_heading}}\nProject: {{project_name}}\nCompleted on: {{completed_on}}"
                    );
            })
            ->update([
                'task_completed_template' => WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE,
            ]);
    }

    public function down(): void
    {
        DB::table('whatsapp_notification_settings')
            ->where('task_assigned_staff_template', WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE)
            ->update([
                'task_assigned_staff_template' => 'A new task has been assigned to you. Task: {{task_heading}}',
            ]);

        DB::table('whatsapp_notification_settings')
            ->where('task_completed_template', WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE)
            ->update([
                'task_completed_template' => "Task completed: {{task_heading}}\nProject: {{project_name}}\nCompleted on: {{completed_on}}",
            ]);
    }
};
