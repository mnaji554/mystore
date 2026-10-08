<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\StoreException;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private readonly ImageService $images) {}

    public function hasPurchased(User $user, Product|int $product): bool
    {
        $productId = $product instanceof Product ? $product->id : $product;

        return OrderItem::query()->where('product_id', $productId)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', OrderStatus::Delivered))
            ->exists();
    }

    /**
     * Create or update the user's review. Reviews require a delivered purchase and admin approval.
     *
     * @param  array{rating:int,title?:?string,comment?:?string}  $data
     * @param  array<int, UploadedFile>  $uploads
     */
    public function save(User $user, Product $product, array $data, array $uploads = []): Review
    {
        if (! $this->hasPurchased($user, $product)) {
            throw new StoreException('يمكنك تقييم المنتجات التي اشتريتها وتم تسليمها فقط.');
        }

        $review = DB::transaction(function () use ($user, $product, $data) {
            $review = Review::withTrashed()->firstOrNew(['product_id' => $product->id, 'user_id' => $user->id]);

            if ($review->trashed()) {
                $review->restore();
            }

            $review->fill([
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'] ?? null,
                'status' => Review::PENDING,
                'approved_at' => null,
            ])->save();

            return $review;
        });

        foreach (array_slice($uploads, 0, 3) as $upload) {
            $stored = $this->images->store($upload, "reviews/{$review->id}", 1200, 300);
            $review->images()->create(['path' => $stored['path']]);
            $this->images->delete($stored['thumb_path']);
        }

        $this->refreshRating($product);

        return $review;
    }

    public function moderate(Review $review, string $status): Review
    {
        $review->update([
            'status' => $status,
            'approved_at' => $status === Review::APPROVED ? now() : null,
        ]);

        $this->refreshRating($review->product);

        return $review;
    }

    public function delete(Review $review): void
    {
        foreach ($review->images as $image) {
            $this->images->delete($image->path);
        }

        $product = $review->product;
        $review->delete();
        $this->refreshRating($product);
    }

    public function refreshRating(?Product $product): void
    {
        if (! $product) {
            return;
        }

        $stats = Review::query()->approved()->where('product_id', $product->id)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')->first();

        // Query update (not model save): the passed model may be stale, which would hide the change.
        Product::query()->whereKey($product->id)->update([
            'reviews_count' => (int) $stats->c,
            'rating_avg' => round((float) $stats->a, 2),
        ]);
    }
}
