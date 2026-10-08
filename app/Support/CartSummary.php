<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

/** Immutable, server-side calculated cart totals. */
class CartSummary
{
    /** @param Collection<int, CartLine> $lines */
    public function __construct(
        public readonly Collection $lines,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $couponDiscount,
        public readonly ?Coupon $coupon,
        public readonly ?string $couponError,
        public readonly ?ShippingMethod $shippingMethod,
        public readonly float $shipping,
        public readonly float $tax,
        public readonly float $total,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function itemsCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }

    /** Amount after sale discounts but before coupon. */
    public function netSubtotal(): float
    {
        return round($this->subtotal - $this->discount, 2);
    }

    /** @return list<string> */
    public function issues(): array
    {
        return $this->lines->filter(fn (CartLine $l) => $l->issue)
            ->map(fn (CartLine $l) => $l->name().': '.$l->issue)->values()->all();
    }

    public function hasIssues(): bool
    {
        return $this->lines->contains(fn (CartLine $l) => $l->issue !== null);
    }

    public function toArray(): array
    {
        return [
            'items' => $this->lines->map->toArray()->values()->all(),
            'items_count' => $this->itemsCount(),
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'coupon_code' => $this->coupon?->code,
            'coupon_discount' => $this->couponDiscount,
            'coupon_error' => $this->couponError,
            'shipping' => $this->shipping,
            'tax' => $this->tax,
            'grand_total' => $this->total,
        ];
    }
}
