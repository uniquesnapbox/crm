<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasTable('lead_status')) {
            return;
        }

        $companies = DB::table('companies')->select('id')->orderBy('id')->get();

        foreach ($companies as $company) {
            $exists = DB::table('lead_status')
                ->where('company_id', $company->id)
                ->whereRaw('LOWER(type) = ?', ['lost'])
                ->exists();

            if ($exists) {
                continue;
            }

            $nextPriority = (int) DB::table('lead_status')
                ->where('company_id', $company->id)
                ->max('priority');

            DB::table('lead_status')->insert([
                'type' => 'Lost',
                'priority' => $nextPriority > 0 ? $nextPriority + 1 : 4,
                'default' => 0,
                'label_color' => '#DB1313',
                'company_id' => $company->id,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('lead_status')) {
            return;
        }

        DB::table('lead_status')
            ->whereRaw('LOWER(type) = ?', ['lost'])
            ->delete();
    }
};
