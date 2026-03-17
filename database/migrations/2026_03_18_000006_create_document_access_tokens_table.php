<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_workflow_id');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('token')->unique();
            $table->string('purpose')->default('public_action');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->index(['document_workflow_id', 'purpose'], 'document_access_tokens_workflow_purpose_index');
            $table->index(['recipient_id'], 'document_access_tokens_recipient_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('document_recipients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_tokens');
    }
};
