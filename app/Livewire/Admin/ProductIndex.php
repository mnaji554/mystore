<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use App\Services\ReportService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $category = null;

    #[Url]
    public string $stock = '';

    #[Url]
    public string $sort = 'id';

    #[Url]
    public string $dir = 'desc';

    public array $selected = [];

    public bool $selectPage = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'status', 'category', 'stock'], true)) {
            $this->resetPage();
            $this->selected = [];
            $this->selectPage = false;
        }
    }

    public function sortBy(string $column): void
    {
        abort_unless(in_array($column, ['id', 'name_ar', 'price', 'stock', 'sales_count', 'created_at'], true), 422);

        $this->dir = ($this->sort === $column && $this->dir === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value ? $this->query()->paginate(15)->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }

    private function query()
    {
        return Product::query()
            ->with(['primaryImage', 'category:id,name_ar', 'variants:id,product_id,stock,is_active'])
            ->when($this->search !== '', function ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
                $q->where(fn ($w) => $w->where('name_ar', 'like', $like)->orWhere('name_en', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->whereIn('category_id', Category::find($this->category)?->descendantIds() ?? [$this->category]))
            ->when($this->stock === 'low', fn ($q) => $q->whereIn('products.id', app(ReportService::class)->lowStockQuery()->select('products.id')))
            ->when($this->stock === 'out', fn ($q) => $q->where('stock', '<=', 0)->whereDoesntHave('variants'))
            ->orderBy(in_array($this->sort, ['id', 'name_ar', 'price', 'stock', 'sales_count', 'created_at'], true) ? $this->sort : 'id', $this->dir === 'asc' ? 'asc' : 'desc');
    }

    public function toggleStatus(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);
        $product->update(['status' => $product->status === Product::STATUS_ACTIVE ? Product::STATUS_DRAFT : Product::STATUS_ACTIVE]);
    }

    public function updateStock(int $id, int $stock): void
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);
        $product->update(['stock' => max(0, $stock)]);
        $this->dispatch('notify', message: 'تم تحديث المخزون', type: 'success');
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);
        $product->delete();
        $this->selected = array_values(array_diff($this->selected, [(string) $id]));
        $this->dispatch('notify', message: 'تم حذف المنتج', type: 'success');
    }

    public function bulkDelete(): void
    {
        $this->authorize('delete', new Product);

        $count = Product::query()->whereIn('id', array_map('intval', $this->selected))->get()->each->delete()->count();

        $this->selected = [];
        $this->selectPage = false;
        $this->dispatch('notify', message: "تم حذف {$count} منتج", type: 'success');
    }

    public function render(CategoryRepository $categories)
    {
        return view('livewire.admin.product-index', [
            'products' => $this->query()->paginate(15),
            'categoryOptions' => $categories->flat(),
        ])->layout('components.layouts.admin', ['title' => 'المنتجات']);
    }
}
