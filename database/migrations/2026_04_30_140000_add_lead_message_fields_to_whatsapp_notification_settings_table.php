<?php

use App\Models\WhatsappNotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_lead_created_message')) {
                $table->enum('send_lead_created_message', ['yes', 'no'])
                    ->default('no')
                    ->after('test_number');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'lead_created_template')) {
                $table->text('lead_created_template')
                    ->nullable()
                    ->after('send_lead_created_message');
            }
        });

        \App\Models\WhatsappNotificationSetting::query()
            ->whereNull('lead_created_template')
            ->update([
                'lead_created_template' => WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE,
            ]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'lead_created_template')) {
                $table->dropColumn('lead_created_template');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'send_lead_created_message')) {
                $table->dropColumn('send_lead_created_message');
            }
        });
    }
};
