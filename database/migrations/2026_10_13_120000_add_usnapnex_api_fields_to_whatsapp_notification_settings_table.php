<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'api_driver')) {
                $table->string('api_driver', 30)->default('bridge')->after('api_token');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'api_session')) {
                $table->string('api_session', 100)->default('default')->after('api_driver');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_notification_settings', 'api_session')) {
                $table->dropColumn('api_session');
            }

            if (Schema::hasColumn('whatsapp_notification_settings', 'api_driver')) {
                $table->dropColumn('api_driver');
            }
        });
    }
};
