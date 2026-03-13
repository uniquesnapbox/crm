<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_locations')) {
            return;
        }

        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('timestamp')->index();

            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['employee_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_locations');
    }
};
