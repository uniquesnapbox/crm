<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_normalized_phone')) {
                $table->string('last_normalized_phone', 30)->nullable()->after('last_sent_at');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_response_message')) {
                $table->text('last_response_message')->nullable()->after('last_normalized_phone');
            }

            if (!Schema::hasColumn('whatsapp_notification_settings', 'last_delivery_status')) {
                $table->string('last_delivery_status', 50)->nullable()->after('last_response_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_notification_settings', function (Blueprint $table) {
            $columns = [
                'last_normalized_phone',
                'last_response_message',
                'last_delivery_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('whatsapp_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
