<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'ticket_assigned_staff_template')) {
                $table->text('ticket_assigned_staff_template')->nullable()->after('lead_created_sender_number');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'ticket_assigned_client_template')) {
                $table->text('ticket_assigned_client_template')->nullable()->after('ticket_assigned_staff_template');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'ticket_resolved_client_template')) {
                $table->text('ticket_resolved_client_template')->nullable()->after('ticket_assigned_client_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'ticket_resolved_client_template')) {
                $table->dropColumn('ticket_resolved_client_template');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'ticket_assigned_client_template')) {
                $table->dropColumn('ticket_assigned_client_template');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'ticket_assigned_staff_template')) {
                $table->dropColumn('ticket_assigned_staff_template');
            }
        });
    }
};
