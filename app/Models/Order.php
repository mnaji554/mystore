<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'shipping_method_id', 'shipping_method_name', 'tracking_number',
        'tracking_url', 'payment_method', 'payment_status', 'status', 'coupon_code', 'subtotal',
        'discount_total', 'coupon_discount', 'shipping_cost', 'tax_total', 'grand_total',
        'currency', 'customer_note', 'placed_at', 'courier_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (self::withTrashed()->where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id')->withTrashed();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isCancellableByCustomer(): bool
    {
        return $this->status->customerCanCancel();
    }

    /** The user, or an anonymous mail-only notifiable for guest checkouts. */
    public function customerNotifiable(): object
    {
        return $this->user ?? Notification::route('mail', [$this->customer_email => $this->customer_name]);
    }

    public function getAddressLineAttribute(): string
    {
        $a = $this->shipping_address ?? [];

        return collect([
            $a['street'] ?? null, $a['building'] ?? null, $a['district'] ?? null,
            $a['city'] ?? null, $a['country'] ?? null,
        ])->filter()->implode('، ');
    }
}
