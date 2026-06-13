<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('payments', 'payments_company_status_paid_on_idx', ['company_id', 'status', 'paid_on']);
        $this->addIndexIfMissing('payments', 'payments_company_project_paid_on_idx', ['company_id', 'project_id', 'paid_on']);

        $this->addIndexIfMissing('expenses', 'expenses_company_status_purchase_date_idx', ['company_id', 'status', 'purchase_date']);
        $this->addIndexIfMissing('expenses', 'expenses_company_user_purchase_date_idx', ['company_id', 'user_id', 'purchase_date']);

        $this->addIndexIfMissing('project_time_logs', 'project_time_logs_company_user_start_end_idx', ['company_id', 'user_id', 'start_time', 'end_time']);
        $this->addIndexIfMissing('project_time_logs', 'project_time_logs_company_project_start_idx', ['company_id', 'project_id', 'start_time']);

        $this->addIndexIfMissing('deals', 'deals_company_pipeline_stage_created_idx', ['company_id', 'lead_pipeline_id', 'pipeline_stage_id', 'created_at']);
        $this->addIndexIfMissing('deals', 'deals_company_added_watcher_idx', ['company_id', 'added_by', 'deal_watcher']);

        $this->addIndexIfMissing('leads', 'leads_company_assigned_created_idx', ['company_id', 'assigned_to', 'created_at']);
        $this->addIndexIfMissing('leads', 'leads_company_email_idx', ['company_id', 'client_email']);

        $this->addIndexIfMissing('tasks', 'tasks_company_project_board_due_idx', ['company_id', 'project_id', 'board_column_id', 'due_date']);

        $this->addIndexIfMissing('invoices', 'invoices_company_status_issue_due_idx', ['company_id', 'status', 'issue_date', 'due_date']);

        $this->addIndexIfMissing('attendances', 'attendances_company_user_clock_in_idx', ['company_id', 'user_id', 'clock_in_time']);

        $this->addIndexIfMissing('lead_follow_up', 'lead_follow_up_deal_next_follow_up_idx', ['deal_id', 'next_follow_up_date']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('payments', 'payments_company_status_paid_on_idx');
        $this->dropIndexIfExists('payments', 'payments_company_project_paid_on_idx');

        $this->dropIndexIfExists('expenses', 'expenses_company_status_purchase_date_idx');
        $this->dropIndexIfExists('expenses', 'expenses_company_user_purchase_date_idx');

        $this->dropIndexIfExists('project_time_logs', 'project_time_logs_company_user_start_end_idx');
        $this->dropIndexIfExists('project_time_logs', 'project_time_logs_company_project_start_idx');

        $this->dropIndexIfExists('deals', 'deals_company_pipeline_stage_created_idx');
        $this->dropIndexIfExists('deals', 'deals_company_added_watcher_idx');

        $this->dropIndexIfExists('leads', 'leads_company_assigned_created_idx');
        $this->dropIndexIfExists('leads', 'leads_company_email_idx');

        $this->dropIndexIfExists('tasks', 'tasks_company_project_board_due_idx');

        $this->dropIndexIfExists('invoices', 'invoices_company_status_issue_due_idx');

        $this->dropIndexIfExists('attendances', 'attendances_company_user_clock_in_idx');

        $this->dropIndexIfExists('lead_follow_up', 'lead_follow_up_deal_next_follow_up_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
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

    private function indexExists(string $table, string $indexName): bool
    {
        $indexName = str_replace("'", "''", $indexName);
        $table = str_replace("'", "''", $table);

        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");

        return !empty($result);
    }
};
