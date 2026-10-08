<?php

namespace App\Policies;

class ProductPolicy extends ManagedResourcePolicy
{
    protected function permission(): string
    {
        return 'manage-products';
    }
}
