<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'leads_company_mobile_unique';

    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'mobile_normalized')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('mobile_normalized', 20)->nullable()->after('mobile');
                $table->index(['company_id', 'mobile_normalized'], 'leads_company_mobile_normalized_index');
            });
        }

        if (!Schema::hasColumn('leads', 'mobile_duplicate_legacy')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->boolean('mobile_duplicate_legacy')->default(false)->after('mobile_normalized');
            });
        }

        if (!Schema::hasColumn('leads', 'legacy_mobile_normalized')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('legacy_mobile_normalized', 20)->nullable()->after('mobile_duplicate_legacy');
            });
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Keep existing duplicates intact. This column is an indexed
        // comparison value maintained by the application; the generated
        // unique key below independently protects direct database inserts.
        DB::statement("UPDATE leads SET mobile_normalized = NULLIF(REGEXP_REPLACE(COALESCE(mobile, ''), '[^0-9]', ''), '')");

        // Do not delete or rewrite legacy duplicate mobile values. Instead,
        // keep the oldest row as the protected canonical row and exempt only
        // the already-existing duplicate rows from the unique key. If one of
        // those legacy rows is later changed to a different mobile, its
        // generated key becomes active again and the database protects it.
        $duplicates = DB::table('leads')
            ->select('company_id', 'mobile_normalized')
            ->whereNotNull('mobile_normalized')
            ->groupBy('company_id', 'mobile_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $canonicalId = DB::table('leads')
                ->where('company_id', $duplicate->company_id)
                ->where('mobile_normalized', $duplicate->mobile_normalized)
                ->min('id');

            DB::table('leads')
                ->where('company_id', $duplicate->company_id)
                ->where('mobile_normalized', $duplicate->mobile_normalized)
                ->where('id', '<>', $canonicalId)
                ->update([
                    'mobile_duplicate_legacy' => true,
                    'legacy_mobile_normalized' => $duplicate->mobile_normalized,
                ]);
        }

        if (!Schema::hasColumn('leads', 'mobile_unique_key')) {
            DB::statement("ALTER TABLE leads ADD COLUMN mobile_unique_key VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN mobile_duplicate_legacy = 1 AND NULLIF(REGEXP_REPLACE(COALESCE(mobile, ''), '[^0-9]', ''), '') = legacy_mobile_normalized THEN NULL ELSE NULLIF(REGEXP_REPLACE(COALESCE(mobile, ''), '[^0-9]', ''), '') END) STORED");
        }

        $indexExists = collect(DB::select("SHOW INDEX FROM leads WHERE Key_name = '" . self::UNIQUE_INDEX . "'"))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unique(['company_id', 'mobile_unique_key'], self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'mobile_normalized')) {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'mobile_unique_key')) {
                    $table->dropUnique(self::UNIQUE_INDEX);
                }

                if (Schema::hasColumn('leads', 'mobile_normalized')) {
                    $table->dropIndex('leads_company_mobile_normalized_index');
                }

                if (Schema::hasColumn('leads', 'mobile_unique_key')) {
                    $table->dropColumn('mobile_unique_key');
                }

                if (Schema::hasColumn('leads', 'legacy_mobile_normalized')) {
                    $table->dropColumn('legacy_mobile_normalized');
                }

                if (Schema::hasColumn('leads', 'mobile_duplicate_legacy')) {
                    $table->dropColumn('mobile_duplicate_legacy');
                }

                $table->dropColumn('mobile_normalized');
            });
        }
    }
};
