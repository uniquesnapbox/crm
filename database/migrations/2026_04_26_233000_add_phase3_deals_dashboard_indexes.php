<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deals') && !$this->hasIndex('deals', 'deals_company_pipeline_stage_updated_idx')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->index(['company_id', 'lead_pipeline_id', 'pipeline_stage_id', 'updated_at'], 'deals_company_pipeline_stage_updated_idx');
            });
        }

        if (Schema::hasTable('deals') && !$this->hasIndex('deals', 'deals_company_agent_watcher_followup_idx')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->index(['company_id', 'agent_id', 'deal_watcher', 'lead_id', 'next_follow_up'], 'deals_company_agent_watcher_followup_idx');
            });
        }

        if (Schema::hasTable('lead_agents') && !$this->hasIndex('lead_agents', 'lead_agents_company_user_added_idx')) {
            Schema::table('lead_agents', function (Blueprint $table) {
                $table->index(['company_id', 'user_id', 'added_by'], 'lead_agents_company_user_added_idx');
            });
        }

        if (Schema::hasTable('users') && !$this->hasIndex('users', 'users_company_status_name_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['company_id', 'status', 'name'], 'users_company_status_name_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deals') && $this->hasIndex('deals', 'deals_company_pipeline_stage_updated_idx')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropIndex('deals_company_pipeline_stage_updated_idx');
            });
        }

        if (Schema::hasTable('deals') && $this->hasIndex('deals', 'deals_company_agent_watcher_followup_idx')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropIndex('deals_company_agent_watcher_followup_idx');
            });
        }

        if (Schema::hasTable('lead_agents') && $this->hasIndex('lead_agents', 'lead_agents_company_user_added_idx')) {
            Schema::table('lead_agents', function (Blueprint $table) {
                $table->dropIndex('lead_agents_company_user_added_idx');
            });
        }

        if (Schema::hasTable('users') && $this->hasIndex('users', 'users_company_status_name_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_company_status_name_idx');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(1) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$databaseName, $table, $indexName]
        );

        return (int) ($result->total ?? 0) > 0;
    }
};

