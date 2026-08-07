<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_details') && !$this->indexExists('client_details', 'client_details_user_id_unique')) {
            Schema::table('client_details', function (Blueprint $table) {
                $table->unique('user_id', 'client_details_user_id_unique');
            });
        }

        if (Schema::hasTable('leads') && !$this->indexExists('leads', 'leads_company_converted_client_idx')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->index(['company_id', 'converted_at', 'client_id'], 'leads_company_converted_client_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('client_details') && $this->indexExists('client_details', 'client_details_user_id_unique')) {
            Schema::table('client_details', function (Blueprint $table) {
                $table->dropUnique('client_details_user_id_unique');
            });
        }

        if (Schema::hasTable('leads') && $this->indexExists('leads', 'leads_company_converted_client_idx')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_company_converted_client_idx');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(1) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$databaseName, $table, $indexName]
        );

        return (int) ($result->total ?? 0) > 0;
    }
};

