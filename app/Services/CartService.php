<?php

namespace App\Services;

use App\Exceptions\CartException;
use App\Exceptions\CouponException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\CartLine;
use App\Support\CartSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public const MAX_LINE_QUANTITY = 50;

    public function __construct(
        private readonly CouponService $coupons,
        private readonly ShippingService $shipping,
    ) {}

    /** Resolve the cart of the current visitor (user cart or guest cart via session token). */
    public function resolve(bool $create = false, ?User $user = null): ?Cart
    {
        $user ??= auth()->user();

        if ($user) {
            return $create
                ? Cart::firstOrCreate(['user_id' => $user->id])
                : Cart::where('user_id', $user->id)->first();
        }

        $token = session('cart_token');
        $cart = $token ? Cart::where('token', $token)->whereNull('user_id')->first() : null;

        if (! $cart && $create) {
            $token = Str::random(40);
            session(['cart_token' => $token]);
            $cart = Cart::create(['token' => $token]);
        }

        return $cart;
    }

    public function count(?User $user = null): int
    {
        $cart = $this->resolve(false, $user);

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    public function add(Cart $cart, int $productId, ?int $variantId = null, int $quantity = 1): CartItem
    {
        $quantity = max(1, $quantity);

        $product = Product::query()->active()->with('variants.options')->find($productId);

        if (! $product) {
            throw new CartException('هذا المنتج غير متاح حالياً.');
        }

        $variant = $this->resolveVariant($product, $variantId);

        return DB::transaction(function () use ($cart, $product, $variant, $quantity) {
            $item = CartItem::query()->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->when($variant, fn ($q) => $q->where('variant_id', $variant->id), fn ($q) => $q->whereNull('variant_id'))
                ->lockForUpdate()->first();

            $newQuantity = ($item?->quantity ?? 0) + $quantity;
            $this->assertQuantity($product, $variant, $newQuantity);

            if ($item) {
                $item->update(['quantity' => $newQuantity]);

                return $item;
            }

            return $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $newQuantity,
            ]);
        });
    }

    public function updateQuantity(Cart $cart, int $itemId, int $quantity): ?CartItem
    {
        $item = $cart->items()->with(['product.variants', 'variant'])->findOrFail($itemId);

        if ($quantity < 1) {
            $item->delete();

            return null;
        }

        $this->assertQuantity($item->product, $item->variant, $quantity);
        $item->update(['quantity' => $quantity]);

        return $item;
    }

    public function remove(Cart $cart, int $itemId): void
    {
        $cart->items()->whereKey($itemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_code' => null]);
    }

    public function applyCoupon(Cart $cart, string $code, ?User $user = null, ?string $email = null): array
    {
        $summary = $this->summary($cart->fresh(), null, null, null);

        if ($summary->isEmpty()) {
            throw new CouponException('السلة فارغة.');
        }

        $result = $this->coupons->validate($code, $summary->lines, $user ?? auth()->user(), $email);
        $cart->update(['coupon_code' => $result['coupon']->code]);

        return $result;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_code' => null]);
    }

    /** Merge a guest cart (by session token) into the user's cart after login. */
    public function mergeGuestCart(User $user, ?string $token): void
    {
        if (! $token) {
            return;
        }

        $guest = Cart::query()->with('items')->where('token', $token)->whereNull('user_id')->first();

        if (! $guest) {
            return;
        }

        DB::transaction(function () use ($guest, $user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            foreach ($guest->items as $guestItem) {
                $existing = $cart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->when($guestItem->variant_id, fn ($q) => $q->where('variant_id', $guestItem->variant_id), fn ($q) => $q->whereNull('variant_id'))
                    ->first();

                $product = Product::query()->active()->with('variants')->find($guestItem->product_id);
                $variant = $guestItem->variant_id ? $product?->variants->firstWhere('id', $guestItem->variant_id) : null;

                if (! $product) {
                    continue;
                }

                $stock = $this->stockFor($product, $variant);
                $quantity = min(($existing?->quantity ?? 0) + $guestItem->quantity, $stock, self::MAX_LINE_QUANTITY);

                if ($quantity < 1) {
                    continue;
                }

                if ($existing) {
                    $existing->update(['quantity' => $quantity]);
                } else {
                    $cart->items()->create([
                        'product_id' => $guestItem->product_id,
                        'variant_id' => $guestItem->variant_id,
                        'quantity' => $quantity,
                    ]);
                }
            }

            if (! $cart->coupon_code && $guest->coupon_code) {
                $cart->update(['coupon_code' => $guest->coupon_code]);
            }

            $guest->delete();
        });

        session()->forget('cart_token');
    }

    /** Calculate every amount on the server from database prices. */
    public function summary(Cart $cart, ?ShippingMethod $method = null, ?User $user = null, ?string $email = null): CartSummary
    {
        $items = $cart->items()
            ->with(['product.variants.options', 'product.primaryImage', 'variant.options'])
            ->orderBy('id')->get();

        $lines = $items->map(fn (CartItem $item) => $this->buildLine($item))->values();

        $subtotal = round($lines->sum(fn (CartLine $l) => $l->regularTotal()), 2);
        $discount = round($lines->sum(fn (CartLine $l) => $l->savings()), 2);
        $net = round($subtotal - $discount, 2);

        $coupon = null;
        $couponDiscount = 0.0;
        $couponError = null;

        if ($cart->coupon_code && $lines->isNotEmpty()) {
            try {
                $result = $this->coupons->validate($cart->coupon_code, $lines, $user ?? $cart->user ?? auth()->user(), $email);
                $coupon = $result['coupon'];
                $couponDiscount = $result['discount'];
            } catch (CouponException $e) {
                $couponError = $e->getMessage();
            }
        }

        $taxable = round($net - $couponDiscount, 2);
        $shipping = ($method && $lines->isNotEmpty()) ? $this->shipping->cost($method, $taxable) : 0.0;
        $tax = $this->tax($taxable);
        $total = round($taxable + $shipping + $tax, 2);

        return new CartSummary($lines, $subtotal, $discount, $couponDiscount, $coupon, $couponError, $method, $shipping, $tax, $total);
    }

    public function tax(float $taxable): float
    {
        if (! (bool) setting('tax_enabled', '1')) {
            return 0.0;
        }

        return round($taxable * ((float) setting('tax_rate', 15)) / 100, 2);
    }

    public function stockFor(Product $product, ?ProductVariant $variant): int
    {
        return (int) ($variant ? $variant->stock : $product->stock);
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        $activeVariants = $product->variants->where('is_active', true);

        if ($activeVariants->isEmpty()) {
            return null;
        }

        $variant = $variantId ? $activeVariants->firstWhere('id', $variantId) : null;

        if (! $variant) {
            throw new CartException('يرجى اختيار الخيارات المطلوبة للمنتج (اللون / المقاس).');
        }

        return $variant;
    }

    private function assertQuantity(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $stock = $this->stockFor($product, $variant);

        if ($stock < 1) {
            throw new CartException('نفدت الكمية من هذا المنتج.');
        }

        if ($quantity > $stock) {
            throw new CartException("الكمية المتاحة من هذا المنتج {$stock} فقط.");
        }

        if ($quantity > self::MAX_LINE_QUANTITY) {
            throw new CartException('تجاوزت الحد الأقصى المسموح للكمية.');
        }
    }

    private function buildLine(CartItem $item): CartLine
    {
        $product = $item->product;
        $variant = $item->variant;
        $issue = null;

        if (! $product || $product->trashed() || $product->status !== Product::STATUS_ACTIVE) {
            $issue = 'المنتج لم يعد متاحاً';
        } elseif ($product->variants->where('is_active', true)->isNotEmpty() && (! $variant || $variant->trashed() || ! $variant->is_active)) {
            $issue = 'الخيار المحدد لم يعد متاحاً';
        }

        $stock = $product ? $this->stockFor($product, $variant) : 0;

        if (! $issue) {
            if ($stock < 1) {
                $issue = 'نفدت الكمية';
            } elseif ($item->quantity > $stock) {
                $issue = "المتاح {$stock} فقط";
            }
        }

        $regular = $variant ? (float) $variant->price : (float) $product->price;
        $price = $variant ? $variant->final_price : $product->final_price;

        return new CartLine($item->id, $product, $variant, $item->quantity, $regular, $price, max($stock, 0), $issue);
    }
}
