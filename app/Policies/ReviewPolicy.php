<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->hasPermission('manage-reviews');
    }

    public function moderate(User $user): bool
    {
        return $user->hasPermission('manage-reviews');
    }
}
