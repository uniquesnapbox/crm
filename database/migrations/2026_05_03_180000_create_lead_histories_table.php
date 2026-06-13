<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_histories')) {
            return;
        }

        Schema::create('lead_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('lead_id');
            $table->string('event_type', 80);
            $table->string('title', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('field_key', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('event_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');

            $table->index(['lead_id', 'event_at'], 'lead_histories_lead_event_at_idx');
            $table->index(['lead_id', 'created_at'], 'lead_histories_lead_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_histories');
    }
};

