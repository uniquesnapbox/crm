<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_areas')) {
            return;
        }

        Schema::create('company_areas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('state')->nullable();
            $table->string('district');
            $table->string('name');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'district', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_areas');
    }
};
