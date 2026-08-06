<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_follow_up', function (Blueprint $table) {
            if (!Schema::hasColumn('lead_follow_up', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('remind_type');
            }

            if (!Schema::hasColumn('lead_follow_up', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('client_id');
            }

            if (!Schema::hasColumn('leads', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('converted_at');
            }
        });

        if (Schema::hasTable('dashboard_widgets') && Schema::hasTable('companies')) {
            $widgets = [
                'todays_follow_ups',
                'upcoming_follow_ups',
                'pending_calls_meetings',
            ];

            $companyIds = Company::query()->pluck('id');

            foreach ($companyIds as $companyId) {
                foreach ($widgets as $widgetName) {
                    $exists = DB::table('dashboard_widgets')
                        ->where('company_id', $companyId)
                        ->where('dashboard_type', 'admin-dashboard')
                        ->where('widget_name', $widgetName)
                        ->exists();

                    if (!$exists) {
                        DB::table('dashboard_widgets')->insert([
                            'company_id' => $companyId,
                            'dashboard_type' => 'admin-dashboard',
                            'widget_name' => $widgetName,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('lead_follow_up', function (Blueprint $table) {
            if (Schema::hasColumn('lead_follow_up', 'longitude')) {
                $table->dropColumn('longitude');
            }

            if (Schema::hasColumn('lead_follow_up', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('leads', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
        });

        if (Schema::hasTable('dashboard_widgets')) {
            DB::table('dashboard_widgets')
                ->where('dashboard_type', 'admin-dashboard')
                ->whereIn('widget_name', ['todays_follow_ups', 'upcoming_follow_ups', 'pending_calls_meetings'])
                ->delete();
        }
    }
};
