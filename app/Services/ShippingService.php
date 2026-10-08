<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

class ShippingService
{
    /** @return Collection<int, ShippingMethod> */
    public function availableMethods(): Collection
    {
        return ShippingMethod::query()->with('company')->active()
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function find(?int $id): ?ShippingMethod
    {
        return $id ? ShippingMethod::query()->with('company')->active()->find($id) : null;
    }

    /** Shipping cost for a given order amount (after discounts, before tax). */
    public function cost(ShippingMethod $method, float $orderAmount): float
    {
        if ($method->is_free) {
            return 0.0;
        }

        if ($method->free_shipping_threshold !== null && $orderAmount >= (float) $method->free_shipping_threshold) {
            return 0.0;
        }

        return round((float) $method->price, 2);
    }

    public function applyTracking(Order $order, ?string $number, ?string $url = null): Order
    {
        $number = $number ? trim($number) : null;

        if (! $url && $number && $order->shipping_method_id) {
            $url = $order->shippingMethod?->company?->trackingUrlFor($number);
        }

        $order->update(['tracking_number' => $number, 'tracking_url' => $url]);

        return $order;
    }
}
