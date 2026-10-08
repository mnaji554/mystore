<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tracking_url_template')->nullable(); // e.g. https://carrier.test/track/{number}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_free')->default(false);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->unsignedSmallInteger('delivery_days_min')->default(1);
            $table->unsignedSmallInteger('delivery_days_max')->default(3);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->string('type', 20); // percentage | fixed
            $table->decimal('value', 12, 2);
            $table->decimal('min_order_amount', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'expires_at']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        Schema::create('coupon_category', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_category');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_companies');
    }
};
