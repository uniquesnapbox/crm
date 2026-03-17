<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_send_status')) {
                $table->string('last_send_status', 50)->nullable()->after('test_number');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_error_message')) {
                $table->text('last_error_message')->nullable()->after('last_send_status');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_http_status')) {
                $table->unsignedSmallInteger('last_http_status')->nullable()->after('last_error_message');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_response_body')) {
                $table->longText('last_response_body')->nullable()->after('last_http_status');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable()->after('last_response_body');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            $columns = [
                'last_send_status',
                'last_error_message',
                'last_http_status',
                'last_response_body',
                'last_sent_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('whatsapp_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
