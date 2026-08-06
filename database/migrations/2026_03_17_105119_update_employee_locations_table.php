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
            if (!Schema::hasColumn('employee_locations', 'address')) {
                $table->string('address', 255)->nullable()->after('longitude');
            }

            if (!Schema::hasColumn('employee_locations', 'type')) {
                $table->enum('type', ['live', 'clock_in', 'clock_out'])->default('live')->after('address');
            }

            if (!Schema::hasColumn('employee_locations', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_locations')) {
            return;
        }

        Schema::table('employee_locations', function (Blueprint $table) {
            if (Schema::hasColumn('employee_locations', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('employee_locations', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('employee_locations', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
