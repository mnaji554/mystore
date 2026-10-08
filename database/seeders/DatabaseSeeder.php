<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            if ($value !== null && ! Setting::where('key', $key)->exists()) {
                Setting::put($key, $value);
            }
        }

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            ShippingSeeder::class,
            CatalogSeeder::class,
            CategoryTreeSeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
