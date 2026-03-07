<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('lead_follow_up', 'whatsapp_sent_at')) {
            Schema::table('lead_follow_up', function (Blueprint $table) {
                $table->timestamp('whatsapp_sent_at')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('lead_follow_up', 'whatsapp_sent_at')) {
            Schema::table('lead_follow_up', function (Blueprint $table) {
                $table->dropColumn('whatsapp_sent_at');
            });
        }
    }
};

