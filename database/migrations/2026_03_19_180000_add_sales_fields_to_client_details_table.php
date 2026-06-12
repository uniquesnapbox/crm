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
        Schema::whenTableDoesntHaveColumn('client_details', 'client_type', function (Blueprint $table) {
            $table->string('client_type', 20)->nullable()->after('note');
        });

        Schema::whenTableDoesntHaveColumn('client_details', 'last_contact_date', function (Blueprint $table) {
            $table->date('last_contact_date')->nullable()->after('client_type');
        });

        Schema::whenTableDoesntHaveColumn('client_details', 'next_followup_date', function (Blueprint $table) {
            $table->date('next_followup_date')->nullable()->after('last_contact_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::whenTableHasColumn('client_details', 'next_followup_date', function (Blueprint $table) {
            $table->dropColumn('next_followup_date');
        });

        Schema::whenTableHasColumn('client_details', 'last_contact_date', function (Blueprint $table) {
            $table->dropColumn('last_contact_date');
        });

        Schema::whenTableHasColumn('client_details', 'client_type', function (Blueprint $table) {
            $table->dropColumn('client_type');
        });
    }

};
