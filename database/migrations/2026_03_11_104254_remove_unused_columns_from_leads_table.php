<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'salutation')) {
                $table->dropColumn('salutation');
            }
            if (Schema::hasColumn('leads', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('leads', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('leads', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'salutation')) {
                $table->string('salutation')->nullable()->after('id');
            }
            if (!Schema::hasColumn('leads', 'state')) {
                $table->string('state')->nullable()->after('country');
            }
            if (!Schema::hasColumn('leads', 'city')) {
                $table->string('city')->nullable()->after('state');
            }
            if (!Schema::hasColumn('leads', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
        });
    }
};