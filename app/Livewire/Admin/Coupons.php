<?php

namespace App\Livewire\Admin;

use App\Models\Coupon;
use App\Models\Product;
use App\Repositories\CategoryRepository;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Coupons extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [];

    public array $productIds = [];

    public array $categoryIds = [];

    public string $productSearch = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Coupon::class);
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form = [
            'code' => '', 'description' => '', 'type' => Coupon::PERCENTAGE, 'value' => '', 'min_order_amount' => '',
            'max_discount' => '', 'usage_limit' => '', 'usage_limit_per_user' => '', 'starts_at' => '', 'expires_at' => '',
            'is_active' => true, 'is_public' => false,
        ];
        $this->productIds = [];
        $this->categoryIds = [];
        $this->productSearch = '';
        $this->editingId = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->authorize('create', Coupon::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $coupon = Coupon::with(['products:id', 'categories:id'])->findOrFail($id);
        $this->authorize('update', $coupon);

        $this->resetForm();
        $this->editingId = $id;

        foreach (['code', 'description', 'type', 'value', 'min_order_amount', 'max_discount', 'usage_limit', 'usage_limit_per_user'] as $key) {
            $this->form[$key] = $coupon->{$key} ?? '';
        }
        foreach (['value', 'min_order_amount', 'max_discount'] as $key) {
            $this->form[$key] = $coupon->{$key} !== null ? (string) (float) $coupon->{$key} : '';
        }
        $this->form['starts_at'] = $coupon->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->form['expires_at'] = $coupon->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->form['is_active'] = $coupon->is_active;
        $this->form['is_public'] = $coupon->is_public;
        $this->productIds = $coupon->products->pluck('id')->all();
        $this->categoryIds = $coupon->categories->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showForm = true;
    }

    public function addProduct(int $id): void
    {
        if (! in_array($id, $this->productIds, true) && Product::whereKey($id)->exists()) {
            $this->productIds[] = $id;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $id): void
    {
        $this->productIds = array_values(array_diff($this->productIds, [$id]));
    }

    protected function rules(): array
    {
        return [
            'form.code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]+$/', Rule::unique('coupons', 'code')->ignore($this->editingId)],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.type' => ['required', Rule::in([Coupon::PERCENTAGE, Coupon::FIXED])],
            'form.value' => ['required', 'numeric', 'gt:0', $this->form['type'] === Coupon::PERCENTAGE ? 'max:100' : 'max:9999999'],
            'form.min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'form.max_discount' => ['nullable', 'numeric', 'gt:0'],
            'form.usage_limit' => ['nullable', 'integer', 'min:1'],
            'form.usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'form.starts_at' => ['nullable', 'date'],
            'form.expires_at' => ['nullable', 'date', 'after:form.starts_at'],
            'form.is_active' => ['boolean'],
            'form.is_public' => ['boolean'],
            'productIds.*' => ['integer', 'exists:products,id'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'هذا الحقل مطلوب.', 'unique' => 'الكود مستخدم مسبقاً.', 'regex' => 'الكود يجب أن يحتوي على أحرف إنجليزية وأرقام فقط.',
            'gt' => 'القيمة يجب أن تكون أكبر من صفر.', 'max' => 'القيمة كبيرة جداً (النسبة حتى 100).', 'numeric' => 'أدخل رقماً صحيحاً.',
            'after' => 'تاريخ الانتهاء يجب أن يكون بعد البداية.', 'date' => 'تاريخ غير صحيح.', 'integer' => 'أدخل رقماً صحيحاً.', 'min' => 'القيمة صغيرة جداً.',
        ];
    }

    public function save(): void
    {
        $coupon = $this->editingId ? Coupon::findOrFail($this->editingId) : null;
        $this->authorize($coupon ? 'update' : 'create', $coupon ?? Coupon::class);

        $this->validate();

        $nullable = fn ($v) => $v === '' || $v === null ? null : $v;
        $data = [
            'code' => $this->form['code'],
            'description' => $nullable($this->form['description']),
            'type' => $this->form['type'],
            'value' => $this->form['value'],
            'min_order_amount' => $nullable($this->form['min_order_amount']),
            'max_discount' => $nullable($this->form['max_discount']),
            'usage_limit' => $nullable($this->form['usage_limit']),
            'usage_limit_per_user' => $nullable($this->form['usage_limit_per_user']),
            'starts_at' => $nullable($this->form['starts_at']),
            'expires_at' => $nullable($this->form['expires_at']),
            'is_active' => (bool) $this->form['is_active'],
            'is_public' => (bool) $this->form['is_public'],
        ];

        $coupon ??= new Coupon;
        $coupon->fill($data)->save();
        $coupon->products()->sync($this->productIds);
        $coupon->categories()->sync(array_map('intval', $this->categoryIds));

        $this->resetForm();
        $this->dispatch('notify', message: 'تم حفظ الكوبون', type: 'success');
    }

    public function toggle(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->authorize('update', $coupon);
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function delete(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->authorize('delete', $coupon);
        $coupon->delete();
        $this->dispatch('notify', message: 'تم حذف الكوبون', type: 'success');
    }

    public function render(CategoryRepository $categories)
    {
        $results = strlen($this->productSearch) >= 2
            ? Product::query()->where(fn ($q) => $q->where('name_ar', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->productSearch).'%')->orWhere('sku', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->productSearch).'%'))
                ->whereNotIn('id', $this->productIds)->limit(6)->get(['id', 'name_ar', 'sku'])
            : collect();

        return view('livewire.admin.coupons', [
            'coupons' => Coupon::query()->withCount(['products', 'categories'])->latest('id')->paginate(12),
            'categoryOptions' => $categories->flat(),
            'selectedProducts' => Product::whereIn('id', $this->productIds)->get(['id', 'name_ar']),
            'results' => $results,
        ])->layout('components.layouts.admin', ['title' => 'الكوبونات']);
    }
}
