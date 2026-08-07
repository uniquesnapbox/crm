<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lead_follow_up', 'whatsapp_reminder_sent_at')) {
            Schema::table('lead_follow_up', function (Blueprint $table) {
                $table->timestamp('whatsapp_reminder_sent_at')->nullable()->after('whatsapp_sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lead_follow_up', 'whatsapp_reminder_sent_at')) {
            Schema::table('lead_follow_up', function (Blueprint $table) {
                $table->dropColumn('whatsapp_reminder_sent_at');
            });
        }
    }
};
