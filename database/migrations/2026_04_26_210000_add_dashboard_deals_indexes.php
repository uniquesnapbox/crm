<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('task_users', 'task_users_user_task_idx', ['user_id', 'task_id']);
        $this->addIndexIfMissing('project_members', 'project_members_user_project_idx', ['user_id', 'project_id']);
        $this->addIndexIfMissing('lead_follow_up', 'lead_follow_up_deal_status_next_idx', ['deal_id', 'status', 'next_follow_up_date']);
        $this->addIndexIfMissing('deals', 'deals_company_watcher_updated_idx', ['company_id', 'deal_watcher', 'updated_at']);
        $this->addIndexIfMissing('employee_shift_schedules', 'employee_shift_user_date_shift_idx', ['user_id', 'date', 'employee_shift_id']);
        $this->addIndexIfMissing('attendances', 'attendances_company_user_in_out_idx', ['company_id', 'user_id', 'clock_in_time', 'clock_out_time']);
        $this->addIndexIfMissing('notices', 'notices_company_added_created_idx', ['company_id', 'added_by', 'created_at']);
        $this->addIndexIfMissing('notices', 'notices_company_to_department_created_idx', ['company_id', 'to', 'department_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('task_users', 'task_users_user_task_idx');
        $this->dropIndexIfExists('project_members', 'project_members_user_project_idx');
        $this->dropIndexIfExists('lead_follow_up', 'lead_follow_up_deal_status_next_idx');
        $this->dropIndexIfExists('deals', 'deals_company_watcher_updated_idx');
        $this->dropIndexIfExists('employee_shift_schedules', 'employee_shift_user_date_shift_idx');
        $this->dropIndexIfExists('attendances', 'attendances_company_user_in_out_idx');
        $this->dropIndexIfExists('notices', 'notices_company_added_created_idx');
        $this->dropIndexIfExists('notices', 'notices_company_to_department_created_idx');
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

