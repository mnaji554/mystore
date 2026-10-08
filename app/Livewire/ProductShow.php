<?php

namespace App\Livewire;

use App\Exceptions\StoreException;
use App\Livewire\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Repositories\ProductRepository;
use App\Services\ReviewService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductShow extends Component
{
    use InteractsWithStore, WithFileUploads;

    public const OPTION_LABELS = ['color' => 'اللون', 'size' => 'المقاس'];

    public string $slug;

    /** @var array<string, string> */
    public array $selected = [];

    public int $quantity = 1;

    public int $reviewsLimit = 5;

    public int $rating = 5;

    public string $title = '';

    public string $comment = '';

    public array $photos = [];

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        abort_unless($this->product, 404);

        $first = $this->product->variants->where('is_active', true)->sortByDesc(fn ($v) => $v->stock > 0)->first();

        foreach ($first?->options ?? [] as $option) {
            $this->selected[$option->name] = $option->value;
        }

        $mine = $this->myReview;
        if ($mine) {
            $this->rating = $mine->rating;
            $this->title = (string) $mine->title;
            $this->comment = (string) $mine->comment;
        }
    }

    #[Computed]
    public function product(): ?Product
    {
        return app(ProductRepository::class)->findActiveBySlug($this->slug);
    }

    /** @return Collection<string, Collection> option name => list of [value, color_hex] */
    #[Computed]
    public function optionGroups(): Collection
    {
        return $this->product->variants->where('is_active', true)
            ->flatMap->options
            ->groupBy('name')
            ->map(fn ($options) => $options->unique('value')->values());
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        $variants = $this->product->variants->where('is_active', true);

        if ($variants->isEmpty()) {
            return null;
        }

        return $variants->first(fn ($v) => $v->options->isNotEmpty()
            && $v->options->every(fn ($o) => ($this->selected[$o->name] ?? null) === $o->value));
    }

    #[Computed]
    public function stock(): int
    {
        if ($this->product->has_variants) {
            return (int) ($this->variant?->stock ?? 0);
        }

        return (int) $this->product->stock;
    }

    #[Computed]
    public function reviews(): Collection
    {
        return Review::query()->approved()->where('product_id', $this->product->id)
            ->with(['user:id,name', 'images'])->latest('id')->limit($this->reviewsLimit)->get();
    }

    #[Computed]
    public function myReview(): ?Review
    {
        return auth()->check()
            ? Review::query()->with('images')->where('product_id', $this->product->id)->where('user_id', auth()->id())->first()
            : null;
    }

    #[Computed]
    public function canReview(): bool
    {
        return auth()->check() && app(ReviewService::class)->hasPurchased(auth()->user(), $this->product);
    }

    public function selectOption(string $name, string $value): void
    {
        $this->selected[$name] = $value;
        $this->quantity = 1;
        unset($this->variant, $this->stock);
    }

    public function increment(): void
    {
        $this->quantity = min($this->quantity + 1, max($this->stock, 1), 50);
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addSelectedToCart(): void
    {
        $variant = $this->variant;

        if ($this->product->has_variants && ! $variant) {
            $this->dispatch('notify', message: 'هذه التركيبة من الخيارات غير متوفرة.', type: 'error');

            return;
        }

        $this->addToCart($this->product->id, $variant?->id, max(1, $this->quantity));
    }

    public function loadMoreReviews(): void
    {
        $this->reviewsLimit += 5;
        unset($this->reviews);
    }

    public function saveReview(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photos' => ['array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        try {
            app(ReviewService::class)->save(auth()->user(), $this->product, [
                'rating' => $this->rating,
                'title' => $this->title ?: null,
                'comment' => $this->comment ?: null,
            ], $this->photos);
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->photos = [];
        unset($this->myReview, $this->product);
        $this->dispatch('notify', message: 'شكراً لك! سيظهر تقييمك بعد موافقة الإدارة.', type: 'success');
    }

    public function deleteReview(): void
    {
        $review = $this->myReview;
        abort_unless($review, 404);
        $this->authorize('delete', $review);

        app(ReviewService::class)->delete($review);
        $this->reset('title', 'comment', 'photos');
        $this->rating = 5;
        unset($this->myReview, $this->reviews, $this->product);
        $this->dispatch('notify', message: 'تم حذف تقييمك.', type: 'success');
    }

    private function jsonLd(Product $product): array
    {
        $images = $product->images->map->url->all();
        $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => route('home')]];
        $position = 2;

        foreach (array_filter([$product->category?->parent, $product->category]) as $cat) {
            $crumbs[] = ['@type' => 'ListItem', 'position' => $position++, 'name' => $cat->name_ar, 'item' => $cat->url];
        }
        $crumbs[] = ['@type' => 'ListItem', 'position' => $position, 'name' => $product->name_ar, 'item' => $product->url];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name_ar,
            'sku' => $product->sku,
            'description' => strip_tags($product->short_description ?: (string) $product->description),
            'image' => $images,
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->url,
                'priceCurrency' => setting('currency', 'SAR'),
                'price' => number_format($product->from_price, 2, '.', ''),
                'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($product->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $product->rating_avg,
                'reviewCount' => $product->reviews_count,
            ];
        }

        return [$schema, ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs]];
    }

    public function render()
    {
        $product = $this->product;
        $related = app(ProductRepository::class)->related($product);

        return view('livewire.product-show', [
            'product' => $product,
            'related' => $related,
        ])->layout('components.layouts.app', [
            'title' => $product->seo_title ?: $product->name_ar,
            'description' => $product->seo_description ?: ($product->short_description ?: $product->description),
            'canonical' => $product->url,
            'image' => $product->image_url,
            'ogType' => 'product',
            'jsonLd' => $this->jsonLd($product),
        ]);
    }
}
