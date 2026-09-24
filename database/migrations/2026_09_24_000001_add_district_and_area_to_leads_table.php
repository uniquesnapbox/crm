<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'state')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('state')->nullable()->after('country');
            });
        }

        if (!Schema::hasColumn('leads', 'district')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('district')->nullable()->after('state');
            });
        }

        if (!Schema::hasColumn('leads', 'area')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('area')->nullable()->after('district');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'area')) {
                $table->dropColumn('area');
            }

            if (Schema::hasColumn('leads', 'district')) {
                $table->dropColumn('district');
            }
        });
    }
};
