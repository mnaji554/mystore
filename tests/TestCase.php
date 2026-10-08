<?php

namespace Tests;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RoleSeeder::class);
    }

    protected function customer(array $attributes = []): User
    {
        return User::factory()->customer()->create($attributes);
    }

    protected function staff(string $role = 'admin'): User
    {
        return User::factory()->{match ($role) {
            'super_admin' => 'superAdmin',
            default => $role,
        }}()->create();
    }

    protected function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    protected function variantProduct(array $variantStocks = [5, 5]): Product
    {
        $product = Product::factory()->create(['stock' => 0]);

        foreach ($variantStocks as $i => $stock) {
            $variant = $product->variants()->create([
                'sku' => $product->sku.'-V'.($i + 1), 'price' => 100 + $i * 10, 'stock' => $stock, 'is_active' => true,
            ]);
            $variant->options()->create(['name' => 'size', 'value' => ['M', 'L', 'XL'][$i] ?? 'S'.$i]);
        }

        return $product->load('variants.options');
    }

    protected function shipping(array $attributes = []): ShippingMethod
    {
        return ShippingMethod::factory()->create($attributes);
    }

    protected function category(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }

    protected function checkoutData(ShippingMethod $method, string $payment = 'cod', array $overrides = []): array
    {
        return array_merge([
            'name' => 'عميل اختبار',
            'email' => 'buyer@example.com',
            'phone' => '0501234567',
            'address' => [
                'full_name' => 'عميل اختبار', 'phone' => '0501234567', 'country' => 'السعودية',
                'city' => 'الرياض', 'district' => 'النخيل', 'street' => 'شارع الملك فهد', 'building' => '5', 'postal_code' => '12345',
            ],
            'shipping_method_id' => $method->id,
            'payment_method' => $payment,
            'note' => null,
        ], $overrides);
    }
}
