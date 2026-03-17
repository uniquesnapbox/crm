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
            if (!Schema::hasColumn('employee_locations', 'clock_in_photo_path')) {
                $table->string('clock_in_photo_path')->nullable()->after('clock_in_address');
            }
            if (!Schema::hasColumn('employee_locations', 'clock_out_photo_path')) {
                $table->string('clock_out_photo_path')->nullable()->after('clock_out_address');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_locations')) {
            return;
        }

        Schema::table('employee_locations', function (Blueprint $table) {
            if (Schema::hasColumn('employee_locations', 'clock_in_photo_path')) {
                $table->dropColumn('clock_in_photo_path');
            }
            if (Schema::hasColumn('employee_locations', 'clock_out_photo_path')) {
                $table->dropColumn('clock_out_photo_path');
            }
        });
    }
};
