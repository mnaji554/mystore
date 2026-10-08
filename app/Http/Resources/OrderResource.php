<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method,
            'shipping_method' => $this->shipping_method_name,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount_total,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => (float) $this->coupon_discount,
            'shipping' => (float) $this->shipping_cost,
            'tax' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'name' => $i->name,
                'sku' => $i->sku,
                'variant' => $i->variant_label,
                'price' => (float) $i->sale_price,
                'quantity' => $i->quantity,
                'total' => (float) $i->line_total,
            ])->values()),
            'history' => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($h) => [
                'status' => $h->to_status->value,
                'label' => $h->to_status->label(),
                'note' => $h->note,
                'at' => $h->created_at->toIso8601String(),
            ])->values()),
            'payment_url' => $this->additional['payment_url'] ?? null,
        ];
    }
}
