<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('employee_details', 'website')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->string('website')->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'office_phone')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->string('office_phone')->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'notice_period')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->unsignedSmallInteger('notice_period')->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'employee_type')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->string('employee_type')->nullable()->default('office_staff');
            });
        }

        if (!Schema::hasColumn('employee_details', 'latitude')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->decimal('latitude', 10, 7)->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'longitude')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->decimal('longitude', 10, 7)->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'office_latitude')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->decimal('office_latitude', 10, 7)->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'office_longitude')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->decimal('office_longitude', 10, 7)->nullable();
            });
        }

        if (!Schema::hasColumn('employee_details', 'allowed_radius')) {
            Schema::table('employee_details', function (Blueprint $table) {
                $table->unsignedInteger('allowed_radius')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = [
            'website',
            'office_phone',
            'notice_period',
            'employee_type',
            'latitude',
            'longitude',
            'office_latitude',
            'office_longitude',
            'allowed_radius',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('employee_details', $column)) {
                Schema::table('employee_details', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

