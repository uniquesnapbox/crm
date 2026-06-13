<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_lead_interest_message')) {
                $table->enum('send_lead_interest_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_lead_created_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'lead_interest_template')) {
                $table->text('lead_interest_template')
                    ->nullable()
                    ->after('lead_created_template');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_ticket_assigned_staff_message')) {
                $table->enum('send_ticket_assigned_staff_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_ticket_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_ticket_assigned_client_message')) {
                $table->enum('send_ticket_assigned_client_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_ticket_assigned_staff_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_ticket_resolved_client_message')) {
                $table->enum('send_ticket_resolved_client_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_ticket_assigned_client_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_task_daily_pending_message')) {
                $table->enum('send_task_daily_pending_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_task_assigned_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'task_daily_pending_template')) {
                $table->text('task_daily_pending_template')
                    ->nullable()
                    ->after('task_assigned_staff_template');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_task_completed_message')) {
                $table->enum('send_task_completed_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_task_daily_pending_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'task_completed_template')) {
                $table->text('task_completed_template')
                    ->nullable()
                    ->after('task_daily_pending_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            $columns = [
                'task_completed_template',
                'send_task_completed_message',
                'task_daily_pending_template',
                'send_task_daily_pending_message',
                'send_ticket_resolved_client_message',
                'send_ticket_assigned_client_message',
                'send_ticket_assigned_staff_message',
                'lead_interest_template',
                'send_lead_interest_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('whatsapp_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
