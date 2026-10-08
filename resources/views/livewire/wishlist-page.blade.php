<div class="container-x py-8">
    <h1 class="mb-6 text-2xl font-extrabold sm:text-3xl">المفضلة</h1>

    @if($products->isEmpty())
        <x-empty-state icon="heart" title="قائمة المفضلة فارغة" text="اضغط على أيقونة القلب في أي منتج لحفظه هنا.">
            <a href="{{ route('products.index') }}" class="btn-primary">تصفح المنتجات</a>
        </x-empty-state>
    @else
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($products as $product)
                <div wire:key="wl-{{ $product->id }}" class="card flex flex-col overflow-hidden">
                    <a href="{{ $product->url }}" class="block aspect-square bg-slate-100 dark:bg-slate-800">
                        @if($product->primaryImage)<img src="{{ $product->thumb_url }}" alt="{{ $product->name_ar }}" loading="lazy" class="h-full w-full object-cover">@endif
                    </a>
                    <div class="flex flex-1 flex-col p-3.5">
                        <a href="{{ $product->url }}" class="line-clamp-2 min-h-[2.75rem] text-sm font-bold hover:text-brand-600">{{ $product->name_ar }}</a>
                        <x-price class="mt-2" :regular="$product->from_regular_price" :final="$product->from_price" :from="$product->has_variants" />
                        <div class="mt-3 flex flex-1 items-end gap-2">
                            @if($product->in_stock)
                                <button type="button" wire:click="moveToCart({{ $product->id }})" class="btn-primary btn-sm flex-1">{{ $product->has_variants ? 'اختر الخيارات' : 'نقل للسلة' }}</button>
                            @else
                                <button class="btn-outline btn-sm flex-1" disabled>غير متوفر</button>
                            @endif
                            <button type="button" wire:click="remove({{ $product->id }})" class="btn-outline btn-sm !px-2.5 text-rose-600" aria-label="إزالة"><x-icon name="trash" class="h-4 w-4" /></button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
