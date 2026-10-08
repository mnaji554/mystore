<?php

namespace App\Livewire;

use App\Exceptions\StoreException;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use App\Support\CartSummary;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Checkout extends Component
{
    public const STEPS = [
        1 => 'معلومات العميل',
        2 => 'العنوان',
        3 => 'طريقة الشحن',
        4 => 'الكوبون',
        5 => 'طريقة الدفع',
        6 => 'المراجعة والتأكيد',
    ];

    public int $step = 1;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?int $addressId = null;

    public bool $saveAddress = true;

    public array $address = [
        'full_name' => '', 'phone' => '', 'country' => 'المملكة العربية السعودية',
        'city' => '', 'district' => '', 'street' => '', 'building' => '', 'postal_code' => '',
    ];

    public ?int $shippingMethodId = null;

    public string $couponCode = '';

    public string $paymentMethod = '';

    public string $note = '';

    public function mount(): void
    {
        $cart = app(CartService::class)->resolve();

        if (! $cart || $cart->items()->doesntExist()) {
            session()->flash('error', 'سلتك فارغة.');
            $this->redirectRoute('cart');

            return;
        }

        if ($user = auth()->user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;

            $default = $user->addresses()->orderByDesc('is_default')->first();
            $this->addressId = $default?->id;
        }

        $this->shippingMethodId = app(ShippingService::class)->availableMethods()->first()?->id;
        $this->paymentMethod = (string) app(PaymentService::class)->available()->keys()->first();
    }

    #[Computed]
    public function cart()
    {
        return app(CartService::class)->resolve();
    }

    #[Computed]
    public function savedAddresses()
    {
        return auth()->check() ? auth()->user()->addresses()->orderByDesc('is_default')->get() : collect();
    }

    #[Computed]
    public function shippingMethods()
    {
        return app(ShippingService::class)->availableMethods();
    }

    #[Computed]
    public function gateways()
    {
        return app(PaymentService::class)->available();
    }

    #[Computed]
    public function summary(): CartSummary
    {
        $method = $this->shippingMethods->firstWhere('id', $this->shippingMethodId);

        return app(CartService::class)->summary($this->cart, $method, auth()->user(), $this->email ?: null);
    }

    /** @return array<string, array> */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190'],
                'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            ],
            2 => $this->addressId ? [] : [
                'address.full_name' => ['required', 'string', 'max:120'],
                'address.phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
                'address.country' => ['required', 'string', 'max:80'],
                'address.city' => ['required', 'string', 'max:100'],
                'address.district' => ['nullable', 'string', 'max:100'],
                'address.street' => ['required', 'string', 'max:190'],
                'address.building' => ['nullable', 'string', 'max:60'],
                'address.postal_code' => ['nullable', 'string', 'max:20'],
            ],
            3 => ['shippingMethodId' => ['required', 'integer']],
            5 => ['paymentMethod' => ['required', 'string']],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'required' => 'هذا الحقل مطلوب.',
            'email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'regex' => 'صيغة غير صحيحة.',
            'max' => 'القيمة طويلة جداً.',
        ];
    }

    private function validateStep(int $step): void
    {
        // Livewire falls back to rules() when given an empty array, so skip steps without rules.
        if ($rules = $this->rulesForStep($step)) {
            $this->validate($rules);
        }
    }

    public function next(): void
    {
        $this->validateStep($this->step);

        if ($this->step === 2 && $this->addressId) {
            abort_unless(auth()->user()?->addresses()->whereKey($this->addressId)->exists(), 403);
        }

        if ($this->step === 3 && ! $this->shippingMethods->contains('id', $this->shippingMethodId)) {
            $this->addError('shippingMethodId', 'طريقة الشحن غير متاحة.');

            return;
        }

        if ($this->step === 5 && ! $this->gateways->has($this->paymentMethod)) {
            $this->addError('paymentMethod', 'طريقة الدفع غير متاحة.');

            return;
        }

        $this->step = min($this->step + 1, count(self::STEPS));
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function goTo(int $step): void
    {
        if ($step < $this->step && $step >= 1) {
            $this->step = $step;
        }
    }

    public function selectAddress(?int $id): void
    {
        $this->addressId = $id;
    }

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => ['required', 'string', 'max:50']], ['couponCode.required' => 'أدخل كود الخصم.']);

        try {
            app(CartService::class)->applyCoupon($this->cart, $this->couponCode, auth()->user(), $this->email ?: null);
            $this->couponCode = '';
            $this->dispatch('notify', message: 'تم تطبيق كود الخصم', type: 'success');
        } catch (StoreException $e) {
            $this->addError('couponCode', $e->getMessage());
        }

        unset($this->summary);
    }

    public function removeCoupon(): void
    {
        app(CartService::class)->removeCoupon($this->cart);
        unset($this->summary);
    }

    public function placeOrder()
    {
        $limiterKey = 'checkout:'.(auth()->id() ?: request()->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, 10)) {
            $this->dispatch('notify', message: 'محاولات كثيرة، يرجى الانتظار قليلاً.', type: 'error');

            return;
        }

        RateLimiter::hit($limiterKey, 60);

        foreach ([1, 2, 3, 5] as $step) {
            $this->validateStep($step);
        }

        $user = auth()->user();
        $snapshot = $this->addressId
            ? $user?->addresses()->find($this->addressId)?->toSnapshot()
            : array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v, $this->address);

        if (! $snapshot) {
            $this->step = 2;
            $this->addError('addressId', 'يرجى اختيار عنوان صحيح.');

            return;
        }

        try {
            $order = app(OrderService::class)->createFromCart($this->cart, [
                'name' => strip_tags(trim($this->name)),
                'email' => $this->email,
                'phone' => trim($this->phone),
                'address' => $snapshot,
                'shipping_method_id' => $this->shippingMethodId,
                'payment_method' => $this->paymentMethod,
                'note' => $this->note ? strip_tags(trim($this->note)) : null,
            ], $user);
        } catch (StoreException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            unset($this->summary);

            return;
        }

        if ($user && ! $this->addressId && $this->saveAddress) {
            $user->addresses()->create($snapshot + ['is_default' => $user->addresses()->doesntExist()]);
        }

        session(['last_order_number' => $order->order_number]);
        $this->dispatch('cart-updated');

        try {
            $result = app(PaymentService::class)->initiate($order);
        } catch (StoreException $e) {
            session()->flash('error', $e->getMessage());

            return $this->redirectRoute('checkout.payment-cancelled', $order->order_number);
        }

        return $result->requiresRedirect()
            ? $this->redirect($result->redirectUrl)
            : $this->redirectRoute('checkout.success', $order->order_number);
    }

    public function render()
    {
        return view('livewire.checkout', ['steps' => self::STEPS])
            ->layout('components.layouts.app', ['title' => 'إتمام الطلب', 'noindex' => true]);
    }
}
