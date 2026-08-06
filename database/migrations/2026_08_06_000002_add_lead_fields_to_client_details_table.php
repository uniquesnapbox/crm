<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('client_details', 'lead_source_id')) {
                $table->unsignedInteger('lead_source_id')->nullable()->after('client_type');
            }

            if (!Schema::hasColumn('client_details', 'lead_category_id')) {
                $table->unsignedInteger('lead_category_id')->nullable()->after('lead_source_id');
            }

            if (!Schema::hasColumn('client_details', 'lead_status_id')) {
                $table->unsignedInteger('lead_status_id')->nullable()->after('lead_category_id');
            }

            if (!Schema::hasColumn('client_details', 'lead_interest_level')) {
                $table->string('lead_interest_level')->nullable()->after('lead_status_id');
            }

            if (!Schema::hasColumn('client_details', 'lead_deal_size')) {
                $table->decimal('lead_deal_size', 15, 2)->nullable()->after('lead_interest_level');
            }

            if (!Schema::hasColumn('client_details', 'lead_contact_status')) {
                $table->string('lead_contact_status')->nullable()->after('lead_deal_size');
            }

            if (!Schema::hasColumn('client_details', 'lead_contact_status_reason')) {
                $table->text('lead_contact_status_reason')->nullable()->after('lead_contact_status');
            }

            if (!Schema::hasColumn('client_details', 'products_services')) {
                $table->text('products_services')->nullable()->after('lead_contact_status_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            foreach ([
                'products_services',
                'lead_contact_status_reason',
                'lead_contact_status',
                'lead_deal_size',
                'lead_interest_level',
                'lead_status_id',
                'lead_category_id',
                'lead_source_id',
            ] as $column) {
                if (Schema::hasColumn('client_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
