<?php

namespace Tests\Feature;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\CartService;
use App\Services\CouponService;
use Tests\TestCase;

class CouponTest extends TestCase
{
    private function lines(array $items, $user = null)
    {
        $user ??= $this->customer();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);

        foreach ($items as [$product, $qty]) {
            $carts->add($cart, $product->id, null, $qty);
        }

        return [$carts->summary($cart, null, $user)->lines, $user];
    }

    private function validate(Coupon $coupon, $lines, $user = null, ?string $email = null): array
    {
        return app(CouponService::class)->validate($coupon->code, $lines, $user, $email);
    }

    public function test_percentage_coupon_with_max_discount_cap(): void
    {
        [$lines, $user] = $this->lines([[$this->product(['price' => 1000, 'stock' => 5]), 1]]);
        $coupon = Coupon::factory()->create(['value' => 20, 'max_discount' => 150]);

        $this->assertSame(150.0, $this->validate($coupon, $lines, $user)['discount']);
    }

    public function test_fixed_coupon_never_exceeds_the_order(): void
    {
        [$lines, $user] = $this->lines([[$this->product(['price' => 30]), 1]]);
        $coupon = Coupon::factory()->fixed(100)->create();

        $this->assertSame(30.0, $this->validate($coupon, $lines, $user)['discount']);
    }

    public function test_minimum_order_amount_is_enforced(): void
    {
        [$lines, $user] = $this->lines([[$this->product(['price' => 100]), 1]]);
        $coupon = Coupon::factory()->create(['min_order_amount' => 200]);

        $this->expectException(CouponException::class);
        $this->validate($coupon, $lines, $user);
    }

    public function test_inactive_not_started_and_expired_coupons_are_rejected(): void
    {
        [$lines, $user] = $this->lines([[$this->product(), 1]]);

        foreach ([
            ['is_active' => false],
            ['starts_at' => now()->addDay()],
            ['expires_at' => now()->subMinute()],
        ] as $state) {
            try {
                $this->validate(Coupon::factory()->create($state), $lines, $user);
                $this->fail('Coupon should be rejected: '.json_encode($state));
            } catch (CouponException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(CouponException::class);
        app(CouponService::class)->validate('DOES-NOT-EXIST', $lines, $user);
    }

    public function test_global_and_per_user_usage_limits(): void
    {
        [$lines, $user] = $this->lines([[$this->product(), 1]]);
        $limited = Coupon::factory()->create(['usage_limit' => 1, 'used_count' => 1]);
        $perUser = Coupon::factory()->create(['usage_limit_per_user' => 1]);
        CouponUsage::create(['coupon_id' => $perUser->id, 'user_id' => $user->id, 'discount_amount' => 5]);

        foreach ([$limited, $perUser] as $coupon) {
            try {
                $this->validate($coupon, $lines, $user);
                $this->fail('limit should apply');
            } catch (CouponException) {
                $this->assertTrue(true);
            }
        }

        // another customer may still use the per-user coupon
        [$otherLines, $other] = $this->lines([[$this->product(), 1]]);
        $this->assertGreaterThan(0, $this->validate($perUser, $otherLines, $other)['discount']);
    }

    public function test_guest_usage_is_tracked_by_email(): void
    {
        [$lines] = $this->lines([[$this->product(), 1]]);
        $coupon = Coupon::factory()->create(['usage_limit_per_user' => 1]);
        CouponUsage::create(['coupon_id' => $coupon->id, 'email' => 'guest@example.com', 'discount_amount' => 5]);

        $this->expectException(CouponException::class);
        $this->validate($coupon, $lines, null, 'guest@example.com');
    }

    public function test_product_specific_coupon_discounts_only_matching_lines(): void
    {
        $eligible = $this->product(['price' => 100]);
        $other = $this->product(['price' => 100]);
        [$lines, $user] = $this->lines([[$eligible, 1], [$other, 1]]);
        $coupon = Coupon::factory()->create(['value' => 50]);
        $coupon->products()->attach($eligible->id);

        $this->assertSame(50.0, $this->validate($coupon->fresh(), $lines, $user)['discount']);
    }

    public function test_category_specific_coupon_covers_subcategories_and_rejects_others(): void
    {
        $parent = $this->category();
        $child = $this->category(['parent_id' => $parent->id]);
        $inChild = $this->product(['price' => 100, 'category_id' => $child->id]);
        $outside = $this->product(['price' => 100]);

        $coupon = Coupon::factory()->create(['value' => 10]);
        $coupon->categories()->attach($parent->id);

        [$lines, $user] = $this->lines([[$inChild, 1], [$outside, 1]]);
        $this->assertSame(10.0, $this->validate($coupon->fresh(), $lines, $user)['discount']);

        [$onlyOutside, $user2] = $this->lines([[$outside, 1]]);
        $this->expectException(CouponException::class);
        $this->validate($coupon->fresh(), $onlyOutside, $user2);
    }

    public function test_coupon_code_is_stored_uppercase(): void
    {
        $this->assertSame('SAVE10', Coupon::factory()->create(['code' => ' save10 '])->code);
    }
}
