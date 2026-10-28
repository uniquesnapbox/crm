<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_categories')) {
            Schema::create('company_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->string('name');
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->unique(['company_id', 'name']);
            });
        }

        if (!Schema::hasColumn('leads', 'company_category_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedInteger('company_category_id')->nullable()->after('company_name');
                $table->foreign('company_category_id')->references('id')->on('company_categories')->nullOnDelete();
                $table->index('company_category_id');
            });
        }

        $defaultCategories = [
            'Pharmacy',
            'Grocery',
            'Restaurant',
        ];

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            foreach ($defaultCategories as $name) {
                DB::table('company_categories')->updateOrInsert(
                    ['company_id' => $companyId, 'name' => $name],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'company_category_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['company_category_id']);
                $table->dropIndex(['company_category_id']);
                $table->dropColumn('company_category_id');
            });
        }

        Schema::dropIfExists('company_categories');
    }
};
