<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'lead_created_sender_number')) {
                $table->string('lead_created_sender_number', 30)
                    ->nullable()
                    ->after('lead_created_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'lead_created_sender_number')) {
                $table->dropColumn('lead_created_sender_number');
            }
        });
    }
};
