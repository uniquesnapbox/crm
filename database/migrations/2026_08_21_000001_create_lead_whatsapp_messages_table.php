<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->index();
            $table->string('direction', 20)->index();
            $table->string('phone', 40)->index();
            $table->string('provider_message_id', 191)->nullable()->unique();
            $table->string('content_type', 40)->default('text');
            $table->longText('message')->nullable();
            $table->string('status', 30)->default('received');
            $table->json('metadata')->nullable();
            $table->timestamp('message_at')->nullable()->index();
            $table->timestamps();

            $table->index(['lead_id', 'message_at']);
            $table->index(['company_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_whatsapp_messages');
    }
};
