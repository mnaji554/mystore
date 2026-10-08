<?php

namespace App\Repositories;

use App\Contracts\ProductSearch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    public const SORTS = [
        'latest' => 'الأحدث',
        'bestselling' => 'الأكثر مبيعاً',
        'price_asc' => 'السعر: من الأقل للأعلى',
        'price_desc' => 'السعر: من الأعلى للأقل',
        'rating' => 'الأعلى تقييماً',
        'discount' => 'أعلى خصم',
    ];

    public function __construct(private readonly ProductSearch $search) {}

    /** Active products with everything a product card needs (no N+1). */
    public function cardQuery(): Builder
    {
        return Product::query()->active()->with(['primaryImage', 'variants', 'category:id,name_ar,slug']);
    }

    /**
     * @param  array{q?:?string,category?:?Category,min_price?:mixed,max_price?:mixed,sort?:?string,mode?:?string,in_stock?:mixed}  $filters
     */
    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->cardQuery();

        if (filled($filters['q'] ?? null)) {
            $this->search->apply($query, $filters['q']);
        }

        if (($filters['category'] ?? null) instanceof Category) {
            $query->whereIn('products.category_id', $filters['category']->descendantIds());
        }

        match ($filters['mode'] ?? null) {
            'new' => $query->where('is_new', true),
            'sale' => $query->where('discount_percent', '>', 0),
            'bestsellers' => $query->where(fn ($q) => $q->where('is_bestseller', true)->orWhere('sales_count', '>', 0)),
            default => null,
        };

        if (is_numeric($filters['min_price'] ?? null)) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (is_numeric($filters['max_price'] ?? null)) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['in_stock'])) {
            $query->where(fn ($q) => $q->where('stock', '>', 0)
                ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)));
        }

        $sort = $filters['sort'] ?? null;

        if (! $sort || ! array_key_exists($sort, self::SORTS)) {
            $sort = ($filters['mode'] ?? null) === 'bestsellers' ? 'bestselling' : (filled($filters['q'] ?? null) ? null : 'latest');
        }

        match ($sort) {
            'bestselling' => $query->orderByDesc('sales_count')->orderByDesc('id'),
            'price_asc' => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            'discount' => $query->orderByDesc('discount_percent')->orderByDesc('id'),
            'latest' => $query->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function findActiveBySlug(string $slug): ?Product
    {
        return Product::query()->active()
            ->with(['images', 'variants.options', 'category.parent'])
            ->where('slug', $slug)->first();
    }

    public function related(Product $product, int $limit = 4): Collection
    {
        return $this->cardQuery()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->inRandomOrder()->limit($limit)->get();
    }

    public function featured(int $limit = 8): Collection
    {
        return Cache::remember('home.featured', 600, fn () => $this->cardQuery()->where('is_featured', true)->latest('id')->limit($limit)->get());
    }

    public function newest(int $limit = 8): Collection
    {
        return Cache::remember('home.new', 600, fn () => $this->cardQuery()->where('is_new', true)->latest('id')->limit($limit)->get());
    }

    public function bestsellers(int $limit = 8): Collection
    {
        return Cache::remember('home.bestsellers', 600, fn () => $this->cardQuery()
            ->orderByDesc('is_bestseller')->orderByDesc('sales_count')->limit($limit)->get());
    }

    public function onSale(int $limit = 8): Collection
    {
        return Cache::remember('home.sale', 600, fn () => $this->cardQuery()
            ->where('discount_percent', '>', 0)->orderByDesc('discount_percent')->limit($limit)->get());
    }
}
