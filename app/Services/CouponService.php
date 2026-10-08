<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use App\Support\CartLine;
use Illuminate\Support\Collection;

class CouponService
{
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->with(['products:id', 'categories:id'])
            ->where('code', strtoupper(trim($code)))->first();
    }

    /**
     * Validate a coupon against the cart lines on the server.
     *
     * @param  Collection<int, CartLine>  $lines
     * @return array{coupon: Coupon, discount: float}
     *
     * @throws CouponException
     */
    public function validate(string $code, Collection $lines, ?User $user = null, ?string $email = null, bool $lock = false): array
    {
        $coupon = $lock
            ? Coupon::query()->with(['products:id', 'categories:id'])->where('code', strtoupper(trim($code)))->lockForUpdate()->first()
            : $this->findByCode($code);

        if (! $coupon || ! $coupon->is_active) {
            throw new CouponException('كود الخصم غير صحيح.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new CouponException('كود الخصم لم يبدأ بعد.');
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw new CouponException('انتهت صلاحية كود الخصم.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new CouponException('تم استنفاد عدد مرات استخدام هذا الكود.');
        }

        if ($coupon->usage_limit_per_user !== null && $this->userUsageCount($coupon, $user, $email) >= $coupon->usage_limit_per_user) {
            throw new CouponException('لقد استخدمت هذا الكود الحد الأقصى المسموح به.');
        }

        $net = round($lines->sum(fn (CartLine $l) => $l->total()), 2);

        if ($coupon->min_order_amount !== null && $net < (float) $coupon->min_order_amount) {
            throw new CouponException('الحد الأدنى للطلب لاستخدام الكود هو '.money($coupon->min_order_amount).'.');
        }

        $eligible = $this->eligibleLines($coupon, $lines);

        if ($eligible->isEmpty()) {
            throw new CouponException('هذا الكود لا ينطبق على المنتجات الموجودة في السلة.');
        }

        $base = round($eligible->sum(fn (CartLine $l) => $l->total()), 2);

        $discount = $coupon->type === Coupon::PERCENTAGE
            ? $base * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = round(min($discount, $base), 2);

        return ['coupon' => $coupon, 'discount' => $discount];
    }

    /** @param Collection<int, CartLine> $lines */
    private function eligibleLines(Coupon $coupon, Collection $lines): Collection
    {
        if ($coupon->isGlobal()) {
            return $lines;
        }

        $productIds = $coupon->products->pluck('id')->all();
        $categoryIds = $coupon->categories->pluck('id')
            ->flatMap(fn ($id) => Category::find($id)?->descendantIds() ?? [])->unique()->all();

        return $lines->filter(fn (CartLine $l) => in_array($l->product->id, $productIds, true)
            || in_array($l->product->category_id, $categoryIds, true));
    }

    public function userUsageCount(Coupon $coupon, ?User $user, ?string $email): int
    {
        $query = CouponUsage::query()->where('coupon_id', $coupon->id);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($email) {
            $query->where('email', strtolower($email));
        } else {
            return 0;
        }

        return $query->count();
    }

    public function recordUsage(Coupon $coupon, Order $order, float $amount): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $order->user_id,
            'email' => strtolower($order->customer_email),
            'order_id' => $order->id,
            'discount_amount' => $amount,
        ]);

        $coupon->increment('used_count');
    }

    public function releaseUsage(Order $order): void
    {
        $usages = CouponUsage::query()->where('order_id', $order->id)->get();

        foreach ($usages as $usage) {
            Coupon::query()->whereKey($usage->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            $usage->delete();
        }
    }
}
