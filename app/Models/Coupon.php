<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    public const PERCENTAGE = 'percentage';

    public const FIXED = 'fixed';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_order_amount', 'max_discount', 'usage_limit',
        'usage_limit_per_user', 'starts_at', 'expires_at', 'is_active', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isGlobal(): bool
    {
        return $this->products->isEmpty() && $this->categories->isEmpty();
    }

    public function getLabelAttribute(): string
    {
        return $this->type === self::PERCENTAGE
            ? rtrim(rtrim((string) $this->value, '0'), '.').'%'
            : money($this->value);
    }
}
