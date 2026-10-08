<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $symbol = Setting::get('currency_symbol', 'ر.س');

        return number_format((float) $amount, 2).' '.$symbol;
    }
}
