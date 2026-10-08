<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 9999999);

        return [
            'category_id' => Category::factory(),
            'name_ar' => 'منتج '.$n,
            'name_en' => 'Product '.$n,
            'slug' => 'product-'.$n,
            'sku' => 'SKU-'.$n,
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'price' => 100,
            'stock' => 10,
            'weight' => 0.5,
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function onSale(float $salePrice = 80): static
    {
        return $this->state(fn () => ['sale_price' => $salePrice]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Product::STATUS_DRAFT]);
    }
}
