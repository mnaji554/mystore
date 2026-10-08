<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrderRepository
{
    public function forUser(User $user, int $perPage = 10)
    {
        return Order::query()->where('user_id', $user->id)->with('items')->latest('id')->paginate($perPage);
    }

    /** Guest-friendly lookup: order number must be combined with the e-mail used at checkout. */
    public function findForTracking(string $number, string $email): ?Order
    {
        return Order::query()->with(['items', 'histories'])
            ->where('order_number', strtoupper(trim($number)))
            ->where('customer_email', strtolower(trim($email)))
            ->first();
    }

    /** @param array{search?:?string,status?:?string,from?:?string,to?:?string,payment?:?string} $filters */
    public function adminQuery(array $filters): Builder
    {
        return Order::query()
            ->with(['user:id,name', 'items:id,order_id,quantity'])
            ->when($filters['search'] ?? null, function (Builder $q, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($term)).'%';
                $q->where(fn (Builder $w) => $w->where('order_number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['payment'] ?? null, fn (Builder $q, string $s) => $q->where('payment_status', $s))
            ->when($filters['from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d));
    }
}
