<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'onesignal_mobile_subscription_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('onesignal_mobile_subscription_id', 255)
                    ->nullable()
                    ->after('onesignal_player_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'onesignal_mobile_subscription_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('onesignal_mobile_subscription_id');
            });
        }
    }
};
