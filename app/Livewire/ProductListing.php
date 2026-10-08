<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithStore;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductListing extends Component
{
    use InteractsWithStore, WithPagination;

    public string $mode = 'all'; // all | category | new | bestsellers | sale | search

    public ?string $slug = null;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url]
    public string $sort = '';

    #[Url]
    public ?string $min = null;

    #[Url]
    public ?string $max = null;

    #[Url]
    public bool $inStock = false;

    public function mount(string $mode = 'all', ?string $slug = null): void
    {
        $this->mode = $mode;
        $this->slug = $slug;

        if ($mode === 'category') {
            abort_unless(app(CategoryRepository::class)->findActiveBySlug((string) $slug), 404);
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'q') {
            $this->q = mb_substr(trim($this->q), 0, 100);
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('min', 'max', 'inStock', 'sort');
        $this->resetPage();
    }

    private function heading(?Category $category): array
    {
        return match ($this->mode) {
            'category' => [$category?->name_ar, $category?->description],
            'new' => ['وصل حديثاً', 'أحدث المنتجات التي وصلت إلى المتجر.'],
            'bestsellers' => ['الأكثر مبيعاً', 'المنتجات التي يفضلها عملاؤنا.'],
            'sale' => ['المنتجات المخفضة', 'خصومات حقيقية على منتجات مختارة.'],
            'search' => [$this->q !== '' ? 'نتائج البحث عن «'.$this->q.'»' : 'البحث', null],
            default => ['كل المنتجات', 'تصفح جميع منتجات المتجر.'],
        };
    }

    public function render(ProductRepository $repository, CategoryRepository $categories)
    {
        // Query-string values are user input: drop invalid UTF-8 so the component state stays JSON-serializable.
        foreach (['q', 'sort', 'min', 'max'] as $property) {
            if (is_string($this->{$property})) {
                $this->{$property} = mb_scrub($this->{$property}, 'UTF-8');
            }
        }

        $category = $this->mode === 'category' ? $categories->findActiveBySlug((string) $this->slug) : null;

        $products = $repository->paginate([
            'q' => in_array($this->mode, ['search', 'all', 'category'], true) ? $this->q : null,
            'category' => $category,
            'mode' => $this->mode,
            'min_price' => $this->min,
            'max_price' => $this->max,
            'sort' => $this->sort,
            'in_stock' => $this->inStock,
        ], (int) config('store.pagination.products'));

        [$heading, $intro] = $this->heading($category);

        $crumbs = [];
        if ($category) {
            if ($category->parent) {
                $crumbs[$category->parent->name_ar] = $category->parent->url;
            }
            $crumbs[$category->name_ar] = null;
        } else {
            $crumbs[$heading] = null;
        }

        $jsonLd = null;
        if ($category) {
            $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => route('home')]];
            $position = 2;
            foreach ($crumbs as $label => $url) {
                $items[] = ['@type' => 'ListItem', 'position' => $position++, 'name' => $label, 'item' => $url ?? $category->url];
            }
            $jsonLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }

        $canonical = match ($this->mode) {
            'category' => $category?->url,
            'new' => route('products.new'),
            'bestsellers' => route('products.bestsellers'),
            'sale' => route('products.sale'),
            'search' => route('search'),
            default => route('products.index'),
        };

        return view('livewire.product-listing', [
            'products' => $products,
            'category' => $category,
            'heading' => $heading,
            'intro' => $intro,
            'crumbs' => $crumbs,
            'sidebarCategories' => $categories->tree(),
            'sorts' => ProductRepository::SORTS,
        ])->layout('components.layouts.app', [
            'title' => $category?->seo_title ?: $heading,
            'description' => $category?->seo_description ?: $intro,
            'canonical' => $canonical,
            'noindex' => $this->mode === 'search',
            'jsonLd' => $jsonLd,
        ]);
    }
}
