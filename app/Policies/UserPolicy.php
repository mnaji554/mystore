<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage-customers');
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->hasPermission('manage-customers');
    }

    public function update(User $user, User $target): bool
    {
        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('manage-customers');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id
            && ! $target->isSuperAdmin()
            && $user->hasPermission('manage-customers')
            && $user->hasPermission('delete-records');
    }

    public function assignRole(User $user, User $target): bool
    {
        return $user->hasPermission('manage-staff') && $user->id !== $target->id;
    }
}
