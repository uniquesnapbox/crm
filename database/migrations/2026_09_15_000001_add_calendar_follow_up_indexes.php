<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing(
            'lead_follow_up',
            'lead_follow_up_calendar_date_lead_status_idx',
            ['next_follow_up_date', 'lead_id', 'status']
        );

        $this->addIndexIfMissing('leads', 'leads_company_added_idx', ['company_id', 'added_by']);
        $this->addIndexIfMissing('leads', 'leads_company_assigned_idx', ['company_id', 'assigned_to']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('lead_follow_up', 'lead_follow_up_calendar_date_lead_status_idx');
        $this->dropIndexIfExists('leads', 'leads_company_added_idx');
        $this->dropIndexIfExists('leads', 'leads_company_assigned_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || !$this->hasColumns($table, $columns) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
