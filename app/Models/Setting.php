<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $table = 'store_settings';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'متجري',
        'store_tagline' => 'كل ما تحتاجه في مكان واحد',
        'logo' => null,
        'currency' => 'SAR',
        'currency_symbol' => 'ر.س',
        'tax_enabled' => '1',
        'tax_rate' => '15',
        'low_stock_threshold' => '5',
        'contact_email' => 'support@mystore.test',
        'contact_phone' => '+966 50 000 0000',
        'contact_address' => 'الرياض، المملكة العربية السعودية',
        'vat_number' => '',
        'meta_description' => 'متجر إلكتروني عربي متكامل لأفضل المنتجات بأسعار مناسبة وشحن سريع.',
    ];

    public static function allValues(): array
    {
        return Cache::rememberForever('store.settings', function () {
            try {
                $stored = self::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                $stored = [];
            }

            return array_merge(self::DEFAULTS, $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::allValues()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        Cache::forget('store.settings');
    }

    public static function logoUrl(): ?string
    {
        $logo = self::get('logo');

        return $logo ? Storage::disk('public')->url($logo) : null;
    }
}
