<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'category_id', 'name_ar', 'name_en', 'slug', 'sku', 'description', 'short_description',
        'price', 'sale_price', 'cost_price', 'stock', 'weight', 'status', 'is_featured',
        'is_bestseller', 'is_new', 'seo_title', 'seo_description', 'seo_keywords',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'rating_avg' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->discount_percent = $product->hasSalePrice()
                ? (int) round((1 - (float) $product->sale_price / (float) $product->price) * 100)
                : 0;
        });

        $flush = fn () => collect(['home.featured', 'home.new', 'home.bestsellers', 'home.sale'])
            ->each(fn ($key) => Cache::forget($key));
        static::saved($flush);
        static::deleted($flush);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function hasSalePrice(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price > 0
            && (float) $this->sale_price < (float) $this->price;
    }

    /** Price a customer pays for the base product (no variant). */
    public function getFinalPriceAttribute(): float
    {
        return $this->hasSalePrice() ? (float) $this->sale_price : (float) $this->price;
    }

    public function getNameAttribute(): string
    {
        return $this->name_ar;
    }

    public function getUrlAttribute(): string
    {
        return route('products.show', $this->slug);
    }

    public function getHasVariantsAttribute(): bool
    {
        return $this->variants->where('is_active', true)->isNotEmpty();
    }

    /** Lowest price a customer can pay (considers active variants). */
    public function getFromPriceAttribute(): float
    {
        if ($this->has_variants) {
            return (float) $this->variants->where('is_active', true)->min(fn ($v) => $v->final_price);
        }

        return $this->final_price;
    }

    public function getFromRegularPriceAttribute(): float
    {
        if ($this->has_variants) {
            $cheapest = $this->variants->where('is_active', true)->sortBy(fn ($v) => $v->final_price)->first();

            return (float) $cheapest->price;
        }

        return (float) $this->price;
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->has_variants
            ? (int) $this->variants->where('is_active', true)->sum('stock')
            : (int) $this->stock;
    }

    public function getInStockAttribute(): bool
    {
        return $this->available_stock > 0;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->primaryImage?->url;
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->primaryImage?->thumb_url;
    }
}
