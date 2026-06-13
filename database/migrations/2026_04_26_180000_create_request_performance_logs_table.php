<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('method', 10)->index();
            $table->string('path')->index();
            $table->integer('status_code')->index();
            $table->decimal('duration_ms', 10, 2)->index();
            $table->decimal('query_time_ms', 10, 2)->default(0);
            $table->unsignedInteger('query_count')->default(0);
            $table->string('route_name')->nullable()->index();
            $table->string('request_id', 64)->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_performance_logs');
    }
};

