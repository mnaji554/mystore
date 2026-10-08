<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const PASSWORD = 'Password123';

    public function run(): void
    {
        $roles = Role::pluck('id', 'slug');

        $staff = [
            ['super@mystore.test', 'المدير العام', Role::SUPER_ADMIN],
            ['admin@mystore.test', 'مدير المتجر', Role::ADMIN],
            ['manager@mystore.test', 'مشرف المتجر', Role::MANAGER],
        ];

        foreach ($staff as [$email, $name, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'phone' => '0500000000', 'password' => self::PASSWORD]);
            $user->email_verified_at = now();
            $user->role_id = $roles[$role];
            $user->save();
        }

        $names = [
            'عبدالله العتيبي', 'سارة القحطاني', 'محمد الشهري', 'نورة الدوسري', 'خالد الحربي', 'ريم الغامدي',
            'فهد المطيري', 'هند السبيعي', 'يوسف الزهراني', 'ليلى العنزي', 'سلطان الشمري', 'أمل البلوي',
        ];
        $cities = ['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة', 'الخبر'];

        foreach (array_merge([['customer@mystore.test', 'عميل تجريبي']], collect($names)->map(fn ($n, $i) => ["customer{$i}@mystore.test", $n])->all()) as $i => [$email, $name]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'phone' => '05'.str_pad((string) (10000000 + $i * 137), 8, '0'), 'password' => self::PASSWORD]);
            $user->email_verified_at = now();
            $user->role_id = $roles[Role::CUSTOMER];
            $user->save();

            if ($user->addresses()->doesntExist()) {
                Address::create([
                    'user_id' => $user->id, 'label' => 'المنزل', 'full_name' => $name, 'phone' => $user->phone,
                    'country' => 'المملكة العربية السعودية', 'city' => $cities[$i % count($cities)],
                    'district' => 'حي النخيل', 'street' => 'شارع الأمير سلطان', 'building' => (string) (10 + $i),
                    'postal_code' => (string) (12000 + $i * 11), 'is_default' => true,
                ]);
            }
        }
    }
}
