<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_workflow_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedInteger('step_no');
            $table->string('status')->default('pending');
            $table->string('action')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['document_workflow_id', 'step_no'], 'document_approvals_workflow_step_index');
            $table->index(['recipient_id', 'status'], 'document_approvals_recipient_status_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('document_recipients')->onDelete('cascade');
            $table->foreign('acted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
