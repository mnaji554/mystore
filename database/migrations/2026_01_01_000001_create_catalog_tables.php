<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->string('full_name');
            $table->string('phone', 30);
            $table->string('country', 80)->default('المملكة العربية السعودية');
            $table->string('city', 100);
            $table->string('district', 100)->nullable();
            $table->string('street');
            $table->string('building', 60)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
            $table->index('is_active');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->string('sku', 80)->unique();
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->integer('stock')->default(0);
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_new')->default(false);
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_new']);
            $table->index(['status', 'sales_count']);
            $table->index('discount_percent');
            $table->index('price');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80)->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('name', 40); // color | size | ...
            $table->string('value', 80);
            $table->string('color_hex', 9)->nullable();
            $table->timestamps();

            $table->index(['variant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_options');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('addresses');
    }
};
