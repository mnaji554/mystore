<?php

namespace App\Services;

use App\Exceptions\StoreException;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function forUser(User $user): Wishlist
    {
        return Wishlist::firstOrCreate(['user_id' => $user->id]);
    }

    /** @return list<int> */
    public function productIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return Wishlist::query()->where('user_id', $user->id)->first()
            ?->items()->pluck('product_id')->all() ?? [];
    }

    public function count(?User $user): int
    {
        return count($this->productIds($user));
    }

    /** Returns true when the product was added, false when removed. */
    public function toggle(User $user, int $productId): bool
    {
        $wishlist = $this->forUser($user);
        $existing = $wishlist->items()->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        if (! Product::query()->active()->whereKey($productId)->exists()) {
            throw new StoreException('هذا المنتج غير متاح.');
        }

        $wishlist->items()->create(['product_id' => $productId]);

        return true;
    }

    public function remove(User $user, int $productId): void
    {
        $this->forUser($user)->items()->where('product_id', $productId)->delete();
    }

    public function products(User $user): Collection
    {
        return Product::query()->active()
            ->with(['primaryImage', 'variants', 'category:id,name_ar,slug'])
            ->whereIn('id', $this->productIds($user))
            ->latest('id')->get();
    }
}
