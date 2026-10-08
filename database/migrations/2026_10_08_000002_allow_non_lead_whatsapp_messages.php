<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_whatsapp_messages') && Schema::hasColumn('lead_whatsapp_messages', 'lead_id')) {
            DB::statement('ALTER TABLE lead_whatsapp_messages MODIFY lead_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_whatsapp_messages') && Schema::hasColumn('lead_whatsapp_messages', 'lead_id')) {
            DB::statement('ALTER TABLE lead_whatsapp_messages MODIFY lead_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
