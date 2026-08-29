<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_whatsapp_templates')) {
            Schema::create('bulk_whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->string('name');
                $table->longText('message');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'name'], 'bulk_whatsapp_templates_company_name_unique');
            });
        }

        if (!Schema::hasTable('bulk_whatsapp_campaigns')) {
            Schema::create('bulk_whatsapp_campaigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('name');
                $table->string('session_key', 50)->nullable()->index();
                $table->longText('message_body')->nullable();
                $table->json('lead_filters')->nullable();
                $table->unsignedInteger('recipient_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->string('status', 30)->default('queued')->index();
                $table->string('batch_id', 255)->nullable()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->longText('last_error')->nullable();
                $table->timestamps();

                $table->foreign('template_id')->references('id')->on('bulk_whatsapp_templates')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('bulk_whatsapp_campaign_recipients')) {
            Schema::create('bulk_whatsapp_campaign_recipients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('campaign_id')->index();
                $table->unsignedInteger('lead_id')->index();
                $table->string('lead_name');
                $table->string('phone', 30)->nullable()->index();
                $table->longText('rendered_message');
                $table->string('status', 20)->default('pending')->index();
                $table->string('provider_message_id', 191)->nullable()->index();
                $table->longText('error_message')->nullable();
                $table->json('response_data')->nullable();
                $table->unsignedSmallInteger('attempt_count')->default(0);
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamps();

                $table->foreign('campaign_id')->references('id')->on('bulk_whatsapp_campaigns')->cascadeOnDelete();
                $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_whatsapp_campaign_recipients');
        Schema::dropIfExists('bulk_whatsapp_campaigns');
        Schema::dropIfExists('bulk_whatsapp_templates');
    }
};
