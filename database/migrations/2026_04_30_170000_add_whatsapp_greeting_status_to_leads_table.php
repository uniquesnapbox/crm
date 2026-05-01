<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'whatsapp_greeting_sent_at')) {
                $table->timestamp('whatsapp_greeting_sent_at')->nullable()->after('mobile');
            }

            if (!Schema::hasColumn('leads', 'whatsapp_greeting_status')) {
                $table->string('whatsapp_greeting_status', 20)->nullable()->after('whatsapp_greeting_sent_at');
            }

            if (!Schema::hasColumn('leads', 'whatsapp_greeting_error')) {
                $table->text('whatsapp_greeting_error')->nullable()->after('whatsapp_greeting_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'whatsapp_greeting_error')) {
                $table->dropColumn('whatsapp_greeting_error');
            }

            if (Schema::hasColumn('leads', 'whatsapp_greeting_status')) {
                $table->dropColumn('whatsapp_greeting_status');
            }

            if (Schema::hasColumn('leads', 'whatsapp_greeting_sent_at')) {
                $table->dropColumn('whatsapp_greeting_sent_at');
            }
        });
    }
};
