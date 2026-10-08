<?php

namespace App\Livewire\Concerns;

use App\Exceptions\StoreException;
use App\Services\CartService;
use App\Services\WishlistService;
use Livewire\Attributes\Computed;

/** Cart + wishlist actions shared by every storefront component that renders product cards. */
trait InteractsWithStore
{
    public function addToCart(int $productId, ?int $variantId = null, int $quantity = 1): void
    {
        try {
            $carts = app(CartService::class);
            $carts->add($carts->resolve(true), $productId, $variantId, $quantity);

            $this->dispatch('cart-updated');
            $this->dispatch('notify', message: 'تمت إضافة المنتج إلى السلة', type: 'success');
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function toggleWishlist(int $productId): void
    {
        if (! auth()->check()) {
            session()->flash('error', 'يرجى تسجيل الدخول لاستخدام المفضلة.');
            $this->redirectRoute('login');

            return;
        }

        try {
            $added = app(WishlistService::class)->toggle(auth()->user(), $productId);
            unset($this->wishlistIds);

            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', message: $added ? 'أضيف إلى المفضلة' : 'أزيل من المفضلة', type: 'success');
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    #[Computed]
    public function wishlistIds(): array
    {
        return app(WishlistService::class)->productIds(auth()->user());
    }
}
