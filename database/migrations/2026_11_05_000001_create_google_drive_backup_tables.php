<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_backup_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->unique();
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->unsignedBigInteger('token_expires_at')->nullable();
            $table->string('google_email')->nullable();
            $table->string('drive_folder_id')->nullable();
            $table->enum('schedule', ['manual', 'daily', 'weekly'])->default('manual');
            $table->unsignedSmallInteger('retention_days')->default(30);
            $table->boolean('include_uploads')->default(true);
            $table->boolean('include_configuration')->default(true);
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::create('google_drive_backup_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status')->default('pending');
            $table->string('google_drive_file_id')->nullable();
            $table->text('google_drive_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_backup_history');
        Schema::dropIfExists('google_drive_backup_integrations');
    }
};
