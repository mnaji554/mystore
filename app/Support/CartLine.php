<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

class CartLine
{
    public function __construct(
        public readonly int $itemId,
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $quantity,
        public readonly float $regularPrice,
        public readonly float $price,
        public readonly int $stock,
        public readonly ?string $issue = null,
    ) {}

    public function name(): string
    {
        return $this->product->name_ar;
    }

    public function sku(): string
    {
        return $this->variant?->sku ?? $this->product->sku;
    }

    public function variantLabel(): ?string
    {
        return $this->variant?->label ?: null;
    }

    public function regularTotal(): float
    {
        return round($this->regularPrice * $this->quantity, 2);
    }

    public function total(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function savings(): float
    {
        return round($this->regularTotal() - $this->total(), 2);
    }

    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant?->id,
            'name' => $this->name(),
            'sku' => $this->sku(),
            'variant' => $this->variantLabel(),
            'image' => $this->product->thumb_url,
            'quantity' => $this->quantity,
            'regular_price' => $this->regularPrice,
            'price' => $this->price,
            'line_total' => $this->total(),
            'stock' => $this->stock,
            'issue' => $this->issue,
        ];
    }
}
