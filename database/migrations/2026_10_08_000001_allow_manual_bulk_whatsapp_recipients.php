<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_whatsapp_campaign_recipients')
            && Schema::hasColumn('bulk_whatsapp_campaign_recipients', 'lead_id')) {
            DB::statement('ALTER TABLE bulk_whatsapp_campaign_recipients MODIFY lead_id INT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_whatsapp_campaign_recipients')
            && Schema::hasColumn('bulk_whatsapp_campaign_recipients', 'lead_id')) {
            DB::statement('ALTER TABLE bulk_whatsapp_campaign_recipients MODIFY lead_id INT UNSIGNED NOT NULL');
        }
    }
};
