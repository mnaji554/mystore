<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCompany extends Model
{
    protected $fillable = ['name', 'tracking_url_template', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function trackingUrlFor(?string $number): ?string
    {
        if (! $number || ! $this->tracking_url_template) {
            return null;
        }

        return str_replace('{number}', urlencode($number), $this->tracking_url_template);
    }
}
