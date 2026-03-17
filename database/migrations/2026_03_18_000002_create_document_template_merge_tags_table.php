<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_merge_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('template_id');
            $table->string('tag_key');
            $table->string('tag_label');
            $table->string('source_type');
            $table->string('source_path')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(['template_id', 'tag_key'], 'document_template_merge_tags_template_tag_unique');
            $table->index(['company_id', 'source_type'], 'document_template_merge_tags_company_source_index');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('document_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_merge_tags');
    }
};
