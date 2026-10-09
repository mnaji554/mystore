<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_company_id', 'name', 'description', 'price', 'is_free', 'free_shipping_threshold',
        'delivery_days_min', 'delivery_days_max', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('shipping_company_id')
                ->orWhereHas('company', fn ($company) => $company->where('is_active', true)));
    }

    public function getDeliveryEstimateAttribute(): string
    {
        return $this->delivery_days_min === $this->delivery_days_max
            ? "{$this->delivery_days_min} أيام"
            : "{$this->delivery_days_min} - {$this->delivery_days_max} أيام";
    }
}
