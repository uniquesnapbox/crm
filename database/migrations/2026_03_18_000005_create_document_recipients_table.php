<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_workflow_id');
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role');
            $table->unsignedInteger('sequence_no')->default(1);
            $table->string('status')->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->boolean('is_external')->default(false);
            $table->timestamps();

            $table->index(['document_workflow_id', 'role', 'status'], 'document_recipients_workflow_role_status_index');
            $table->index(['recipient_type', 'recipient_id'], 'document_recipients_type_recipient_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_recipients');
    }
};
