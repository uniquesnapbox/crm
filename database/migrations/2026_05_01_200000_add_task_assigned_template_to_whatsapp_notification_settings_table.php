<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'task_assigned_staff_template')) {
                $table->text('task_assigned_staff_template')->nullable()->after('ticket_resolved_client_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'task_assigned_staff_template')) {
                $table->dropColumn('task_assigned_staff_template');
            }
        });
    }
};
