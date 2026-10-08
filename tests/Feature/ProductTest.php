<?php

namespace Tests\Feature;

use App\Livewire\ProductListing;
use App\Livewire\ProductShow;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_discount_percentage_is_calculated_from_sale_price(): void
    {
        $product = $this->product(['price' => 200, 'sale_price' => 150]);
        $this->assertSame(25, $product->discount_percent);

        $product->update(['sale_price' => null]);
        $this->assertSame(0, $product->fresh()->discount_percent);

        // a sale price above the regular price is ignored
        $product->update(['sale_price' => 300]);
        $this->assertSame(0, $product->fresh()->discount_percent);
        $this->assertEquals(200.0, $product->fresh()->final_price);
    }

    public function test_listing_shows_only_active_products(): void
    {
        $this->product(['name_ar' => 'منتج ظاهر']);
        $this->product(['name_ar' => 'منتج مسودة', 'status' => Product::STATUS_DRAFT]);

        $this->get('/products')->assertOk()->assertSee('منتج ظاهر')->assertDontSee('منتج مسودة');
    }

    public function test_draft_product_page_is_not_found(): void
    {
        $draft = $this->product(['status' => Product::STATUS_DRAFT]);
        $active = $this->product();

        $this->get('/products/'.$draft->slug)->assertNotFound();
        $this->get('/products/'.$active->slug)->assertOk()->assertSee($active->name_ar)
            ->assertSee('application/ld+json', false)->assertSee('"@type":"Product"', false)->assertSee('BreadcrumbList', false);
    }

    public function test_search_matches_name_sku_description_and_category(): void
    {
        $category = $this->category(['name_ar' => 'ساعات فاخرة']);
        $byName = $this->product(['name_ar' => 'ساعة ذهبية', 'category_id' => $category->id]);
        $bySku = $this->product(['name_ar' => 'منتج آخر', 'sku' => 'ZX-9000']);
        $byDescription = $this->product(['name_ar' => 'منتج ثالث', 'description' => 'يتميز بشاشة لمس كبيرة']);
        $other = $this->product(['name_ar' => 'منتج مختلف تماماً']);

        Livewire::test(ProductListing::class, ['mode' => 'search'])
            ->set('q', 'ذهبية')->assertSee($byName->name_ar)->assertDontSee($other->name_ar)
            ->set('q', 'ZX-9000')->assertSee($bySku->name_ar)->assertDontSee($byName->name_ar)
            ->set('q', 'شاشة لمس')->assertSee($byDescription->name_ar)
            ->set('q', 'ساعات فاخرة')->assertSee($byName->name_ar);
    }

    public function test_search_input_with_wildcards_is_treated_literally(): void
    {
        $this->product(['name_ar' => 'منتج عادي']);

        Livewire::test(ProductListing::class, ['mode' => 'search'])
            ->set('q', '%')->assertDontSee('منتج عادي');
    }

    public function test_category_page_includes_nested_subcategories(): void
    {
        $parent = $this->category(['slug' => 'electronics']);
        $child = $this->category(['slug' => 'phones', 'parent_id' => $parent->id]);
        $inChild = $this->product(['category_id' => $child->id, 'name_ar' => 'هاتف تجريبي']);
        $outside = $this->product(['name_ar' => 'منتج خارج التصنيف']);

        $this->get('/categories/electronics')->assertOk()->assertSee($inChild->name_ar)->assertDontSee($outside->name_ar);
        $this->get('/categories/missing')->assertNotFound();
    }

    public function test_filters_and_sorting(): void
    {
        $cheap = $this->product(['name_ar' => 'رخيص', 'price' => 50]);
        $pricey = $this->product(['name_ar' => 'غالي', 'price' => 500]);
        $soldOut = $this->product(['name_ar' => 'نافد', 'price' => 60, 'stock' => 0]);

        Livewire::test(ProductListing::class, ['mode' => 'all'])
            ->set('min', '100')->assertSee('غالي')->assertDontSee('رخيص')
            ->set('min', '')->set('max', '55')->assertSee('رخيص')->assertDontSee('غالي')
            ->set('max', '')->set('inStock', true)->assertDontSee('نافد')->assertSee('رخيص');

        $ids = app(ProductRepository::class)->paginate(['sort' => 'price_desc'])->pluck('id')->all();
        $this->assertSame([$pricey->id, $soldOut->id, $cheap->id], $ids);
    }

    public function test_special_pages_filter_by_flags(): void
    {
        $this->product(['name_ar' => 'جديد جداً', 'is_new' => true]);
        $this->product(['name_ar' => 'مخفض جداً', 'price' => 100, 'sale_price' => 70]);
        $this->product(['name_ar' => 'عادي جداً']);

        $this->get('/products/new')->assertSee('جديد جداً')->assertDontSee('عادي جداً');
        $this->get('/products/sale')->assertSee('مخفض جداً')->assertDontSee('عادي جداً');
        $this->get('/products/bestsellers')->assertOk();
    }

    public function test_variant_product_shows_options_and_unavailable_combination(): void
    {
        $product = $this->variantProduct([5, 0]);

        Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->assertSee('المقاس')
            ->call('selectOption', 'size', 'L')
            ->assertSee('نفدت الكمية');
    }

    public function test_sitemap_and_robots(): void
    {
        $product = $this->product();

        $this->get('/sitemap.xml')->assertOk()->assertSee($product->slug)->assertHeader('Content-Type', 'application/xml');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /admin');
    }

    public function test_product_page_has_seo_meta(): void
    {
        $product = $this->product(['seo_title' => 'عنوان سيو مخصص', 'seo_description' => 'وصف سيو مخصص']);

        $this->get('/products/'.$product->slug)
            ->assertSee('<title>عنوان سيو مخصص', false)
            ->assertSee('rel="canonical" href="'.$product->url.'"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('name="twitter:card"', false);
    }
}
