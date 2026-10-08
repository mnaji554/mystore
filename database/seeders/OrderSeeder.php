<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ReviewService;
use App\Services\ShippingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

class OrderSeeder extends Seeder
{
    private const COMMENTS = [
        5 => ['منتج رائع وجودته ممتازة', 'وصل بسرعة وبحالة ممتازة، أنصح به', 'أفضل من توقعاتي بكثير', 'تجربة شراء ممتازة وسأكرر الشراء'],
        4 => ['جيد جداً مقابل السعر', 'المنتج جيد لكن التغليف يحتاج تحسين', 'مناسب وعملي'],
        3 => ['المنتج مقبول لكن توقعت أفضل', 'متوسط الجودة'],
    ];

    public function run(CartService $carts, OrderService $orders, ReviewService $reviews, ShippingService $shipping): void
    {
        // Seeded history must not send e-mails/notifications.
        Event::fake([OrderPlaced::class, OrderStatusChanged::class]);

        $customers = User::whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))->with('addresses')->get();
        $products = Product::active()->with('variants')->get();
        $methods = ShippingMethod::active()->get();
        $welcome = Coupon::where('code', 'WELCOME10')->exists();

        if ($customers->isEmpty() || $products->isEmpty() || $methods->isEmpty()) {
            return;
        }

        $targets = array_merge(
            array_fill(0, 16, OrderStatus::Delivered),
            array_fill(0, 3, OrderStatus::Shipped),
            array_fill(0, 3, OrderStatus::Processing),
            array_fill(0, 3, OrderStatus::Confirmed),
            array_fill(0, 3, OrderStatus::Pending),
            array_fill(0, 2, OrderStatus::Cancelled),
            [OrderStatus::Refunded],
        );
        shuffle($targets);

        foreach ($targets as $n => $target) {
            $customer = $customers[$n % $customers->count()];
            $cart = $carts->resolve(true, $customer);

            foreach ($products->random(random_int(1, 3)) as $product) {
                $variant = $product->variants->where('is_active', true)->where('stock', '>', 2)->shuffle()->first();

                try {
                    if ($product->variants->isNotEmpty() && ! $variant) {
                        continue;
                    }
                    $carts->add($cart, $product->id, $variant?->id, random_int(1, 2));
                } catch (\Throwable) {
                    continue;
                }
            }

            if ($cart->items()->doesntExist()) {
                continue;
            }

            if ($welcome && $n % 5 === 0) {
                try {
                    $carts->applyCoupon($cart, 'WELCOME10', $customer, $customer->email);
                } catch (\Throwable) {
                    // coupon not applicable to this cart — fine
                }
            }

            $address = $customer->addresses->first();

            try {
                $order = $orders->createFromCart($cart, [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => (string) $customer->phone,
                    'address' => $address->toSnapshot(),
                    'shipping_method_id' => $methods->random()->id,
                    'payment_method' => 'cod',
                    'note' => null,
                ], $customer);
            } catch (\Throwable $e) {
                $this->command?->warn('Skipped order: '.$e->getMessage());

                continue;
            }

            $this->progress($order, $target, $orders, $shipping);
            $this->backdate($order, random_int(1, 55));

            if ($target === OrderStatus::Delivered && $n % 2 === 0) {
                $this->review($order, $reviews);
            }
        }

        $this->command?->info('Orders seeded: '.Order::count());
    }

    private function progress(Order $order, OrderStatus $target, OrderService $orders, ShippingService $shipping): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->first();

        $path = match ($target) {
            OrderStatus::Pending => [],
            OrderStatus::Confirmed => [OrderStatus::Confirmed],
            OrderStatus::Processing => [OrderStatus::Confirmed, OrderStatus::Processing],
            OrderStatus::Shipped => [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped],
            OrderStatus::Delivered => [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered],
            OrderStatus::Cancelled => [OrderStatus::Confirmed, OrderStatus::Cancelled],
            OrderStatus::Refunded => [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Refunded],
        };

        foreach ($path as $step) {
            $orders->changeStatus($order, $step, $admin, null);

            if ($step === OrderStatus::Shipped) {
                $shipping->applyTracking($order->refresh(), 'TRK'.random_int(100000, 999999));
            }
        }
    }

    private function backdate(Order $order, int $daysAgo): void
    {
        $base = now()->subDays($daysAgo)->subHours(random_int(0, 20));

        Order::whereKey($order->id)->update(['created_at' => $base, 'placed_at' => $base, 'updated_at' => $base->copy()->addHours(6)]);

        foreach ($order->histories()->reorder('id')->get() as $i => $history) {
            $history->newQuery()->whereKey($history->id)->update(['created_at' => $base->copy()->addHours($i * 5), 'updated_at' => $base->copy()->addHours($i * 5)]);
        }
    }

    private function review(Order $order, ReviewService $reviews): void
    {
        $item = $order->items()->whereNotNull('product_id')->first();

        if (! $item || Review::where('product_id', $item->product_id)->where('user_id', $order->user_id)->exists()) {
            return;
        }

        $rating = Arr::random([5, 5, 5, 4, 4, 3]);
        $review = Review::create([
            'product_id' => $item->product_id,
            'user_id' => $order->user_id,
            'rating' => $rating,
            'title' => Arr::random(['تجربة ممتازة', 'أنصح به', 'جيد جداً', 'راضٍ عن الشراء']),
            'comment' => Arr::random(self::COMMENTS[$rating]),
            'status' => Review::PENDING,
        ]);

        if (random_int(1, 10) <= 8) {
            $reviews->moderate($review, Review::APPROVED);
        }
    }
}
