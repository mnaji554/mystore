@props(['product', 'inWishlist' => false])
@php
    $fromPrice = $product->from_price;
    $regular = $product->from_regular_price;
    $onSale = $regular > $fromPrice;
    $inStock = $product->in_stock;
@endphp
<article {{ $attributes->merge(['class' => 'card group relative flex animate-fade-in flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    <a href="{{ route('products.show', $product->slug) }}" class="relative block aspect-square overflow-hidden bg-slate-100 dark:bg-slate-800">
        @if($product->primaryImage)
            <img src="{{ $product->thumb_url }}" alt="{{ $product->name_ar }}" loading="lazy" decoding="async" width="480" height="480"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105 {{ $inStock ? '' : 'opacity-60 grayscale' }}">
        @else
            <span class="grid h-full w-full place-items-center text-slate-300 dark:text-slate-600"><x-icon name="photo" class="h-14 w-14" /></span>
        @endif
        <span class="absolute start-2 top-2 flex flex-col gap-1">
            @if($onSale && $product->discount_percent > 0)<span class="badge bg-rose-600 text-white">-{{ $product->discount_percent }}%</span>@endif
            @if($product->is_new)<span class="badge bg-emerald-600 text-white">جديد</span>@endif
            @if(! $inStock)<span class="badge bg-slate-800 text-white">نفد المخزون</span>@endif
        </span>
    </a>

    <button type="button" wire:click="toggleWishlist({{ $product->id }})" wire:loading.attr="disabled" wire:target="toggleWishlist({{ $product->id }})"
            class="absolute end-2 top-2 grid h-9 w-9 place-items-center rounded-full bg-white/90 shadow transition hover:scale-110 dark:bg-slate-900/90 {{ $inWishlist ? 'text-rose-600' : 'text-slate-500' }}"
            aria-label="{{ $inWishlist ? 'إزالة من المفضلة' : 'إضافة للمفضلة' }}">
        <x-icon name="heart" class="h-5 w-5 {{ $inWishlist ? 'fill-current' : '' }}" />
    </button>

    <div class="flex flex-1 flex-col p-3.5">
        @if($product->category)
            <a href="{{ route('categories.show', $product->category->slug) }}" class="text-xs font-bold text-brand-600 hover:underline dark:text-brand-400">{{ $product->category->name_ar }}</a>
        @endif
        <h3 class="mt-1 line-clamp-2 min-h-[2.75rem] text-sm font-bold leading-snug">
            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-600">{{ $product->name_ar }}</a>
        </h3>
        <div class="mt-1"><x-rating :value="$product->rating_avg" :count="$product->reviews_count" /></div>
        <div class="mt-2"><x-price :regular="$regular" :final="$fromPrice" :from="$product->has_variants" /></div>

        <div class="mt-3 flex-1"></div>
        @if(! $inStock)
            <button class="btn-outline w-full" disabled>غير متوفر</button>
        @elseif($product->has_variants)
            <a href="{{ route('products.show', $product->slug) }}" class="btn-outline w-full">اختر الخيارات</a>
        @else
            <button type="button" wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }})" class="btn-primary w-full">
                <x-icon name="cart" class="h-4 w-4" /> أضف للسلة
            </button>
        @endif
    </div>
</article>
