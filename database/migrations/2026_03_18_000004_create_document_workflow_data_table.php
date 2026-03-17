<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflow_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('document_workflow_id');
            $table->longText('data_json')->nullable();
            $table->timestamps();

            $table->unique('document_workflow_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_workflow_id')->references('id')->on('document_workflows')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_data');
    }
};
