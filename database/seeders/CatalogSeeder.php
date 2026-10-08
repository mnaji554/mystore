<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    private const COLORS = [
        'أسود' => '#111827', 'أبيض' => '#f9fafb', 'أزرق' => '#2563eb', 'أحمر' => '#dc2626',
        'رمادي' => '#6b7280', 'بيج' => '#d6c4a8', 'أخضر' => '#16a34a', 'وردي' => '#ec4899', 'بني' => '#7c4a21',
    ];

    public function run(ImageService $images): void
    {
        $categories = $this->categories();

        foreach ($this->products() as $slug => $items) {
            $category = $categories[$slug];

            foreach ($items as $i => $item) {
                $this->createProduct($category, $slug, $i, $item, $images);
            }
        }
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $definitions = [
            'electronics' => ['إلكترونيات', 'Electronics', null],
            'phones' => ['هواتف ذكية', 'Smartphones', 'electronics'],
            'computers' => ['حواسيب وأجهزة لوحية', 'Computers & Tablets', 'electronics'],
            'men' => ['أزياء رجالية', "Men's Fashion", null],
            'women' => ['أزياء نسائية', "Women's Fashion", null],
            'shoes' => ['أحذية', 'Shoes', null],
            'home' => ['المنزل والمطبخ', 'Home & Kitchen', null],
            'beauty' => ['الجمال والعناية', 'Beauty & Care', null],
            'sports' => ['الرياضة واللياقة', 'Sports & Fitness', null],
            'toys' => ['ألعاب وهدايا', 'Toys & Gifts', null],
            // inner (third level) categories
            'android-phones' => ['هواتف أندرويد', 'Android Phones', 'phones'],
            'phone-accessories' => ['إكسسوارات الهواتف', 'Phone Accessories', 'phones'],
            'laptops' => ['حواسيب محمولة', 'Laptops', 'computers'],
            'tablets' => ['أجهزة لوحية', 'Tablets', 'computers'],
            'pc-accessories' => ['ملحقات الكمبيوتر', 'PC Accessories', 'computers'],
            'men-clothes' => ['ملابس رجالية', "Men's Clothing", 'men'],
            'men-shirts' => ['قمصان', 'Shirts', 'men-clothes'],
            'men-pants' => ['بناطيل', 'Pants', 'men-clothes'],
            'women-clothes' => ['ملابس نسائية', "Women's Clothing", 'women'],
            'dresses' => ['فساتين', 'Dresses', 'women-clothes'],
            'abayas' => ['عبايات', 'Abayas', 'women-clothes'],
            'kitchen' => ['المطبخ', 'Kitchen', 'home'],
            'cookware' => ['أواني الطهي', 'Cookware', 'kitchen'],
            'small-appliances' => ['أجهزة صغيرة', 'Small Appliances', 'kitchen'],
            'fitness' => ['معدات اللياقة', 'Fitness Equipment', 'sports'],
            'yoga' => ['اليوغا', 'Yoga', 'fitness'],
        ];

        $created = [];
        $order = 1;

        foreach ($definitions as $slug => [$ar, $en, $parent]) {
            $created[$slug] = Category::updateOrCreate(['slug' => $slug], [
                'parent_id' => $parent ? $created[$parent]->id : null,
                'name_ar' => $ar,
                'name_en' => $en,
                'description' => "تسوّق أفضل منتجات {$ar} بأسعار منافسة وجودة مضمونة.",
                'sort_order' => $order++,
                'is_active' => true,
                'seo_title' => $ar.' | أفضل الأسعار',
                'seo_description' => "اكتشف مجموعة {$ar} المتنوعة مع شحن سريع وإرجاع سهل.",
            ]);
        }

        return $created;
    }

    private function createProduct(Category $category, string $slug, int $index, array $item, ImageService $images): void
    {
        [$nameAr, $nameEn, $price, $sale, $variantSpec] = $item + [4 => null];
        $sku = 'MS-'.strtoupper(substr($slug, 0, 3)).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

        $product = Product::withTrashed()->firstOrNew(['sku' => $sku]);
        $product->fill([
            'category_id' => $category->id,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn).'-'.strtolower($sku),
            'short_description' => "{$nameAr} بجودة عالية وتصميم عملي يناسب الاستخدام اليومي.",
            'description' => "{$nameAr}\n\nمنتج مختار بعناية من أفضل الموردين، يجمع بين المتانة والأناقة وسهولة الاستخدام.\n\nالمميزات:\n• خامات عالية الجودة\n• تصميم عصري يناسب جميع الأذواق\n• ضمان استبدال خلال 14 يوماً\n• شحن سريع وتغليف آمن",
            'price' => $price,
            'sale_price' => $sale,
            'cost_price' => round($price * 0.6, 2),
            'stock' => $variantSpec ? 0 : random_int(8, 60),
            'weight' => round(random_int(100, 3000) / 1000, 3),
            'status' => Product::STATUS_ACTIVE,
            'is_featured' => $index === 0 || $index === 3,
            'is_bestseller' => $index < 2,
            'is_new' => $index >= 3,
            'seo_title' => $nameAr.' | '.$category->name_ar,
            'seo_description' => "اشتري {$nameAr} بأفضل سعر مع شحن سريع.",
            'seo_keywords' => implode('، ', [$nameAr, $category->name_ar]),
        ])->save();

        if ($variantSpec) {
            $this->createVariants($product, $variantSpec, $price, $sale);
        }

        if ($product->images()->doesntExist()) {
            $this->createImages($product, $slug, $index, $images);
        }
    }

    private function createVariants(Product $product, array $spec, float $price, ?float $sale): void
    {
        if ($product->variants()->exists()) {
            return;
        }

        $colors = $spec['colors'] ?? [null];
        $sizes = $spec['sizes'] ?? [null];
        $n = 1;

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $variant = $product->variants()->create([
                    'sku' => $product->sku.'-V'.str_pad((string) $n++, 2, '0', STR_PAD_LEFT),
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock' => random_int(0, 4) === 0 ? random_int(1, 4) : random_int(6, 30),
                    'is_active' => true,
                ]);

                if ($color) {
                    $variant->options()->create(['name' => 'color', 'value' => $color, 'color_hex' => self::COLORS[$color] ?? '#888888']);
                }
                if ($size) {
                    $variant->options()->create(['name' => 'size', 'value' => (string) $size]);
                }
            }
        }
    }

    private function createImages(Product $product, string $slug, int $index, ImageService $images): void
    {
        $palette = [
            'electronics' => [[30, 41, 59], [99, 102, 241]], 'phones' => [[15, 23, 42], [56, 189, 248]],
            'computers' => [[17, 24, 39], [139, 92, 246]], 'men' => [[30, 58, 138], [147, 197, 253]],
            'women' => [[157, 23, 77], [251, 207, 232]], 'shoes' => [[67, 20, 7], [251, 146, 60]],
            'home' => [[6, 78, 59], [110, 231, 183]], 'beauty' => [[112, 26, 117], [245, 208, 254]],
            'sports' => [[154, 52, 18], [253, 186, 116]], 'toys' => [[161, 98, 7], [253, 224, 71]],
        ][$slug];

        $size = 700;
        $img = imagecreatetruecolor($size, $size);

        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            $color = imagecolorallocate(
                $img,
                (int) ($palette[0][0] + ($palette[1][0] - $palette[0][0]) * $t),
                (int) ($palette[0][1] + ($palette[1][1] - $palette[0][1]) * $t),
                (int) ($palette[0][2] + ($palette[1][2] - $palette[0][2]) * $t),
            );
            imageline($img, 0, $y, $size, $y, $color);
        }

        // Decorative shapes (different per product) so the catalog does not look uniform.
        mt_srand($index * 97 + strlen($slug));
        imagealphablending($img, true);
        for ($i = 0; $i < 4; $i++) {
            $shape = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(95, 112));
            imagefilledellipse($img, mt_rand(100, 600), mt_rand(100, 600), mt_rand(160, 420), mt_rand(160, 420), $shape);
        }
        $center = imagecolorallocatealpha($img, 255, 255, 255, 80);
        $this->roundedRect($img, 220, 220, 480, 480, $center);
        mt_srand();

        $paths = $images->storeGd($img, "products/{$product->id}", 900, 400);
        imagedestroy($img);

        $product->images()->create([
            'path' => $paths['path'], 'thumb_path' => $paths['thumb_path'],
            'alt' => $product->name_ar, 'sort_order' => 1, 'is_primary' => true,
        ]);
    }

    /** category slug => list of [name_ar, name_en, price, sale_price|null, variantSpec|null] */
    private function products(): array
    {
        $clothSizes = ['M', 'L', 'XL'];

        return [
            'phones' => [
                ['هاتف ذكي نوفا إكس 128 جيجا', 'Nova X Smartphone 128GB', 2499, 2199, ['colors' => ['أسود', 'أزرق', 'رمادي']]],
                ['هاتف ذكي برو ماكس 256 جيجا', 'Pro Max Smartphone 256GB', 3999, null, ['colors' => ['أسود', 'بيج']]],
                ['هاتف اقتصادي لايت 64 جيجا', 'Lite Smartphone 64GB', 799, 699, null],
                ['سماعات لاسلكية بلوتوث', 'Wireless Bluetooth Earbuds', 349, 279, null],
                ['شاحن سريع 65 واط', '65W Fast Charger', 129, null, null],
            ],
            'computers' => [
                ['حاسوب محمول 15.6 بوصة Core i7', '15.6" Core i7 Laptop', 3799, 3499, null],
                ['حاسوب محمول خفيف 14 بوصة', 'Ultralight 14" Laptop', 2899, null, null],
                ['جهاز لوحي 10.5 بوصة', '10.5" Tablet', 1299, 1099, ['colors' => ['رمادي', 'أزرق']]],
                ['لوحة مفاتيح ميكانيكية', 'Mechanical Keyboard', 249, null, null],
                ['ماوس لاسلكي مريح', 'Ergonomic Wireless Mouse', 89, 69, null],
            ],
            'electronics' => [
                ['شاشة ذكية 55 بوصة 4K', '55" 4K Smart TV', 2299, 1999, null],
                ['مكبر صوت بلوتوث محمول', 'Portable Bluetooth Speaker', 199, null, null],
                ['ساعة ذكية رياضية', 'Sport Smart Watch', 599, 499, ['colors' => ['أسود', 'وردي', 'أخضر']]],
                ['كاميرا مراقبة منزلية', 'Home Security Camera', 229, null, null],
                ['بنك طاقة 20000 مللي أمبير', '20000mAh Power Bank', 159, 129, null],
            ],
            'men' => [
                ['قميص قطني كلاسيكي', 'Classic Cotton Shirt', 129, 99, ['colors' => ['أبيض', 'أزرق'], 'sizes' => $clothSizes]],
                ['بنطال جينز مستقيم', 'Straight Fit Jeans', 189, null, ['colors' => ['أزرق'], 'sizes' => ['30', '32', '34']]],
                ['ثوب رجالي صيفي', 'Summer Thobe', 219, 189, ['colors' => ['أبيض', 'بيج'], 'sizes' => ['54', '56', '58']]],
                ['جاكيت جلد صناعي', 'Faux Leather Jacket', 349, null, ['colors' => ['أسود', 'بني'], 'sizes' => ['M', 'L']]],
                ['تيشيرت رياضي سريع الجفاف', 'Quick-Dry Sport T-Shirt', 79, 59, ['colors' => ['أسود', 'رمادي'], 'sizes' => $clothSizes]],
            ],
            'women' => [
                ['فستان سهرة أنيق', 'Elegant Evening Dress', 459, 389, ['colors' => ['أسود', 'أحمر'], 'sizes' => ['S', 'M', 'L']]],
                ['عباية عملية بقصة عصرية', 'Modern Practical Abaya', 299, null, ['colors' => ['أسود'], 'sizes' => ['52', '54', '56']]],
                ['حجاب قطني ناعم', 'Soft Cotton Hijab', 49, null, ['colors' => ['أسود', 'بيج', 'وردي']]],
                ['بلوزة كاجوال', 'Casual Blouse', 119, 89, ['colors' => ['أبيض', 'وردي'], 'sizes' => ['S', 'M', 'L']]],
                ['حقيبة يد جلدية', 'Leather Handbag', 279, null, null],
            ],
            'shoes' => [
                ['حذاء رياضي للجري', 'Running Shoes', 329, 279, ['colors' => ['أسود', 'أزرق'], 'sizes' => ['40', '42', '44']]],
                ['حذاء كلاسيكي رجالي', "Men's Classic Shoes", 299, null, ['colors' => ['أسود', 'بني'], 'sizes' => ['41', '43']]],
                ['صندل مريح', 'Comfort Sandals', 99, 79, ['colors' => ['بني'], 'sizes' => ['38', '40', '42']]],
                ['حذاء كاجوال', 'Casual Sneakers', 249, null, ['colors' => ['أبيض'], 'sizes' => ['40', '41', '42', '43']]],
                ['جزمة شتوية', 'Winter Boots', 359, 299, null],
            ],
            'home' => [
                ['طقم أواني طهي 12 قطعة', '12-Piece Cookware Set', 499, 429, null],
                ['خلاط كهربائي 1000 واط', '1000W Blender', 229, null, null],
                ['مكنسة كهربائية لاسلكية', 'Cordless Vacuum Cleaner', 699, 599, null],
                ['طقم أغطية سرير قطن', 'Cotton Bedding Set', 259, null, ['colors' => ['أبيض', 'رمادي'], 'sizes' => ['مفرد', 'مزدوج', 'كينج']]],
                ['قلاية هوائية 5 لتر', '5L Air Fryer', 349, 299, null],
            ],
            'beauty' => [
                ['عطر شرقي فاخر 100 مل', 'Luxury Oriental Perfume 100ml', 289, 249, null],
                ['كريم مرطب للبشرة', 'Moisturizing Face Cream', 69, null, null],
                ['مجفف شعر احترافي', 'Professional Hair Dryer', 179, 149, null],
                ['طقم عناية باللحية', 'Beard Care Kit', 129, null, null],
                ['ماسك الوجه بالطين', 'Clay Face Mask', 45, 35, null],
            ],
            'sports' => [
                ['سجادة يوغا مانعة للانزلاق', 'Non-Slip Yoga Mat', 99, 79, ['colors' => ['أزرق', 'وردي', 'أخضر']]],
                ['دمبل قابل للتعديل 20 كجم', '20kg Adjustable Dumbbell', 379, null, null],
                ['زجاجة مياه رياضية', 'Sports Water Bottle', 45, null, null],
                ['حقيبة رياضية واسعة', 'Spacious Gym Bag', 149, 119, null],
                ['حبل قفز احترافي', 'Pro Jump Rope', 39, null, null],
            ],
            'toys' => [
                ['مكعبات بناء 500 قطعة', '500-Piece Building Blocks', 139, 109, null],
                ['دمية ناعمة كبيرة', 'Large Plush Toy', 89, null, null],
                ['سيارة تحكم عن بعد', 'Remote Control Car', 199, 169, null],
                ['لعبة ألغاز تعليمية', 'Educational Puzzle Game', 59, null, null],
                ['صندوق هدايا مميز', 'Premium Gift Box', 120, null, null],
            ],
        ];
    }

    /** GD has no built-in rounded rectangle. */
    private function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $color, int $r = 40): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);

        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }
}
