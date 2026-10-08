<?php

namespace App\Livewire;

use App\Exceptions\StoreException;
use App\Services\CartService;
use App\Support\CartSummary;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CartPage extends Component
{
    public string $couponCode = '';

    #[Computed]
    public function cart()
    {
        return app(CartService::class)->resolve();
    }

    #[Computed]
    public function summary(): ?CartSummary
    {
        return $this->cart ? app(CartService::class)->summary($this->cart) : null;
    }

    private function refreshCart(): void
    {
        unset($this->cart, $this->summary);
        $this->dispatch('cart-updated');
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        try {
            app(CartService::class)->updateQuantity($this->cart, $itemId, $quantity);
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }

        $this->refreshCart();
    }

    public function remove(int $itemId): void
    {
        app(CartService::class)->remove($this->cart, $itemId);
        $this->refreshCart();
        $this->dispatch('notify', message: 'تم حذف المنتج من السلة', type: 'success');
    }

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => ['required', 'string', 'max:50']], ['couponCode.required' => 'أدخل كود الخصم.']);

        try {
            app(CartService::class)->applyCoupon($this->cart, $this->couponCode, auth()->user());
            $this->couponCode = '';
            $this->dispatch('notify', message: 'تم تطبيق كود الخصم', type: 'success');
        } catch (StoreException $e) {
            $this->addError('couponCode', $e->getMessage());
        }

        $this->refreshCart();
    }

    public function removeCoupon(): void
    {
        app(CartService::class)->removeCoupon($this->cart);
        $this->refreshCart();
    }

    public function render()
    {
        return view('livewire.cart-page')->layout('components.layouts.app', [
            'title' => 'سلة المشتريات',
            'noindex' => true,
        ]);
    }
}
