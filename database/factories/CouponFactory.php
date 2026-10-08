<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CP####??')),
            'type' => Coupon::PERCENTAGE,
            'value' => 10,
            'is_active' => true,
            'is_public' => false,
        ];
    }

    public function fixed(float $amount): static
    {
        return $this->state(fn () => ['type' => Coupon::FIXED, 'value' => $amount]);
    }
}
