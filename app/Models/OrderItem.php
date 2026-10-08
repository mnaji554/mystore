<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'name', 'sku', 'variant_label',
        'unit_price', 'sale_price', 'quantity', 'line_total', 'cost_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id')->withTrashed();
    }
}
