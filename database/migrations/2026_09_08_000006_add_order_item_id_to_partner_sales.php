<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('order_item_id')->nullable()->after('order_id')->index();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partner_sales', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropColumn('order_item_id');
        });
    }
};
