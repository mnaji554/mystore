<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="بحث بالاسم أو SKU…" class="input !w-56" aria-label="بحث">
            <select wire:model.live="status" class="input !w-auto" aria-label="الحالة">
                <option value="">كل الحالات</option><option value="active">منشور</option><option value="draft">مسودة</option>
            </select>
            <select wire:model.live="category" class="input !w-auto" aria-label="التصنيف">
                <option value="">كل التصنيفات</option>
                @foreach($categoryOptions as $opt)<option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>@endforeach
            </select>
            <select wire:model.live="stock" class="input !w-auto" aria-label="المخزون">
                <option value="">كل المخزون</option><option value="low">منخفض</option><option value="out">نفد</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            @if(count($selected))
                <button type="button" wire:click="bulkDelete" wire:confirm="حذف {{ count($selected) }} منتج؟" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4" /> حذف المحدد ({{ count($selected) }})</button>
            @endif
            @can('create', App\Models\Product::class)
                <a href="{{ route('admin.products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> منتج جديد</a>
            @endcan
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="table-th w-10"><input type="checkbox" wire:model.live="selectPage" class="rounded text-brand-600" aria-label="تحديد الكل"></th>
                    <th class="table-th">المنتج</th>
                    <th class="table-th"><button wire:click="sortBy('price')" class="font-bold">السعر</button></th>
                    <th class="table-th"><button wire:click="sortBy('stock')" class="font-bold">المخزون</button></th>
                    <th class="table-th"><button wire:click="sortBy('sales_count')" class="font-bold">المبيعات</button></th>
                    <th class="table-th">الحالة</th>
                    <th class="table-th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($products as $product)
                    <tr wire:key="prod-{{ $product->id }}">
                        <td class="table-td"><input type="checkbox" wire:model.live="selected" value="{{ $product->id }}" class="rounded text-brand-600" aria-label="تحديد"></td>
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">@if($product->primaryImage)<img src="{{ $product->thumb_url }}" alt="" loading="lazy" class="h-full w-full object-cover">@endif</span>
                                <div><a href="{{ route('admin.products.edit', $product) }}" class="font-bold hover:text-brand-600">{{ $product->name_ar }}</a>
                                    <p class="text-xs text-slate-500"><span dir="ltr">{{ $product->sku }}</span> · {{ $product->category?->name_ar ?? 'بدون تصنيف' }}</p></div>
                            </div>
                        </td>
                        <td class="table-td">
                            @if($product->hasSalePrice())<span class="font-bold text-rose-600">{{ money($product->sale_price) }}</span> <span class="text-xs text-slate-400 line-through">{{ money($product->price) }}</span>
                            @else<span class="font-bold">{{ money($product->price) }}</span>@endif
                        </td>
                        <td class="table-td">
                            @if($product->has_variants)
                                <span class="badge {{ $product->available_stock <= 5 ? 'badge-rose' : 'badge-slate' }}">{{ $product->available_stock }}</span> <span class="text-xs text-slate-400">متغيرات</span>
                            @else
                                <input type="number" min="0" value="{{ $product->stock }}" wire:change="updateStock({{ $product->id }}, $event.target.value)" class="input !w-20 !py-1.5 {{ $product->stock <= 5 ? '!border-rose-300' : '' }}" aria-label="المخزون">
                            @endif
                        </td>
                        <td class="table-td">{{ $product->sales_count }}</td>
                        <td class="table-td">
                            <button type="button" wire:click="toggleStatus({{ $product->id }})" class="badge {{ $product->status === 'active' ? 'badge-emerald' : 'badge-slate' }}">{{ $product->status === 'active' ? 'منشور' : 'مسودة' }}</button>
                        </td>
                        <td class="table-td text-end">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="btn-ghost btn-sm !p-2" aria-label="عرض"><x-icon name="eye" class="h-4 w-4" /></a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-ghost btn-sm !p-2" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></a>
                                @can('delete', $product)
                                    <button type="button" wire:click="delete({{ $product->id }})" wire:confirm="حذف المنتج «{{ $product->name_ar }}»؟" class="btn-ghost btn-sm !p-2 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0"><x-empty-state icon="cube" title="لا توجد منتجات" text="جرّب تغيير الفلاتر أو أضف منتجاً جديداً." class="!border-0 !shadow-none" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</div>
