<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->json('shipping_address');
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->string('status', 20)->default('pending');
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency', 8)->default('SAR');
            $table->text('customer_note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('customer_email');
            $table->index('payment_status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name');
            $table->string('sku', 80);
            $table->string('variant_label')->nullable();
            $table->decimal('unit_price', 12, 2); // regular price
            $table->decimal('sale_price', 12, 2); // effective price paid per unit
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
            $table->index(['coupon_id', 'email']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            $table->string('status', 20)->default('pending'); // pending | paid | failed | refunded
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('SAR');
            $table->string('reference')->nullable(); // provider session / intent id
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('reference');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // charge | refund | webhook
            $table->string('status', 20);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('provider_reference')->nullable();
            $table->json('payload')->nullable(); // never contains card data
            $table->timestamps();

            $table->index('payment_id');
            $table->index('provider_reference');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
