<?php

namespace App\Policies;

use App\Models\User;

/** Base policy for admin-managed resources guarded by a "manage-*" permission. */
abstract class ManagedResourcePolicy
{
    abstract protected function permission(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission($this->permission());
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->hasPermission($this->permission());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission($this->permission());
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->hasPermission($this->permission());
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $user->hasPermission($this->permission()) && $user->hasPermission('delete-records');
    }
}
