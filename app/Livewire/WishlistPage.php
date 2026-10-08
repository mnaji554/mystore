<?php

namespace App\Livewire;

use App\Exceptions\StoreException;
use App\Livewire\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Livewire\Component;

class WishlistPage extends Component
{
    use InteractsWithStore;

    public function remove(int $productId): void
    {
        app(WishlistService::class)->remove(auth()->user(), $productId);
        unset($this->wishlistIds);
        $this->dispatch('wishlist-updated');
    }

    public function moveToCart(int $productId): void
    {
        $product = Product::query()->active()->with('variants')->find($productId);

        if (! $product) {
            return;
        }

        if ($product->has_variants) {
            $this->redirect($product->url);

            return;
        }

        try {
            $carts = app(CartService::class);
            $carts->add($carts->resolve(true), $productId);
            app(WishlistService::class)->remove(auth()->user(), $productId);
            unset($this->wishlistIds);

            $this->dispatch('cart-updated');
            $this->dispatch('wishlist-updated');
            $this->dispatch('notify', message: 'تم نقل المنتج إلى السلة', type: 'success');
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.wishlist-page', [
            'products' => app(WishlistService::class)->products(auth()->user()),
        ])->layout('components.layouts.app', ['title' => 'المفضلة', 'noindex' => true]);
    }
}
