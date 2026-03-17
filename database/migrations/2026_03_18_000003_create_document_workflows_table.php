<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflows', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('template_id')->nullable();
            $table->string('document_number')->nullable();
            $table->string('original_document_number')->nullable();
            $table->string('title');
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('document_type');
            $table->string('module_context')->nullable();
            $table->unsignedInteger('context_id')->nullable();
            $table->unsignedInteger('owner_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->string('status')->default('draft');
            $table->string('approval_status')->default('not_required');
            $table->string('signature_status')->default('not_required');
            $table->longText('generated_html')->nullable();
            $table->string('generated_pdf_path')->nullable();
            $table->string('verification_hash')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('last_updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status'], 'document_workflows_company_status_index');
            $table->index(['document_type', 'status'], 'document_workflows_type_status_index');
            $table->index(['owner_id'], 'document_workflows_owner_index');
            $table->index(['client_id'], 'document_workflows_client_index');
            $table->index(['project_id'], 'document_workflows_project_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('document_templates')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('last_updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflows');
    }
};
