<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('action', 80);
            $table->string('entity_type', 80);
            $table->string('entity_id', 80);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('source', 40)->default('chatgpt_mcp');
            $table->uuid('request_id')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entity_type', 'entity_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_audit_logs');
    }
};
