<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_task_created_message')) {
                $table->enum('send_task_created_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_ticket_resolved_client_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'task_created_staff_template')) {
                $table->text('task_created_staff_template')
                    ->nullable()
                    ->after('ticket_resolved_client_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            $columns = [
                'task_created_staff_template',
                'send_task_created_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('whatsapp_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
