<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->string('partner_name');
            $table->string('company_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('last_updated_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('last_updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('partner_product_commissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('partner_id')->index();
            $table->unsignedInteger('product_id')->index();
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('commission_value', 16, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['partner_id', 'product_id']);
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('partner_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('partner_id')->index();
            $table->unsignedInteger('lead_id')->nullable()->index();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedInteger('invoice_id')->nullable()->index();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->decimal('quantity', 16, 2)->default(1);
            $table->decimal('sale_amount', 16, 2)->default(0);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('commission_rate', 16, 2)->default(0);
            $table->decimal('commission_amount', 16, 2)->default(0);
            $table->date('sale_date')->index();
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('partner_commission_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('partner_id')->index();
            $table->decimal('amount', 16, 2);
            $table->date('payment_date')->index();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_commission_payments');
        Schema::dropIfExists('partner_sales');
        Schema::dropIfExists('partner_product_commissions');
        Schema::dropIfExists('partners');
    }
};
