<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('document_workflow_id');
            $table->unsignedInteger('recipient_id')->nullable();
            $table->string('signer_name');
            $table->string('signer_email')->nullable();
            $table->string('signature_type');
            $table->string('signature_file')->nullable();
            $table->string('signature_text')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('location_meta')->nullable();
            $table->timestamps();

            $table->index(['document_workflow_id', 'signed_at'], 'document_signatures_workflow_signed_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('document_recipients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
