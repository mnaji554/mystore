<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Exceptions\CouponException;
use App\Exceptions\OrderException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
        private readonly ShippingService $shipping,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Create an order from a cart. Every price is recomputed from the database inside a transaction
     * with row locks, so nothing coming from the browser influences the totals.
     *
     * @param  array{name:string,email:string,phone:string,address:array,shipping_method_id:int,payment_method:string,note?:?string}  $data
     */
    public function createFromCart(Cart $cart, array $data, ?User $user = null): Order
    {
        $shippingMethod = $this->shipping->find($data['shipping_method_id'] ?? null)
            ?? throw new OrderException('يرجى اختيار طريقة شحن صحيحة.');

        $this->payments->gateway($data['payment_method']);

        $order = DB::transaction(function () use ($cart, $data, $user, $shippingMethod) {
            $cart->load('items');
            $this->lockStock($cart);

            $summary = $this->carts->summary($cart->fresh(), $shippingMethod, $user, $data['email']);

            if ($summary->isEmpty()) {
                throw new OrderException('سلة المشتريات فارغة.');
            }

            if ($summary->hasIssues()) {
                throw new OrderException('بعض المنتجات في السلة غير متاحة: '.implode('، ', $summary->issues()));
            }

            if ($cart->coupon_code) {
                try {
                    $couponResult = $this->coupons->validate($cart->coupon_code, $summary->lines, $user, $data['email'], lock: true);
                } catch (CouponException $e) {
                    throw new OrderException($e->getMessage());
                }
            }

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'user_id' => $user?->id,
                'customer_name' => $data['name'],
                'customer_email' => strtolower($data['email']),
                'customer_phone' => $data['phone'],
                'shipping_address' => $data['address'],
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method_name' => $shippingMethod->name,
                'payment_method' => $data['payment_method'],
                'payment_status' => PaymentStatus::Pending,
                'status' => OrderStatus::Pending,
                'coupon_code' => $summary->coupon?->code,
                'subtotal' => $summary->subtotal,
                'discount_total' => $summary->discount,
                'coupon_discount' => $summary->couponDiscount,
                'shipping_cost' => $summary->shipping,
                'tax_total' => $summary->tax,
                'grand_total' => $summary->total,
                'currency' => setting('currency', 'SAR'),
                'customer_note' => $data['note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($summary->lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'variant_id' => $line->variant?->id,
                    'name' => $line->name(),
                    'sku' => $line->sku(),
                    'variant_label' => $line->variantLabel(),
                    'unit_price' => $line->regularPrice,
                    'sale_price' => $line->price,
                    'quantity' => $line->quantity,
                    'line_total' => $line->total(),
                    'cost_price' => $line->product->cost_price,
                ]);

                $line->variant
                    ? ProductVariant::query()->whereKey($line->variant->id)->decrement('stock', $line->quantity)
                    : Product::query()->whereKey($line->product->id)->decrement('stock', $line->quantity);

                Product::query()->whereKey($line->product->id)->increment('sales_count', $line->quantity);
            }

            $order->histories()->create([
                'user_id' => $user?->id,
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'note' => 'تم إنشاء الطلب',
            ]);

            $this->payments->createPayment($order, $data['payment_method']);

            if (isset($couponResult)) {
                $this->coupons->recordUsage($couponResult['coupon'], $order, $couponResult['discount']);
            }

            $this->carts->clear($cart);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    /** Lock product / variant rows so concurrent checkouts cannot oversell. */
    private function lockStock(Cart $cart): void
    {
        $productIds = $cart->items->pluck('product_id')->unique()->all();
        $variantIds = $cart->items->pluck('variant_id')->filter()->unique()->all();

        Product::query()->withTrashed()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();

        if ($variantIds) {
            ProductVariant::query()->withTrashed()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get();
        }
    }

    public function changeStatus(Order $order, OrderStatus $to, ?User $actor = null, ?string $note = null): Order
    {
        $from = null;

        DB::transaction(function () use ($order, $to, $actor, $note, &$from) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw new OrderException("لا يمكن تغيير حالة الطلب من «{$from->label()}» إلى «{$to->label()}».");
            }

            $attributes = ['status' => $to];

            if ($to === OrderStatus::Delivered && $locked->payment_method === 'cod' && $locked->payment_status !== PaymentStatus::Paid) {
                $attributes['payment_status'] = PaymentStatus::Paid;
                $locked->payments()->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);
            }

            $locked->update($attributes);

            $locked->histories()->create([
                'user_id' => $actor?->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);

            if ($to->releasesStock()) {
                $this->restock($locked);
                $this->coupons->releaseUsage($locked);
            }

            $order->setRawAttributes($locked->getAttributes(), true);
            $order->unsetRelation('histories');
        });

        if ($to === OrderStatus::Refunded) {
            $this->payments->refundOrder($order);
        }

        OrderStatusChanged::dispatch($order->fresh(), $from, $to);

        return $order;
    }

    public function cancelByCustomer(Order $order, User $user): Order
    {
        if (! $order->status->customerCanCancel()) {
            throw new OrderException('لا يمكن إلغاء الطلب في حالته الحالية.');
        }

        return $this->changeStatus($order, OrderStatus::Cancelled, $user, 'تم الإلغاء بواسطة العميل');
    }

    private function restock(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            if ($item->variant_id) {
                ProductVariant::query()->withTrashed()->whereKey($item->variant_id)->increment('stock', $item->quantity);
            } elseif ($item->product_id) {
                Product::query()->withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            if ($item->product_id) {
                Product::query()->withTrashed()->whereKey($item->product_id)
                    ->where('sales_count', '>=', $item->quantity)->decrement('sales_count', $item->quantity);
            }
        }
    }
}
