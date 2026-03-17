<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->string('base_url')->default('https://api-whatsapp.wascript.com.br');
            $table->string('api_token')->nullable();
            $table->string('default_country_code', 10)->nullable();
            $table->string('test_number', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notification_settings');
    }
};
