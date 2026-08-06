<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_ticket_message')) {
                $table->enum('send_ticket_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_lead_created_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_task_assigned_message')) {
                $table->enum('send_task_assigned_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_ticket_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'send_task_assigned_message')) {
                $table->dropColumn('send_task_assigned_message');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'send_ticket_message')) {
                $table->dropColumn('send_ticket_message');
            }
        });
    }
};
