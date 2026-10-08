<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\StoreException;
use App\Livewire\Account\Reviews;
use App\Livewire\Admin;
use App\Livewire\ProductShow;
use App\Models\Order;
use App\Models\Review;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ReviewService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    private function purchase($user, $product, OrderStatus $finalStatus = OrderStatus::Delivered): Order
    {
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $product->id);
        $orders = app(OrderService::class);
        $order = $orders->createFromCart($cart, $this->checkoutData($this->shipping()), $user);

        $path = [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        foreach ($path as $status) {
            if ($order->fresh()->status === $finalStatus) {
                break;
            }
            $orders->changeStatus($order->fresh(), $status);
        }

        return $order->fresh();
    }

    public function test_users_cannot_review_products_they_did_not_buy_or_have_not_received(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $reviews = app(ReviewService::class);

        try {
            $reviews->save($user, $product, ['rating' => 5]);
            $this->fail('unpurchased product');
        } catch (StoreException) {
        }

        $this->purchase($user, $product, OrderStatus::Shipped);

        $this->expectException(StoreException::class);
        $reviews->save($user, $product, ['rating' => 5]);
    }

    public function test_buyer_can_review_and_review_awaits_moderation(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);

        Livewire::actingAs($user)->test(ProductShow::class, ['slug' => $product->slug])
            ->set('rating', 4)->set('title', 'جيد')->set('comment', 'منتج ممتاز')
            ->call('saveReview')->assertHasNoErrors();

        $review = Review::firstOrFail();
        $this->assertSame(Review::PENDING, $review->status);
        $this->assertSame(0, $product->fresh()->reviews_count);

        $this->get('/products/'.$product->slug)->assertDontSee('منتج ممتاز');
    }

    public function test_review_validation(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);

        Livewire::actingAs($user)->test(ProductShow::class, ['slug' => $product->slug])
            ->set('rating', 9)->call('saveReview')->assertHasErrors('rating')
            ->set('rating', 3)->set('title', str_repeat('x', 200))->call('saveReview')->assertHasErrors('title');
    }

    public function test_guest_cannot_submit_review(): void
    {
        $product = $this->product();

        Livewire::test(ProductShow::class, ['slug' => $product->slug])->call('saveReview')->assertForbidden();
    }

    public function test_review_images_are_stored_securely(): void
    {
        Storage::fake('public');
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);

        Livewire::actingAs($user)->test(ProductShow::class, ['slug' => $product->slug])
            ->set('photos', [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.png', 300, 300)])
            ->call('saveReview')->assertHasNoErrors();

        $review = Review::with('images')->firstOrFail();
        $this->assertCount(2, $review->images);
        foreach ($review->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith('reviews/'.$review->id.'/', $image->path);
        }

        Livewire::actingAs($user)->test(ProductShow::class, ['slug' => $product->slug])
            ->set('photos', [UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
            ->call('saveReview')->assertHasErrors('photos.0');
    }

    public function test_average_rating_counts_only_approved_reviews(): void
    {
        $product = $this->product();
        $reviews = app(ReviewService::class);
        $users = collect(range(1, 3))->map(fn () => $this->customer());

        foreach ($users as $user) {
            $this->purchase($user, $product);
        }

        $r1 = $reviews->save($users[0], $product, ['rating' => 5]);
        $r2 = $reviews->save($users[1], $product, ['rating' => 3]);
        $reviews->save($users[2], $product, ['rating' => 1]); // stays pending

        $this->assertSame(0, $product->fresh()->reviews_count);

        $reviews->moderate($r1, Review::APPROVED);
        $reviews->moderate($r2, Review::APPROVED);

        $product->refresh();
        $this->assertSame(2, $product->reviews_count);
        $this->assertEquals(4.0, (float) $product->rating_avg);

        $reviews->moderate($r2, Review::REJECTED);
        $this->assertEquals(5.0, (float) $product->fresh()->rating_avg);
        $this->assertSame(1, $product->fresh()->reviews_count);
    }

    public function test_editing_a_review_sends_it_back_to_moderation_and_one_review_per_product(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);
        $reviews = app(ReviewService::class);

        $review = $reviews->moderate($reviews->save($user, $product, ['rating' => 5, 'comment' => 'قبل']), Review::APPROVED);
        $this->assertSame(1, $product->fresh()->reviews_count);

        $reviews->save($user, $product, ['rating' => 2, 'comment' => 'بعد']);

        $this->assertSame(1, Review::count());
        $this->assertSame(Review::PENDING, $review->fresh()->status);
        $this->assertSame('بعد', $review->fresh()->comment);
        $this->assertSame(0, $product->fresh()->reviews_count);
    }

    public function test_only_owner_or_moderator_can_delete(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);
        $review = app(ReviewService::class)->save($user, $product, ['rating' => 5]);

        $this->assertFalse($this->customer()->can('delete', $review));
        $this->assertTrue($user->can('delete', $review));
        $this->assertTrue($this->staff('manager')->can('delete', $review));
        $this->assertFalse($this->customer()->can('update', $review));

        Livewire::actingAs($this->customer())->test(Reviews::class)->call('delete', $review->id)->assertForbidden();
        Livewire::actingAs($user)->test(Reviews::class)->call('delete', $review->id);
        $this->assertSoftDeleted($review);
    }

    public function test_admin_moderation_screen_updates_product_rating(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->purchase($user, $product);
        $review = app(ReviewService::class)->save($user, $product, ['rating' => 4, 'comment' => 'للمراجعة']);

        Livewire::actingAs($this->staff('manager'))->test(Admin\Reviews::class)
            ->assertSee('للمراجعة')->call('moderate', $review->id, 'approved');

        $this->assertSame(Review::APPROVED, $review->fresh()->status);
        $this->assertEquals(4.0, (float) $product->fresh()->rating_avg);
        $this->get('/products/'.$product->slug)->assertSee('للمراجعة');

        Livewire::actingAs($this->customer())->test(Admin\Reviews::class)->assertForbidden();
    }
}
