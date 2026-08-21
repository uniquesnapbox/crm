<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_follow_up_attachments')) {
            return;
        }

        Schema::create('lead_follow_up_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->unsignedInteger('lead_follow_up_id');
            $table->foreign('lead_follow_up_id')->references('id')->on('lead_follow_up')->cascadeOnDelete();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('filename');
            $table->string('hashname');
            $table->string('mime_type')->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_up_attachments');
    }
};
