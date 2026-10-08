<?php

namespace Tests\Feature;

use App\Jobs\PruneAbandonedCarts;
use App\Jobs\SendLowStockReport;
use App\Livewire\Account\Addresses;
use App\Livewire\ProductListing;
use App\Livewire\WishlistPage;
use App\Models\Cart;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\User;
use App\Notifications\ContactMessageNotification;
use App\Notifications\LowStockNotification;
use App\Services\ImageService;
use App\Services\ReportService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    public function test_public_pages_render(): void
    {
        $this->product();

        foreach (['/', '/products', '/products/new', '/products/bestsellers', '/products/sale', '/search?q='.urlencode('منتج'), '/cart', '/coupons',
            '/contact', '/track-order', '/pages/about', '/pages/privacy', '/pages/terms', '/pages/returns', '/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/')->assertSee('dir="rtl"', false)->assertSee('lang="ar"', false);
    }

    public function test_search_pages_are_noindexed_and_have_canonical_urls(): void
    {
        $this->get('/search?q=abc')->assertSee('noindex', false);
        $this->get('/search?q=%FF%FE%FA&sort=%C3')->assertOk(); // malformed UTF-8 must not crash
        $this->get('/products')->assertDontSee('noindex', false)->assertSee('rel="canonical"', false);
    }

    public function test_wishlist_requires_login_and_supports_add_remove_and_move_to_cart(): void
    {
        $product = $this->product(['stock' => 3]);
        $user = $this->customer();

        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('toggleWishlist', $product->id)->assertRedirect(route('login'));
        $this->assertDatabaseCount('wishlist_items', 0);

        $this->actingAs($user);
        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('toggleWishlist', $product->id);
        $this->assertDatabaseCount('wishlist_items', 1);

        Livewire::test(WishlistPage::class)->assertSee($product->name_ar)->call('moveToCart', $product->id)->assertDontSee($product->name_ar);
        $this->assertDatabaseCount('wishlist_items', 0);
        $this->assertSame(1, $user->fresh()->cart->items()->first()->quantity);

        Livewire::test(ProductListing::class, ['mode' => 'all'])->call('toggleWishlist', $product->id)->call('toggleWishlist', $product->id);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_wishlist_of_one_user_is_not_visible_to_another(): void
    {
        $product = $this->product(['name_ar' => 'منتج خاص']);
        $a = $this->customer();
        $b = $this->customer();

        Livewire::actingAs($a)->test(ProductListing::class, ['mode' => 'all'])->call('toggleWishlist', $product->id);
        Livewire::actingAs($b)->test(WishlistPage::class)->assertDontSee('منتج خاص');
    }

    public function test_address_book_crud_and_default_handling(): void
    {
        $user = $this->customer();
        $this->actingAs($user);

        $fill = fn ($c, $city) => $c->call('create')->set('full_name', 'أحمد')->set('phone', '0501234567')->set('city', $city)->set('street', 'شارع 1');

        $c = Livewire::test(Addresses::class);
        $fill($c, 'الرياض')->call('save')->assertHasNoErrors();
        $fill($c, 'جدة')->call('save');

        $this->assertSame(2, $user->addresses()->count());
        $first = $user->addresses()->where('city', 'الرياض')->first();
        $second = $user->addresses()->where('city', 'جدة')->first();
        $this->assertTrue($first->is_default); // first address becomes default automatically

        $c->call('makeDefault', $second->id);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);

        $c->call('delete', $second->id);
        $this->assertTrue($first->fresh()->is_default); // default falls back to the remaining address

        $fill($c, '')->call('save')->assertHasErrors('city');
        $c->call('create')->set('full_name', 'x')->set('phone', 'abc')->call('save')->assertHasErrors('phone');
    }

    public function test_contact_form_stores_message_and_notifies_store(): void
    {
        Notification::fake();

        $this->post('/contact', ['name' => 'سائل', 'email' => 'asker@example.com', 'subject' => 'استفسار', 'message' => 'هل المنتج متوفر بألوان أخرى؟'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['email' => 'asker@example.com']);
        Notification::assertSentOnDemand(ContactMessageNotification::class);

        $this->post('/contact', ['name' => '', 'email' => 'x', 'subject' => '', 'message' => 'قصير'])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->post('/contact', ['name' => 'بوت', 'email' => 'b@example.com', 'subject' => 'سبام', 'message' => 'رسالة سبام طويلة', 'website' => 'http://spam'])->assertSessionHasErrors('website');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_coupons_page_lists_only_public_valid_coupons(): void
    {
        Coupon::factory()->create(['code' => 'PUBLICOK', 'is_public' => true]);
        Coupon::factory()->create(['code' => 'HIDDENONE', 'is_public' => false]);
        Coupon::factory()->create(['code' => 'EXPIREDONE', 'is_public' => true, 'expires_at' => now()->subDay()]);
        Coupon::factory()->create(['code' => 'USEDUPONE', 'is_public' => true, 'usage_limit' => 1, 'used_count' => 1]);

        $this->get('/coupons')->assertSee('PUBLICOK')->assertDontSee('HIDDENONE')->assertDontSee('EXPIREDONE')->assertDontSee('USEDUPONE');
    }

    public function test_image_service_reencodes_to_webp_and_rejects_non_images(): void
    {
        Storage::fake('public');
        $service = app(ImageService::class);

        $paths = $service->store(UploadedFile::fake()->image('big.jpg', 3000, 2000), 'products/1');
        Storage::disk('public')->assertExists([$paths['path'], $paths['thumb_path']]);
        $this->assertStringEndsWith('.webp', $paths['path']);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($paths['path']));
        $this->assertLessThanOrEqual(1400, max($w, $h));
        [$tw] = getimagesizefromstring(Storage::disk('public')->get($paths['thumb_path']));
        $this->assertLessThanOrEqual(480, $tw);

        $fake = UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo "pwned"; ?>');
        $this->expectException(\RuntimeException::class);
        $service->store($fake, 'products/1');
    }

    public function test_responses_include_security_headers(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_xss_in_product_name_is_escaped(): void
    {
        $product = $this->product(['name_ar' => '<script>alert(1)</script>']);

        $this->get('/products/'.$product->slug)->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->get('/products')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_mass_assignment_cannot_change_role_or_active_flag(): void
    {
        $user = $this->customer();
        $probe = new User(['name' => 'x', 'role_id' => 999, 'is_active' => false]);

        $this->assertNull($probe->role_id);
        $this->assertNull($probe->getAttribute('is_active'));
        $this->assertSame('x', $probe->name);

        $this->actingAs($user)->put('/account/profile', ['name' => 'مستخدم', 'email' => $user->email, 'role_id' => 1, 'is_active' => 0])->assertSessionHasNoErrors();
        $this->assertSame('customer', $user->fresh()->role->slug);
    }

    public function test_scheduler_has_the_expected_jobs(): void
    {
        $events = collect(app(Schedule::class)->events())->map->description;

        foreach (['cancel-unpaid-orders', 'low-stock-report', 'prune-abandoned-carts'] as $name) {
            $this->assertTrue($events->contains($name), $name);
        }
    }

    public function test_low_stock_report_notifies_staff(): void
    {
        $admin = $this->staff('admin');
        $this->product(['name_ar' => 'قارب على النفاد', 'stock' => 1]);
        $this->product(['stock' => 500]);
        Notification::fake();

        (new SendLowStockReport)->handle(app(ReportService::class));

        Notification::assertSentTo($admin, LowStockNotification::class, fn ($n) => count($n->items) === 1 && $n->items[0]['stock'] === 1);
    }

    public function test_abandoned_guest_carts_are_pruned(): void
    {
        $old = Cart::create(['token' => 'old']);
        $old->forceFill(['updated_at' => now()->subDays(40)])->saveQuietly();
        Cart::create(['token' => 'new']);
        $userCart = Cart::create(['user_id' => User::factory()->customer()->create()->id]);
        $userCart->forceFill(['updated_at' => now()->subDays(90)])->saveQuietly();

        (new PruneAbandonedCarts)->handle();

        $this->assertDatabaseMissing('carts', ['token' => 'old']);
        $this->assertDatabaseHas('carts', ['token' => 'new']);
        $this->assertDatabaseHas('carts', ['id' => $userCart->id]);
    }
}
