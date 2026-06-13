<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lead_follow_up')) {
            return;
        }

        if (!Schema::hasColumn('lead_follow_up', 'lead_id')) {
            Schema::table('lead_follow_up', function (Blueprint $table) {
                $table->unsignedInteger('lead_id')->nullable()->after('deal_id');
                $table->index('lead_id', 'lead_follow_up_lead_id_idx');
            });
        }

        if (Schema::hasTable('deals')) {
            DB::statement('
                UPDATE lead_follow_up
                INNER JOIN deals ON deals.id = lead_follow_up.deal_id
                SET lead_follow_up.lead_id = deals.lead_id
                WHERE lead_follow_up.lead_id IS NULL
                  AND deals.lead_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('lead_follow_up') || !Schema::hasColumn('lead_follow_up', 'lead_id')) {
            return;
        }

        Schema::table('lead_follow_up', function (Blueprint $table) {
            $table->dropIndex('lead_follow_up_lead_id_idx');
            $table->dropColumn('lead_id');
        });
    }
};
