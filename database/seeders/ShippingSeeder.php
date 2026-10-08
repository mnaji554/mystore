<?php

namespace Database\Seeders;

use App\Models\ShippingCompany;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $fast = ShippingCompany::firstOrCreate(['name' => 'الشحن السريع'], [
            'tracking_url_template' => 'https://tracking.example.com/fast?number={number}',
        ]);
        $reliable = ShippingCompany::firstOrCreate(['name' => 'التوصيل الموثوق'], [
            'tracking_url_template' => 'https://tracking.example.com/track/{number}',
        ]);

        $methods = [
            ['name' => 'توصيل عادي', 'description' => 'توصيل لجميع المدن', 'company' => $reliable, 'price' => 25, 'is_free' => false, 'threshold' => 300, 'min' => 3, 'max' => 5, 'sort' => 1],
            ['name' => 'توصيل سريع', 'description' => 'خلال يوم إلى يومين عمل', 'company' => $fast, 'price' => 45, 'is_free' => false, 'threshold' => null, 'min' => 1, 'max' => 2, 'sort' => 2],
            ['name' => 'استلام من المتجر', 'description' => 'استلم طلبك من فرعنا بالرياض', 'company' => null, 'price' => 0, 'is_free' => true, 'threshold' => null, 'min' => 0, 'max' => 1, 'sort' => 3],
        ];

        foreach ($methods as $m) {
            ShippingMethod::updateOrCreate(['name' => $m['name']], [
                'shipping_company_id' => $m['company']?->id,
                'description' => $m['description'],
                'price' => $m['price'],
                'is_free' => $m['is_free'],
                'free_shipping_threshold' => $m['threshold'],
                'delivery_days_min' => $m['min'],
                'delivery_days_max' => $m['max'],
                'sort_order' => $m['sort'],
                'is_active' => true,
            ]);
        }
    }
}
