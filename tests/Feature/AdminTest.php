<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Admin;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ShippingCompany;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\CartService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\ShippingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private function placeOrder(?User $user = null): Order
    {
        $user ??= $this->customer();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $this->product(['price' => 100, 'stock' => 10])->id, null, 2);

        return app(OrderService::class)->createFromCart($cart, $this->checkoutData($this->shipping()), $user);
    }

    public function test_dashboard_reports_sales_and_low_stock(): void
    {
        $order = $this->placeOrder();
        $this->product(['name_ar' => 'منتج قليل', 'stock' => 2]);
        $this->actingAs($this->staff('admin'));

        $component = Livewire::test(Admin\Dashboard::class);
        $component->assertSee('إجمالي المبيعات')->assertSee('منتج قليل')->assertSee($order->order_number);

        $stats = app(ReportService::class)->summary();
        $this->assertEquals((float) $order->grand_total, $stats['total_sales']);
        $this->assertSame(1, $stats['orders_count']);
        $this->assertSame(1, $stats['new_orders']);
        $this->assertGreaterThanOrEqual(1, $stats['low_stock_count']);
        $this->assertEquals((float) $order->grand_total, $stats['avg_order_value']);
    }

    public function test_dashboard_metrics_link_to_matching_admin_sections_and_filters(): void
    {
        $this->actingAs($this->staff('admin'));

        $component = Livewire::test(Admin\Dashboard::class);

        $component->assertSee('href="'.e(route('admin.orders.index', [
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
        ])).'"', false);
        $component->assertSee('href="'.e(route('admin.orders.index', [
            'status' => OrderStatus::Pending->value,
        ])).'"', false);
        $component->assertSee('href="'.e(route('admin.customers.index', [
            'role' => Role::CUSTOMER,
        ])).'"', false);
        $component->assertSee('href="'.e(route('admin.products.index', ['stock' => 'low'])).'"', false);
    }

    public function test_category_chart_target_opens_the_matching_category_for_editing(): void
    {
        $category = $this->category(['name_ar' => 'تصنيف مرتبط']);
        $this->actingAs($this->staff('admin'));

        $this->get(route('admin.categories', ['edit' => $category->id]))
            ->assertOk()
            ->assertSee('تعديل التصنيف')
            ->assertSee('تصنيف مرتبط');
    }

    public function test_charts_data_reflect_orders(): void
    {
        $order = $this->placeOrder();
        $reports = app(ReportService::class);

        $this->assertSame(30, count($reports->dailySales(30)['labels']));
        $this->assertEquals((float) $order->grand_total, end($reports->dailySales(30)['data']));
        $this->assertEquals((float) $order->grand_total, end($reports->monthlySales(12)['data']));
        $this->assertSame(1, $reports->ordersByStatus()['data'][0]);
        $this->assertNotEmpty($reports->topProducts()['labels']);
    }

    public function test_admin_can_create_a_product_with_variants_and_images(): void
    {
        Storage::fake('public');
        $category = $this->category();
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\ProductForm::class)
            ->set('form.name_ar', 'قميص جديد')->set('form.name_en', 'New Shirt')->set('form.sku', 'SH-001')
            ->set('form.category_id', (string) $category->id)->set('form.price', '200')->set('form.sale_price', '150')->set('form.stock', 0)
            ->set('form.is_featured', true)
            ->call('addVariant')->set('variants.0.sku', 'SH-001-R')->set('variants.0.color', 'أحمر')->set('variants.0.color_hex', '#ff0000')->set('variants.0.size', 'M')->set('variants.0.stock', 7)->set('variants.0.price', '200')
            ->call('addVariant')->set('variants.1.sku', 'SH-001-B')->set('variants.1.color', 'أزرق')->set('variants.1.stock', 3)->set('variants.1.price', '210')
            ->set('uploads', [UploadedFile::fake()->image('one.jpg', 900, 900), UploadedFile::fake()->image('two.png', 500, 500)])
            ->call('save')->assertHasNoErrors();

        $product = Product::with(['variants.options', 'images'])->firstOrFail();
        $this->assertSame('new-shirt', $product->slug);
        $this->assertSame(25, $product->discount_percent);
        $this->assertCount(2, $product->variants);
        $this->assertSame(10, $product->available_stock);
        $this->assertSame('أحمر', $product->variants[0]->option('color')->value);
        $this->assertSame('#ff0000', $product->variants[0]->option('color')->color_hex);
        $this->assertCount(2, $product->images);
        $this->assertTrue($product->images[0]->is_primary);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            Storage::disk('public')->assertExists($image->thumb_path);
            $this->assertStringStartsWith('products/'.$product->id.'/', $image->path);
            $this->assertStringEndsWith('.webp', $image->path);
        }
    }

    public function test_product_form_validation_and_unique_constraints(): void
    {
        $this->product(['sku' => 'TAKEN']);
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\ProductForm::class)
            ->call('save')->assertHasErrors(['form.name_ar', 'form.sku', 'form.price'])
            ->set('form.name_ar', 'س')->set('form.sku', 'TAKEN')->set('form.price', '100')->call('save')->assertHasErrors('form.sku')
            ->set('form.sku', 'NEW')->set('form.sale_price', '150')->call('save')->assertHasErrors('form.sale_price')
            ->set('form.sale_price', '')->set('uploads', [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->call('save')->assertHasErrors('uploads.0');

        $this->assertSame(1, Product::count());
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $existing = $this->variantProduct([3]);
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\ProductForm::class)
            ->set('form.name_ar', 'منتج')->set('form.sku', 'UNIQUE-1')->set('form.price', '100')
            ->call('addVariant')->set('variants.0.sku', $existing->variants[0]->sku)->set('variants.0.size', 'M')
            ->call('save')->assertHasErrors('variants.0.sku');
    }

    public function test_admin_can_edit_product_manage_images_and_delete_variants(): void
    {
        Storage::fake('public');
        $product = $this->variantProduct([4, 6]);
        $this->actingAs($this->staff('admin'));

        $component = Livewire::test(Admin\ProductForm::class, ['product' => $product])
            ->assertSet('form.sku', $product->sku)
            ->set('form.price', '120')->set('form.name_ar', 'اسم معدل')
            ->set('uploads', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
            ->call('removeVariant', 1)->call('save')->assertHasNoErrors();

        $product->refresh()->load(['variants', 'images']);
        $this->assertSame('اسم معدل', $product->name_ar);
        $this->assertCount(1, $product->variants);
        $this->assertCount(2, $product->images);

        [$first, $second] = $product->images;
        $this->assertTrue($first->is_primary);

        $component = Livewire::test(Admin\ProductForm::class, ['product' => $product]);
        $component->call('setPrimary', $second->id);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertFalse($first->fresh()->is_primary);

        $component->call('moveImage', $first->id, 'up')->call('moveImage', $first->id, 'down');
        $component->call('deleteImage', $second->id);
        Storage::disk('public')->assertMissing($second->path);
        $this->assertTrue($first->fresh()->is_primary);
        $this->assertCount(1, $product->images()->get());
    }

    public function test_product_list_search_filter_sort_and_bulk_delete(): void
    {
        $category = $this->category();
        $a = $this->product(['name_ar' => 'ألف', 'category_id' => $category->id, 'price' => 10]);
        $b = $this->product(['name_ar' => 'باء', 'price' => 30, 'status' => 'draft']);
        $c = $this->product(['name_ar' => 'جيم', 'price' => 20]);
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\ProductIndex::class)
            ->assertSee('ألف')->assertSee('باء')
            ->set('search', 'باء')->assertSee('باء')->assertDontSee('ألف')
            ->set('search', '')->set('status', 'draft')->assertSee('باء')->assertDontSee('جيم')
            ->set('status', '')->set('category', $category->id)->assertSee('ألف')->assertDontSee('جيم')
            ->set('category', null)->call('sortBy', 'price');

        $ids = Livewire::test(Admin\ProductIndex::class)->call('sortBy', 'price')->viewData('products')->pluck('id')->all();
        $this->assertSame([$a->id, $c->id, $b->id], $ids);

        Livewire::test(Admin\ProductIndex::class)->set('selected', [(string) $a->id, (string) $c->id])->call('bulkDelete');

        $this->assertSoftDeleted($a);
        $this->assertSoftDeleted($c);
        $this->assertNotSoftDeleted($b);
    }

    public function test_category_crud_with_nesting_and_cycle_protection(): void
    {
        $this->actingAs($this->staff('admin'));

        $component = Livewire::test(Admin\Categories::class)
            ->call('create')->set('form.name_ar', 'إلكترونيات')->set('form.name_en', 'Electronics')->call('save')->assertHasNoErrors();

        $parent = Category::firstOrFail();
        $this->assertSame('electronics', $parent->slug);

        $component->call('create')->set('form.name_ar', 'هواتف')->set('form.name_en', 'Phones')->set('form.parent_id', (string) $parent->id)->call('save');
        $child = Category::where('slug', 'phones')->firstOrFail();
        $this->assertSame($parent->id, $child->parent_id);

        // making the parent a child of its own child is rejected
        $component->call('edit', $parent->id)->set('form.parent_id', (string) $child->id)->call('save')->assertHasErrors('form.parent_id');
        $this->assertNull($parent->fresh()->parent_id);

        // duplicated slug
        $component->call('create')->set('form.name_ar', 'x')->set('form.slug', 'phones')->call('save')->assertHasErrors('form.slug');

        // cannot delete a category that has children, can delete a leaf
        $component->call('delete', $parent->id);
        $this->assertNotSoftDeleted($parent);
        $component->call('delete', $child->id);
        $this->assertSoftDeleted($child);

        // ordering
        $other = $this->category(['sort_order' => 5]);
        $component->call('move', $other->id, 'up');
        $this->assertLessThan($parent->fresh()->sort_order, $other->fresh()->sort_order);
    }

    public function test_admin_changes_order_status_with_note_and_tracking(): void
    {
        $order = $this->placeOrder();
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        Livewire::test(Admin\OrderShow::class, ['order' => $order])
            ->set('newStatus', 'shipped')->call('changeStatus')->assertHasErrors('newStatus')
            ->set('newStatus', 'confirmed')->set('note', 'تم التأكيد هاتفياً')->call('changeStatus')->assertHasNoErrors()
            ->set('trackingNumber', 'AB123')->call('saveTracking')->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame('AB123', $order->tracking_number);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'user_id' => $admin->id, 'note' => 'تم التأكيد هاتفياً', 'to_status' => 'confirmed']);
    }

    public function test_tracking_url_is_generated_from_the_shipping_company_template(): void
    {
        $company = ShippingCompany::create(['name' => 'ناقل', 'tracking_url_template' => 'https://track.example/?n={number}']);
        $method = ShippingMethod::factory()->create(['shipping_company_id' => $company->id]);
        $user = $this->customer();
        $carts = app(CartService::class);
        $cart = $carts->resolve(true, $user);
        $carts->add($cart, $this->product()->id);
        $order = app(OrderService::class)->createFromCart($cart, $this->checkoutData($method), $user);

        $this->actingAs($this->staff('admin'));
        Livewire::test(Admin\OrderShow::class, ['order' => $order])->set('trackingNumber', 'X 99')->call('saveTracking');

        $this->assertSame('https://track.example/?n=X+99', $order->fresh()->tracking_url);
    }

    public function test_order_list_search_and_filters(): void
    {
        $alice = $this->customer(['name' => 'أليس']);
        $o1 = $this->placeOrder($alice);
        $o1->update(['customer_name' => 'أليس']);
        $o2 = $this->placeOrder();
        app(OrderService::class)->changeStatus($o2, OrderStatus::Confirmed);
        $this->actingAs($this->staff('manager'));

        Livewire::test(Admin\OrderIndex::class)
            ->assertSee($o1->order_number)->assertSee($o2->order_number)
            ->set('search', $o1->order_number)->assertSee($o1->order_number)->assertDontSee($o2->order_number)
            ->set('search', 'أليس')->assertSee($o1->order_number)->assertDontSee($o2->order_number)
            ->set('search', '')->set('status', 'confirmed')->assertSee($o2->order_number)->assertDontSee($o1->order_number)
            ->set('status', '')->set('from', now()->addDay()->format('Y-m-d'))->assertDontSee($o1->order_number);
    }

    public function test_invoice_html_and_pdf(): void
    {
        $order = $this->placeOrder();
        $admin = $this->staff('admin');

        $html = app(InvoiceService::class)->html($order);
        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString('INV-', $html);
        $this->assertStringContainsString('dir="rtl"', $html);

        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))->assertOk()->assertSee($order->items[0]->name);

        $response = $this->get(route('admin.orders.invoice.pdf', $order));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // the owner downloads it from the account area; strangers cannot
        $this->actingAs($order->user)->get(route('account.orders.invoice', $order->order_number))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->customer())->get(route('account.orders.invoice', $order->order_number))->assertForbidden();

        // one invoice per order, stable number
        $this->assertSame(1, Invoice::count());
    }

    public function test_coupon_management(): void
    {
        $product = $this->product();
        $category = $this->category();
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Coupons::class)
            ->call('create')->set('form.code', 'save-20')->set('form.type', 'percentage')->set('form.value', '20')->set('form.max_discount', '100')
            ->set('form.min_order_amount', '50')->set('form.usage_limit', '10')->set('form.usage_limit_per_user', '1')
            ->set('form.starts_at', now()->format('Y-m-d\TH:i'))->set('form.expires_at', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('addProduct', $product->id)->set('categoryIds', [(string) $category->id])
            ->call('save')->assertHasNoErrors();

        $coupon = Coupon::with(['products', 'categories'])->firstOrFail();
        $this->assertSame('SAVE-20', $coupon->code);
        $this->assertSame([$product->id], $coupon->products->pluck('id')->all());
        $this->assertSame([$category->id], $coupon->categories->pluck('id')->all());
        $this->assertFalse($coupon->isGlobal());

        Livewire::test(Admin\Coupons::class)
            ->call('create')->set('form.code', 'SAVE-20')->set('form.value', '10')->call('save')->assertHasErrors('form.code')
            ->set('form.code', 'BAD')->set('form.value', '150')->call('save')->assertHasErrors('form.value')
            ->set('form.value', '10')->set('form.expires_at', now()->subDay()->format('Y-m-d\TH:i'))->set('form.starts_at', now()->format('Y-m-d\TH:i'))->call('save')->assertHasErrors('form.expires_at');
    }

    public function test_shipping_management(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Shipping::class)
            ->call('newCompany')->set('company.name', 'ناقل سريع')->set('company.tracking_url_template', 'https://t.example/{number}')->call('saveCompany')->assertHasNoErrors()
            ->call('newMethod')->set('method.name', 'توصيل مجاني')->set('method.price', '0')->set('method.is_free', true)->set('method.delivery_days_min', 2)->set('method.delivery_days_max', 1)->call('saveMethod')->assertHasErrors('method.delivery_days_max')
            ->set('method.delivery_days_max', 4)->set('method.free_shipping_threshold', '300')->call('saveMethod')->assertHasNoErrors();

        $this->assertSame(1, ShippingCompany::count());
        $method = ShippingMethod::firstOrFail();
        $this->assertTrue($method->is_free);
        $this->assertSame(0.0, app(ShippingService::class)->cost($method, 10));
    }

    public function test_customer_management_toggle_and_delete(): void
    {
        $customer = $this->customer(['name' => 'عميل مميز']);
        $this->placeOrder($customer);
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        Livewire::test(Admin\CustomerIndex::class)->assertSee($customer->email)->call('toggleActive', $customer->id);
        $this->assertFalse($customer->fresh()->is_active);

        Livewire::test(Admin\CustomerShow::class, ['user' => $customer])->assertSee($customer->name)
            ->set('name', 'اسم مُحدّث')->call('save')->assertHasNoErrors();
        $this->assertSame('اسم مُحدّث', $customer->fresh()->name);

        Livewire::test(Admin\CustomerIndex::class)->call('delete', $customer->id);
        $this->assertSoftDeleted($customer);

        Livewire::test(Admin\CustomerIndex::class)->call('delete', $admin->id)->assertForbidden();
    }

    public function test_settings_are_saved_and_used_by_the_storefront(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Settings::class)
            ->set('form.store_name', 'متجر الاختبار')->set('form.tax_rate', '5')->set('form.currency_symbol', 'ر.س')
            ->call('save')->assertHasNoErrors();

        $this->get('/')->assertSee('متجر الاختبار');
        $this->assertSame(5.0, app(CartService::class)->tax(100.0));

        Livewire::test(Admin\Settings::class)->set('form.tax_rate', '500')->call('save')->assertHasErrors('form.tax_rate');
    }

    public function test_admin_can_manage_payment_methods_without_saving_unlisted_settings(): void
    {
        config(['services.stripe.key' => 'pk_test_x', 'services.stripe.secret' => 'sk_test_x']);
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Settings::class)
            ->set('form.payment_cod_enabled', false)
            ->set('form.payment_stripe_enabled', true)
            ->set('form.unlisted_setting', 'unexpected')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('طرق الدفع')
            ->assertDontSee('sk_test_x');

        $this->assertDatabaseHas('store_settings', ['key' => 'payment_cod_enabled', 'value' => '0']);
        $this->assertDatabaseHas('store_settings', ['key' => 'payment_stripe_enabled', 'value' => '1']);
        $this->assertNull(Setting::get('unlisted_setting'));

        $available = app(PaymentService::class)->available();
        $this->assertFalse($available->has('cod'));
        $this->assertTrue($available->has('stripe'));
    }

    public function test_payment_method_toggles_reject_non_boolean_values(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(Admin\Settings::class)
            ->set('form.payment_cod_enabled', 'enabled')
            ->call('save')
            ->assertHasErrors('form.payment_cod_enabled');

        $this->assertDatabaseMissing('store_settings', ['key' => 'payment_cod_enabled']);
    }
}
