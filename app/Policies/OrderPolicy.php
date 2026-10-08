<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends ManagedResourcePolicy
{
    protected function permission(): string
    {
        return 'manage-orders';
    }

    public function view(User $user, mixed $order = null): bool
    {
        return $user->hasPermission('manage-orders') || ($order instanceof Order && $order->user_id === $user->id);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->isCancellableByCustomer();
    }

    public function downloadInvoice(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
