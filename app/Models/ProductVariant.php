<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'sku', 'price', 'sale_price', 'stock', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class, 'variant_id');
    }

    public function hasSalePrice(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price > 0
            && (float) $this->sale_price < (float) $this->price;
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->hasSalePrice() ? (float) $this->sale_price : (float) $this->price;
    }

    public function getLabelAttribute(): string
    {
        return $this->options->map(fn ($o) => $o->value)->implode(' / ');
    }

    public function option(string $name): ?VariantOption
    {
        return $this->options->firstWhere('name', $name);
    }
}
