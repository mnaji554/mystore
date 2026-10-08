<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShippingMethod> */
class ShippingMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'شحن '.fake()->unique()->word(),
            'price' => 25,
            'is_free' => false,
            'delivery_days_min' => 2,
            'delivery_days_max' => 4,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
