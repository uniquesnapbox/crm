<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfMissing('projects', 'projects_company_client_deleted_id_idx', 'company_id, client_id, deleted_at, id');
        $this->addIndexIfMissing('projects', 'projects_company_status_deleted_deadline_idx', 'company_id, status, deleted_at, deadline');

        $this->addIndexIfMissing('invoices', 'invoices_project_send_status_id_idx', 'project_id, send_status, status, id');
        $this->addIndexIfMissing('invoices', 'invoices_company_status_due_date_idx', 'company_id, status, due_date');
        $this->addIndexIfMissing('invoices', 'invoices_client_status_send_id_idx', 'client_id, status, send_status, id');

        $this->addIndexIfMissing('tasks', 'tasks_company_board_deleted_due_idx', 'company_id, board_column_id, deleted_at, due_date');
        $this->addIndexIfMissing('tasks', 'tasks_company_project_status_due_idx', 'company_id, project_id, status, due_date');

        $this->addIndexIfMissing('leads', 'leads_company_status_created_idx', 'company_id, status_id, created_at');
        $this->addIndexIfMissing('leads', 'leads_company_source_created_idx', 'company_id, source_id, created_at');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('projects', 'projects_company_client_deleted_id_idx');
        $this->dropIndexIfExists('projects', 'projects_company_status_deleted_deadline_idx');

        $this->dropIndexIfExists('invoices', 'invoices_project_send_status_id_idx');
        $this->dropIndexIfExists('invoices', 'invoices_company_status_due_date_idx');
        $this->dropIndexIfExists('invoices', 'invoices_client_status_send_id_idx');

        $this->dropIndexIfExists('tasks', 'tasks_company_board_deleted_due_idx');
        $this->dropIndexIfExists('tasks', 'tasks_company_project_status_due_idx');

        $this->dropIndexIfExists('leads', 'leads_company_status_created_idx');
        $this->dropIndexIfExists('leads', 'leads_company_source_created_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columns})");
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};

