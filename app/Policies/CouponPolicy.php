<?php

namespace App\Policies;

class CouponPolicy extends ManagedResourcePolicy
{
    protected function permission(): string
    {
        return 'manage-coupons';
    }
}
