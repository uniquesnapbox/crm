<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('document_workflow_id');
            $table->string('action');
            $table->string('actor_type')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->longText('meta_json')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['document_workflow_id', 'action', 'created_at'], 'document_audit_logs_workflow_action_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');
    }
};
