<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $sports = Category::where('slug', 'sports')->first();

        $definitions = [
            ['code' => 'WELCOME10', 'description' => 'خصم 10% للطلب الأول', 'type' => Coupon::PERCENTAGE, 'value' => 10, 'usage_limit_per_user' => 1, 'is_public' => true],
            ['code' => 'SAVE50', 'description' => 'خصم 50 ريال على الطلبات فوق 300', 'type' => Coupon::FIXED, 'value' => 50, 'min_order_amount' => 300, 'is_public' => true],
            ['code' => 'VIP20', 'description' => 'خصم 20% بحد أقصى 200', 'type' => Coupon::PERCENTAGE, 'value' => 20, 'max_discount' => 200, 'usage_limit' => 100, 'expires_at' => now()->addMonths(3), 'is_public' => false],
            ['code' => 'SPORT15', 'description' => 'خصم 15% على الرياضة واللياقة', 'type' => Coupon::PERCENTAGE, 'value' => 15, 'is_public' => true],
            ['code' => 'EXPIRED5', 'description' => 'كوبون منتهي (للتجربة)', 'type' => Coupon::PERCENTAGE, 'value' => 5, 'expires_at' => now()->subDay(), 'is_public' => false],
        ];

        foreach ($definitions as $definition) {
            $coupon = Coupon::withTrashed()->firstOrNew(['code' => $definition['code']]);
            $coupon->fill($definition + ['is_active' => true])->save();

            if ($definition['code'] === 'SPORT15' && $sports) {
                $coupon->categories()->sync([$sports->id]);
            }
        }
    }
}
