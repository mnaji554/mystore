<?php

namespace App\Policies;

class CategoryPolicy extends ManagedResourcePolicy
{
    protected function permission(): string
    {
        return 'manage-categories';
    }
}
