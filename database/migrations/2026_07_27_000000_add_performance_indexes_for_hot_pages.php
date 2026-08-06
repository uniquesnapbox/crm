<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('leads', 'leads_company_created_idx', ['company_id', 'created_at']);
        $this->addIndexIfMissing('leads', 'leads_company_updated_idx', ['company_id', 'updated_at']);

        $this->addIndexIfMissing('tasks', 'tasks_company_start_idx', ['company_id', 'start_date']);
        $this->addIndexIfMissing('tasks', 'tasks_company_completed_idx', ['company_id', 'completed_on']);

        $this->addIndexIfMissing('task_users', 'task_users_user_task_idx', ['user_id', 'task_id']);
        $this->addIndexIfMissing('task_users', 'task_users_task_user_idx', ['task_id', 'user_id']);
        $this->addIndexIfMissing('mention_users', 'mention_users_user_task_idx', ['user_id', 'task_id']);
        $this->addIndexIfMissing('mention_users', 'mention_users_task_user_idx', ['task_id', 'user_id']);
        $this->addIndexIfMissing('task_labels', 'task_labels_task_label_idx', ['task_id', 'label_id']);

        $this->addIndexIfMissing('employee_details', 'employee_details_joining_date_idx', ['joining_date']);
        $this->addIndexIfMissing('employee_details', 'employee_details_designation_joining_idx', ['designation_id', 'joining_date']);
        $this->addIndexIfMissing('employee_details', 'employee_details_department_joining_idx', ['department_id', 'joining_date']);

        $this->addIndexIfMissing('tickets', 'tickets_company_updated_idx', ['company_id', 'updated_at']);
        $this->addIndexIfMissing('tickets', 'tickets_agent_status_updated_idx', ['agent_id', 'status', 'updated_at']);

        $this->addIndexIfMissing('project_time_logs', 'project_time_logs_task_start_idx', ['task_id', 'start_time']);
        $this->addIndexIfMissing('project_time_logs', 'project_time_logs_user_end_idx', ['user_id', 'end_time']);

        $this->addIndexIfMissing('lead_histories', 'lead_histories_lead_event_idx', ['lead_id', 'event_at', 'id']);
        $this->addIndexIfMissing('lead_follow_up', 'lead_follow_up_lead_created_idx', ['lead_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('leads', 'leads_company_created_idx');
        $this->dropIndexIfExists('leads', 'leads_company_updated_idx');

        $this->dropIndexIfExists('tasks', 'tasks_company_start_idx');
        $this->dropIndexIfExists('tasks', 'tasks_company_completed_idx');

        $this->dropIndexIfExists('task_users', 'task_users_user_task_idx');
        $this->dropIndexIfExists('task_users', 'task_users_task_user_idx');
        $this->dropIndexIfExists('mention_users', 'mention_users_user_task_idx');
        $this->dropIndexIfExists('mention_users', 'mention_users_task_user_idx');
        $this->dropIndexIfExists('task_labels', 'task_labels_task_label_idx');

        $this->dropIndexIfExists('employee_details', 'employee_details_joining_date_idx');
        $this->dropIndexIfExists('employee_details', 'employee_details_designation_joining_idx');
        $this->dropIndexIfExists('employee_details', 'employee_details_department_joining_idx');

        $this->dropIndexIfExists('tickets', 'tickets_company_updated_idx');
        $this->dropIndexIfExists('tickets', 'tickets_agent_status_updated_idx');

        $this->dropIndexIfExists('project_time_logs', 'project_time_logs_task_start_idx');
        $this->dropIndexIfExists('project_time_logs', 'project_time_logs_user_end_idx');

        $this->dropIndexIfExists('lead_histories', 'lead_histories_lead_event_idx');
        $this->dropIndexIfExists('lead_follow_up', 'lead_follow_up_lead_created_idx');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName) || $this->indexOnColumnsExists($table, $columns)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
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
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function indexOnColumnsExists(string $table, array $columns): bool
    {
        $indexes = DB::table('information_schema.statistics')
            ->select('index_name', 'column_name', 'seq_in_index')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get()
            ->groupBy('index_name');

        foreach ($indexes as $indexColumns) {
            $existingColumns = $indexColumns->pluck('column_name')->all();

            if ($existingColumns === $columns) {
                return true;
            }
        }

        return false;
    }
};
