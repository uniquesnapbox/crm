<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('email_notification_settings', 'send_whatsapp')) {
                $table->enum('send_whatsapp', ['yes', 'no'])->default('no')->after('send_push');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('email_notification_settings', 'send_whatsapp')) {
                $table->dropColumn('send_whatsapp');
            }
        });
    }
};
