<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'interest_level')) {
                $table->string('interest_level')->nullable()->after('status_id');
            }

            if (!Schema::hasColumn('leads', 'deal_size')) {
                $table->decimal('deal_size', 15, 2)->nullable()->after('interest_level');
            }

            if (!Schema::hasColumn('leads', 'contact_status')) {
                $table->string('contact_status')->nullable()->after('deal_size');
            }

            if (!Schema::hasColumn('leads', 'contact_status_reason')) {
                $table->text('contact_status_reason')->nullable()->after('contact_status');
            }

            if (!Schema::hasColumn('leads', 'products_services')) {
                $table->text('products_services')->nullable()->after('contact_status_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['products_services', 'contact_status_reason', 'contact_status', 'deal_size', 'interest_level'] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
