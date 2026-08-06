<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('client_details', 'cell')) {
                $table->string('cell')->nullable()->after('office');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (Schema::hasColumn('client_details', 'cell')) {
                $table->dropColumn('cell');
            }
        });
    }
};
