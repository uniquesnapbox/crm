<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_locations')) {
            return;
        }

        Schema::table('employee_locations', function (Blueprint $table) {
            // Link to an attendance row (optional but useful for session grouping)
            if (!Schema::hasColumn('employee_locations', 'attendance_id')) {
                $table->unsignedBigInteger('attendance_id')->nullable()->after('employee_id');
                $table->index('attendance_id');
            }

            // Clock in snapshot
            if (!Schema::hasColumn('employee_locations', 'clock_in_at')) {
                $table->dateTime('clock_in_at')->nullable()->after('timestamp');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_at');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_in_address')) {
                $table->text('clock_in_address')->nullable()->after('clock_in_longitude');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_in_photo_path')) {
                $table->string('clock_in_photo_path')->nullable()->after('clock_in_address');
            }

            // Live tracking (last update)
            if (!Schema::hasColumn('employee_locations', 'last_update_at')) {
                $table->dateTime('last_update_at')->nullable()->after('clock_in_address');
            }
            if (!Schema::hasColumn('employee_locations', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 7)->nullable()->after('last_update_at');
            }
            if (!Schema::hasColumn('employee_locations', 'last_longitude')) {
                $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            }
            if (!Schema::hasColumn('employee_locations', 'last_address')) {
                $table->text('last_address')->nullable()->after('last_longitude');
            }

            // Clock out snapshot
            if (!Schema::hasColumn('employee_locations', 'clock_out_at')) {
                $table->dateTime('clock_out_at')->nullable()->after('last_address');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_at');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_out_address')) {
                $table->text('clock_out_address')->nullable()->after('clock_out_longitude');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_out_photo_path')) {
                $table->string('clock_out_photo_path')->nullable()->after('clock_out_address');
            }

            // Active flag for current shift
            if (!Schema::hasColumn('employee_locations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('clock_out_address');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_locations')) {
            return;
        }

        Schema::table('employee_locations', function (Blueprint $table) {
            $drops = [
                'attendance_id',
                'clock_in_at',
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_address',
                'clock_in_photo_path',
                'last_update_at',
                'last_latitude',
                'last_longitude',
                'last_address',
                'clock_out_at',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_address',
                'clock_out_photo_path',
                'is_active',
            ];

            foreach ($drops as $column) {
                if (Schema::hasColumn('employee_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
