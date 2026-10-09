<?php

namespace App\Support;

/**
 * Central definition of roles and permissions used by Gates and the seeder.
 */
class Permissions
{
    public const ALL = [
        'access-admin' => 'دخول لوحة التحكم',
        'manage-products' => 'إدارة المنتجات',
        'manage-categories' => 'إدارة التصنيفات',
        'manage-orders' => 'إدارة الطلبات',
        'manage-customers' => 'إدارة العملاء',
        'manage-coupons' => 'إدارة الكوبونات',
        'manage-shipping' => 'إدارة الشحن',
        'manage-reviews' => 'إدارة التقييمات',
        'manage-settings' => 'إعدادات المتجر',
        'manage-staff' => 'إدارة الموظفين والأدوار',
        'delete-records' => 'حذف السجلات',
    ];

    public const ROLES = [
        'super_admin' => ['name' => 'مدير عام', 'permissions' => ['*']],
        'admin' => ['name' => 'مدير', 'permissions' => [
            'access-admin', 'manage-products', 'manage-categories', 'manage-orders', 'manage-customers',
            'manage-coupons', 'manage-shipping', 'manage-reviews', 'manage-settings', 'delete-records',
        ]],
        'manager' => ['name' => 'مشرف', 'permissions' => [
            'access-admin', 'manage-products', 'manage-categories', 'manage-orders', 'manage-reviews',
        ]],
        'courier' => ['name' => 'مندوب توصيل', 'permissions' => []],
        'customer' => ['name' => 'عميل', 'permissions' => []],
    ];

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }
}
