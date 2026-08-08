<?php

use App\Models\WhatsappNotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'send_lead_followup_message')) {
                $table->enum('send_lead_followup_message', ['yes', 'no'])
                    ->default('yes')
                    ->after('send_lead_interest_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'lead_followup_template')) {
                $table->text('lead_followup_template')
                    ->nullable()
                    ->after('lead_interest_template');
            }
        });

        DB::table('whatsapp_notification_settings')
            ->whereNull('lead_followup_template')
            ->update([
                'lead_followup_template' => WhatsappNotificationSetting::DEFAULT_LEAD_FOLLOWUP_TEMPLATE,
            ]);
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'lead_followup_template')) {
                $table->dropColumn('lead_followup_template');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'send_lead_followup_message')) {
                $table->dropColumn('send_lead_followup_message');
            }
        });
    }
};
