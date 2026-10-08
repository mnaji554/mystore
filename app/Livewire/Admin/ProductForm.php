<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Repositories\CategoryRepository;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    public const MAX_IMAGES = 8;

    public ?int $productId = null;

    public array $form = [];

    public array $variants = [];

    public array $removedVariantIds = [];

    public array $uploads = [];

    public function mount(?Product $product = null): void
    {
        $this->form = [
            'name_ar' => '', 'name_en' => '', 'slug' => '', 'sku' => '', 'category_id' => '',
            'description' => '', 'short_description' => '', 'price' => '', 'sale_price' => '', 'cost_price' => '',
            'stock' => 0, 'weight' => '', 'status' => Product::STATUS_ACTIVE,
            'is_featured' => false, 'is_bestseller' => false, 'is_new' => true,
            'seo_title' => '', 'seo_description' => '', 'seo_keywords' => '',
        ];

        if ($product?->exists) {
            $this->authorize('update', $product);
            $this->productId = $product->id;

            foreach (array_keys($this->form) as $key) {
                $value = $product->{$key};
                $this->form[$key] = is_bool($value) ? $value : ($value ?? '');
            }
            $this->form['price'] = (string) $product->price;
            $this->form['sale_price'] = $product->sale_price !== null ? (string) $product->sale_price : '';
            $this->form['cost_price'] = $product->cost_price !== null ? (string) $product->cost_price : '';
            $this->form['weight'] = $product->weight !== null ? (string) (float) $product->weight : '';
            $this->form['category_id'] = (string) ($product->category_id ?? '');

            $this->variants = $product->variants()->with('options')->get()->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'price' => (string) $v->price,
                'sale_price' => $v->sale_price !== null ? (string) $v->sale_price : '',
                'stock' => $v->stock,
                'is_active' => $v->is_active,
                'color' => (string) $v->option('color')?->value,
                'color_hex' => (string) ($v->option('color')?->color_hex ?: '#000000'),
                'size' => (string) $v->option('size')?->value,
            ])->all();
        } else {
            $this->authorize('create', Product::class);
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null, 'sku' => ($this->form['sku'] ?: 'SKU').'-'.(count($this->variants) + 1),
            'price' => $this->form['price'], 'sale_price' => '', 'stock' => 0, 'is_active' => true,
            'color' => '', 'color_hex' => '#000000', 'size' => '',
        ];
    }

    public function removeVariant(int $index): void
    {
        if (! empty($this->variants[$index]['id'])) {
            $this->removedVariantIds[] = $this->variants[$index]['id'];
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    protected function rules(): array
    {
        return [
            'form.name_ar' => ['required', 'string', 'max:190'],
            'form.name_en' => ['nullable', 'string', 'max:190'],
            'form.slug' => ['nullable', 'string', 'max:190', 'regex:/^[\p{L}\p{N}\-_]+$/u', Rule::unique('products', 'slug')->ignore($this->productId)],
            'form.sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->productId)],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.description' => ['nullable', 'string', 'max:20000'],
            'form.short_description' => ['nullable', 'string', 'max:1000'],
            'form.price' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'form.sale_price' => ['nullable', 'numeric', 'min:0.01', 'lt:form.price'],
            'form.cost_price' => ['nullable', 'numeric', 'min:0'],
            'form.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'form.weight' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'form.status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_DRAFT])],
            'form.is_featured' => ['boolean'],
            'form.is_bestseller' => ['boolean'],
            'form.is_new' => ['boolean'],
            'form.seo_title' => ['nullable', 'string', 'max:190'],
            'form.seo_description' => ['nullable', 'string', 'max:500'],
            'form.seo_keywords' => ['nullable', 'string', 'max:255'],
            'variants' => ['array', 'max:100'],
            'variants.*.sku' => ['required', 'string', 'max:80'],
            'variants.*.price' => ['required', 'numeric', 'min:0.01'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0.01'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['boolean'],
            'variants.*.color' => ['nullable', 'string', 'max:60'],
            'variants.*.color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'variants.*.size' => ['nullable', 'string', 'max:60'],
            'uploads' => ['array', 'max:'.self::MAX_IMAGES],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'unique' => ':attribute مستخدم مسبقاً.',
            'numeric' => ':attribute يجب أن يكون رقماً.',
            'integer' => ':attribute يجب أن يكون رقماً صحيحاً.',
            'min' => 'قيمة :attribute صغيرة جداً.',
            'max' => 'قيمة :attribute كبيرة جداً.',
            'lt' => 'سعر الخصم يجب أن يكون أقل من السعر الأصلي.',
            'image' => 'الملف يجب أن يكون صورة.',
            'mimes' => 'صيغة الصورة غير مدعومة.',
            'regex' => 'صيغة :attribute غير صحيحة.',
            'exists' => 'القيمة المختارة غير صحيحة.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name_ar' => 'الاسم بالعربية', 'form.name_en' => 'الاسم بالإنجليزية', 'form.slug' => 'الرابط (Slug)',
            'form.sku' => 'SKU', 'form.price' => 'السعر', 'form.sale_price' => 'سعر الخصم', 'form.cost_price' => 'سعر التكلفة',
            'form.stock' => 'المخزون', 'form.weight' => 'الوزن', 'form.category_id' => 'التصنيف',
            'variants.*.sku' => 'SKU المتغير', 'variants.*.price' => 'سعر المتغير', 'variants.*.stock' => 'مخزون المتغير',
            'variants.*.sale_price' => 'سعر خصم المتغير', 'variants.*.color_hex' => 'لون المتغير',
            'uploads' => 'الصور', 'uploads.*' => 'الصورة',
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'product';
        $candidate = $slug;
        $i = 2;

        while (Product::withTrashed()->where('slug', $candidate)->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }

    private function validateVariantSkus(): bool
    {
        $seen = [$this->form['sku']];
        $ok = true;

        foreach ($this->variants as $i => $variant) {
            $sku = trim($variant['sku']);
            $clash = in_array($sku, $seen, true)
                || ProductVariant::withTrashed()->where('sku', $sku)->when($variant['id'], fn ($q) => $q->where('id', '!=', $variant['id']))->exists()
                || Product::withTrashed()->where('sku', $sku)->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))->exists();

            if ($clash) {
                $this->addError("variants.$i.sku", 'SKU مستخدم مسبقاً.');
                $ok = false;
            }

            if ($variant['sale_price'] !== '' && (float) $variant['sale_price'] >= (float) $variant['price']) {
                $this->addError("variants.$i.sale_price", 'سعر الخصم يجب أن يكون أقل من السعر.');
                $ok = false;
            }

            if (trim($variant['color']) === '' && trim($variant['size']) === '') {
                $this->addError("variants.$i.color", 'حدد اللون أو المقاس.');
                $ok = false;
            }

            $seen[] = $sku;
        }

        return $ok;
    }

    public function save(ImageService $images)
    {
        $product = $this->productId ? Product::findOrFail($this->productId) : null;
        $this->authorize($product ? 'update' : 'create', $product ?? Product::class);

        $this->validate();

        if (! $this->validateVariantSkus()) {
            return;
        }

        $existingCount = $product ? $product->images()->count() : 0;
        if ($existingCount + count($this->uploads) > self::MAX_IMAGES) {
            $this->addError('uploads', 'الحد الأقصى '.self::MAX_IMAGES.' صور للمنتج.');

            return;
        }

        $f = $this->form;
        $slugSource = trim($f['slug']) !== '' ? Str::lower(trim($f['slug'])) : Str::slug($f['name_en'] ?: $f['name_ar'], '-', 'ar');

        $data = [
            'category_id' => $f['category_id'] !== '' ? (int) $f['category_id'] : null,
            'name_ar' => strip_tags($f['name_ar']),
            'name_en' => $f['name_en'] !== '' ? strip_tags($f['name_en']) : null,
            'slug' => $this->uniqueSlug($slugSource),
            'sku' => trim($f['sku']),
            'description' => $f['description'] !== '' ? strip_tags($f['description']) : null,
            'short_description' => $f['short_description'] !== '' ? strip_tags($f['short_description']) : null,
            'price' => $f['price'],
            'sale_price' => $f['sale_price'] !== '' ? $f['sale_price'] : null,
            'cost_price' => $f['cost_price'] !== '' ? $f['cost_price'] : null,
            'stock' => (int) $f['stock'],
            'weight' => $f['weight'] !== '' ? $f['weight'] : null,
            'status' => $f['status'],
            'is_featured' => (bool) $f['is_featured'],
            'is_bestseller' => (bool) $f['is_bestseller'],
            'is_new' => (bool) $f['is_new'],
            'seo_title' => $f['seo_title'] ?: null,
            'seo_description' => $f['seo_description'] ?: null,
            'seo_keywords' => $f['seo_keywords'] ?: null,
        ];

        $stored = [];

        try {
            $product = DB::transaction(function () use ($product, $data, $images, &$stored) {
                $product ??= new Product;
                $product->fill($data)->save();

                $this->syncVariants($product);

                $hasPrimary = $product->images()->where('is_primary', true)->exists();
                $order = (int) $product->images()->max('sort_order');

                foreach ($this->uploads as $upload) {
                    $paths = $images->store($upload, "products/{$product->id}");
                    $stored[] = $paths;

                    $product->images()->create([
                        'path' => $paths['path'],
                        'thumb_path' => $paths['thumb_path'],
                        'alt' => $product->name_ar,
                        'sort_order' => ++$order,
                        'is_primary' => ! $hasPrimary,
                    ]);
                    $hasPrimary = true;
                }

                return $product;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $paths) {
                $images->delete($paths['path'], $paths['thumb_path']);
            }
            report($e);
            $this->dispatch('notify', message: 'تعذر حفظ المنتج: '.$e->getMessage(), type: 'error');

            return;
        }

        session()->flash('success', 'تم حفظ المنتج بنجاح.');

        return $this->redirectRoute('admin.products.edit', $product);
    }

    private function syncVariants(Product $product): void
    {
        if ($this->removedVariantIds) {
            ProductVariant::withTrashed()->where('product_id', $product->id)->whereIn('id', $this->removedVariantIds)->forceDelete();
        }

        foreach ($this->variants as $row) {
            $variant = $row['id']
                ? ProductVariant::withTrashed()->where('product_id', $product->id)->findOrFail($row['id'])
                : new ProductVariant(['product_id' => $product->id]);

            $variant->fill([
                'sku' => trim($row['sku']),
                'price' => $row['price'],
                'sale_price' => $row['sale_price'] !== '' ? $row['sale_price'] : null,
                'stock' => (int) $row['stock'],
                'is_active' => (bool) $row['is_active'],
            ])->save();

            $variant->options()->delete();

            if (trim($row['color']) !== '') {
                $variant->options()->create(['name' => 'color', 'value' => strip_tags(trim($row['color'])), 'color_hex' => $row['color_hex'] ?: null]);
            }
            if (trim($row['size']) !== '') {
                $variant->options()->create(['name' => 'size', 'value' => strip_tags(trim($row['size']))]);
            }
        }
    }

    private function ownedImage(int $id): ProductImage
    {
        abort_unless($this->productId, 404);

        return ProductImage::where('product_id', $this->productId)->findOrFail($id);
    }

    public function setPrimary(int $id): void
    {
        $this->authorize('update', Product::findOrFail($this->productId));
        $image = $this->ownedImage($id);

        ProductImage::where('product_id', $this->productId)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
    }

    public function deleteImage(int $id, ImageService $images): void
    {
        $this->authorize('update', Product::findOrFail($this->productId));
        $image = $this->ownedImage($id);
        $wasPrimary = $image->is_primary;

        $images->delete($image->path, $image->thumb_path);
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $this->productId)->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }
    }

    public function moveImage(int $id, string $direction): void
    {
        $this->authorize('update', Product::findOrFail($this->productId));
        $this->ownedImage($id);

        $list = ProductImage::where('product_id', $this->productId)->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $list->search(fn ($i) => $i->id === $id);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swap < 0 || $swap >= $list->count()) {
            return;
        }

        $order = $list->pluck('id')->all();
        [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];

        foreach ($order as $position => $imageId) {
            ProductImage::whereKey($imageId)->update(['sort_order' => $position + 1]);
        }
    }

    public function render(CategoryRepository $categories)
    {
        $images = $this->productId
            ? ProductImage::where('product_id', $this->productId)->orderByDesc('is_primary')->orderBy('sort_order')->get()
            : collect();

        return view('livewire.admin.product-form', [
            'categoryOptions' => $categories->flat(),
            'images' => $images,
        ])->layout('components.layouts.admin', ['title' => $this->productId ? 'تعديل منتج' : 'منتج جديد']);
    }
}
