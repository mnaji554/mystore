<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Full three-level taxonomy: root → sub → inner.
 * Node format: 'الاسم العربي|English name' (optionally '|slug' to reuse an existing category).
 */
class CategoryTreeSeeder extends Seeder
{
    /** @var array<string, true> */
    private array $usedSlugs = [];

    public function run(): void
    {
        $order = 1;

        foreach ($this->tree() as [$root, $subs]) {
            $rootModel = $this->upsert($root, null, $order++);
            $subOrder = 1;

            foreach ($subs as [$sub, $inners]) {
                $subModel = $this->upsert($sub, $rootModel->id, $subOrder++);
                $innerOrder = 1;

                foreach ($inners as $inner) {
                    $this->upsert($inner, $subModel->id, $innerOrder++);
                }
            }
        }

        $this->command?->info('Categories: '.Category::count());
    }

    private function upsert(string $node, ?int $parentId, int $order): Category
    {
        [$ar, $en, $slug] = array_pad(explode('|', $node), 3, null);
        $slug = $slug ?: Str::slug($en);

        // identical English names under different parents must not collapse into one category
        if (isset($this->usedSlugs[$slug])) {
            $slug .= '-'.($parentId ?? 0);
        }
        $this->usedSlugs[$slug] = true;

        return Category::withTrashed()->updateOrCreate(['slug' => $slug], [
            'parent_id' => $parentId,
            'name_ar' => $ar,
            'name_en' => $en,
            'description' => "تسوّق أفضل منتجات {$ar} بأسعار منافسة وجودة مضمونة.",
            'sort_order' => $order,
            'is_active' => true,
            'deleted_at' => null,
            'seo_title' => $ar.' | أفضل الأسعار',
            'seo_description' => "اكتشف مجموعة {$ar} المتنوعة مع شحن سريع وإرجاع سهل.",
        ]);
    }

    /** @return list<array{0:string,1:list<array{0:string,1:list<string>}>}> */
    private function tree(): array
    {
        return [
            ['إلكترونيات|Electronics|electronics', [
                ['هواتف ذكية|Smartphones|phones', ['هواتف أندرويد|Android Phones|android-phones', 'هواتف آيفون|iPhones', 'هواتف اقتصادية|Budget Phones', 'هواتف قابلة للطي|Foldable Phones', 'إكسسوارات الهواتف|Phone Accessories|phone-accessories', 'شواحن وكابلات|Chargers & Cables', 'جرابات وحمايات|Cases & Screen Protectors']],
                ['حواسيب وأجهزة لوحية|Computers & Tablets|computers', ['حواسيب محمولة|Laptops|laptops', 'أجهزة لوحية|Tablets|tablets', 'حواسيب مكتبية|Desktop PCs', 'شاشات الكمبيوتر|Monitors', 'ملحقات الكمبيوتر|PC Accessories|pc-accessories', 'طابعات وماسحات|Printers & Scanners']],
                ['التلفزيونات والصوتيات|TV & Audio', ['تلفزيونات ذكية|Smart TVs', 'مكبرات الصوت|Speakers', 'سماعات الرأس|Headphones', 'أنظمة المسرح المنزلي|Home Theater', 'أجهزة الاستقبال|Receivers']],
                ['الكاميرات والتصوير|Cameras & Photography', ['كاميرات رقمية|Digital Cameras', 'كاميرات الحركة|Action Cameras', 'كاميرات المراقبة|Security Cameras', 'عدسات وملحقات|Lenses & Accessories', 'طائرات الدرون|Drones']],
                ['الأجهزة القابلة للارتداء|Wearables', ['ساعات ذكية|Smart Watches', 'أساور اللياقة|Fitness Bands', 'نظارات ذكية|Smart Glasses', 'سماعات لاسلكية|Wireless Earbuds']],
                ['الألعاب الإلكترونية|Gaming', ['أجهزة الألعاب|Consoles', 'أذرع التحكم|Controllers', 'ألعاب الفيديو|Video Games', 'كراسي الألعاب|Gaming Chairs', 'إكسسوارات الألعاب|Gaming Accessories']],
                ['الشبكات والتخزين|Networking & Storage', ['راوترات|Routers', 'أجهزة تقوية الإشارة|Range Extenders', 'ذاكرات فلاش|Flash Drives', 'أقراص صلبة|Hard Drives', 'بطاقات الذاكرة|Memory Cards']],
                ['المنزل الذكي|Smart Home', ['إضاءة ذكية|Smart Lighting', 'أقفال ذكية|Smart Locks', 'مساعدات صوتية|Voice Assistants', 'حساسات وأجهزة إنذار|Sensors & Alarms']],
            ]],
            ['أزياء رجالية|Men\'s Fashion|men', [
                ['ملابس رجالية|Men\'s Clothing|men-clothes', ['قمصان|Shirts|men-shirts', 'بناطيل|Pants|men-pants', 'تيشيرتات|T-Shirts', 'جاكيتات ومعاطف|Jackets & Coats', 'بدلات رسمية|Suits', 'جينز|Jeans', 'ملابس رياضية رجالية|Men\'s Activewear']],
                ['الثياب والملابس التقليدية|Traditional Wear', ['ثياب|Thobes', 'شماغ وغتر|Shemagh & Ghutra', 'مشالح|Bishts', 'عقل وطواقي|Agal & Caps']],
                ['ملابس داخلية ونوم|Underwear & Sleepwear', ['ملابس داخلية|Underwear', 'جوارب|Socks', 'بيجامات|Pajamas', 'أرواب|Robes']],
                ['إكسسوارات رجالية|Men\'s Accessories', ['ساعات رجالية|Men\'s Watches', 'أحزمة|Belts', 'محافظ|Wallets', 'ربطات عنق|Ties', 'نظارات شمسية رجالية|Men\'s Sunglasses', 'أزرار أكمام|Cufflinks']],
                ['حقائب رجالية|Men\'s Bags', ['حقائب ظهر|Backpacks', 'حقائب لابتوب|Laptop Bags', 'حقائب سفر|Travel Bags', 'حقائب جانبية|Messenger Bags']],
            ]],
            ['أزياء نسائية|Women\'s Fashion|women', [
                ['ملابس نسائية|Women\'s Clothing|women-clothes', ['فساتين|Dresses|dresses', 'عبايات|Abayas|abayas', 'بلوزات وقمصان|Tops & Blouses', 'بناطيل وجينز|Pants & Jeans', 'تنانير|Skirts', 'جاكيتات وكارديجان|Jackets & Cardigans', 'ملابس رياضية نسائية|Women\'s Activewear']],
                ['الحجاب والأوشحة|Hijabs & Scarves', ['حجاب|Hijabs', 'شالات وأوشحة|Shawls & Scarves', 'نقاب وبرقع|Niqab', 'إكسسوارات الحجاب|Hijab Accessories']],
                ['ملابس نوم وداخلية|Lingerie & Sleepwear', ['ملابس نوم|Nightwear', 'ملابس داخلية نسائية|Women\'s Underwear', 'أروب|Women\'s Robes', 'ملابس الأمومة|Maternity']],
                ['حقائب نسائية|Women\'s Bags', ['حقائب يد|Handbags', 'حقائب كتف|Shoulder Bags', 'حقائب سهرة|Clutches', 'حقائب ظهر نسائية|Women\'s Backpacks', 'محافظ نسائية|Women\'s Wallets']],
                ['مجوهرات وإكسسوارات|Jewelry & Accessories', ['عقود|Necklaces', 'أساور|Bracelets', 'خواتم|Rings', 'أقراط|Earrings', 'ساعات نسائية|Women\'s Watches', 'نظارات نسائية|Women\'s Sunglasses']],
            ]],
            ['أحذية|Shoes|shoes', [
                ['أحذية رجالية|Men\'s Shoes', ['أحذية رياضية|Men\'s Sneakers', 'أحذية رسمية|Formal Shoes', 'صنادل ونعال|Sandals & Slippers', 'جزم|Men\'s Boots', 'أحذية كاجوال|Men\'s Casual Shoes']],
                ['أحذية نسائية|Women\'s Shoes', ['كعب عالي|High Heels', 'أحذية مسطحة|Flats', 'أحذية رياضية نسائية|Women\'s Sneakers', 'صنادل نسائية|Women\'s Sandals', 'جزم نسائية|Women\'s Boots']],
                ['أحذية الأطفال|Kids\' Shoes', ['أحذية مدرسية|School Shoes', 'أحذية رياضية للأطفال|Kids\' Sneakers', 'صنادل أطفال|Kids\' Sandals', 'أحذية الرضع|Baby Shoes']],
                ['إكسسوارات الأحذية|Shoe Accessories', ['شرائط وأربطة|Laces', 'ملمعات ومنظفات|Shoe Care', 'حشوات داخلية|Insoles']],
            ]],
            ['المنزل والمطبخ|Home & Kitchen|home', [
                ['المطبخ|Kitchen|kitchen', ['أواني الطهي|Cookware|cookware', 'أجهزة صغيرة|Small Appliances|small-appliances', 'أدوات المائدة|Dinnerware', 'أدوات المطبخ|Kitchen Tools', 'حافظات الطعام|Food Storage', 'أدوات الخبز|Bakeware', 'أدوات القهوة والشاي|Coffee & Tea']],
                ['الأثاث|Furniture', ['كنب وأرائك|Sofas', 'أسرّة|Beds', 'خزائن وأدراج|Wardrobes & Drawers', 'طاولات|Tables', 'كراسي|Chairs', 'مكاتب|Desks', 'أرفف ومكتبات|Shelves']],
                ['المفروشات والديكور|Home Decor', ['سجاد وموكيت|Rugs & Carpets', 'ستائر|Curtains', 'وسائد ومفارش|Cushions & Throws', 'إطارات ولوحات|Frames & Art', 'مزهريات|Vases', 'شموع ومعطرات|Candles & Fragrances', 'ساعات حائط|Wall Clocks']],
                ['غرف النوم|Bedroom', ['أطقم أغطية السرير|Bedding Sets', 'مراتب|Mattresses', 'وسائد النوم|Sleeping Pillows', 'بطانيات ولحف|Blankets & Duvets']],
                ['الحمام|Bathroom', ['مناشف|Towels', 'ستائر الحمام|Shower Curtains', 'ملحقات الحمام|Bath Accessories', 'منظمات الحمام|Bathroom Storage']],
                ['الإضاءة|Lighting', ['ثريات|Chandeliers', 'مصابيح مكتبية|Desk Lamps', 'إضاءة خارجية|Outdoor Lighting', 'شرائط LED|LED Strips']],
                ['التنظيف والغسيل|Cleaning & Laundry', ['مكانس كهربائية|Vacuum Cleaners', 'منظفات|Detergents', 'أدوات التنظيف|Cleaning Tools', 'مستلزمات الكي|Ironing']],
                ['الأجهزة المنزلية الكبيرة|Large Appliances', ['ثلاجات|Refrigerators', 'غسالات|Washing Machines', 'أفران|Ovens', 'مكيفات|Air Conditioners', 'سخانات المياه|Water Heaters']],
            ]],
            ['الجمال والعناية|Beauty & Care|beauty', [
                ['العناية بالبشرة|Skincare', ['مرطبات|Moisturizers', 'غسول الوجه|Cleansers', 'سيرومات|Serums', 'واقي الشمس|Sunscreen', 'أقنعة الوجه|Face Masks', 'العناية بالعين|Eye Care']],
                ['المكياج|Makeup', ['مكياج الوجه|Face Makeup', 'مكياج العيون|Eye Makeup', 'أحمر الشفاه|Lipsticks', 'فرش وأدوات المكياج|Makeup Brushes', 'العناية بالأظافر|Nail Care']],
                ['العطور|Fragrances', ['عطور رجالية|Men\'s Perfumes', 'عطور نسائية|Women\'s Perfumes', 'عود ودهن عود|Oud & Oils', 'بخور ومعطرات|Incense', 'عطور الجسم|Body Mists']],
                ['العناية بالشعر|Hair Care', ['شامبو وبلسم|Shampoo & Conditioner', 'زيوت وعلاجات الشعر|Hair Oils & Treatments', 'مجففات ومكواة الشعر|Dryers & Stylers', 'صبغات الشعر|Hair Color', 'فرش ومشط|Brushes & Combs']],
                ['العناية الشخصية|Personal Care', ['العناية بالجسم|Body Care', 'العناية الفموية|Oral Care', 'مزيلات العرق|Deodorants', 'الحلاقة والإزالة|Shaving & Hair Removal', 'مستلزمات الاستحمام|Bath & Shower']],
                ['عناية الرجال|Men\'s Grooming', ['العناية باللحية|Beard Care', 'ماكينات الحلاقة|Shavers', 'عطور ومعطرات رجالية|Men\'s Colognes', 'عناية البشرة للرجال|Men\'s Skincare']],
                ['الأجهزة الصحية|Health Devices', ['أجهزة قياس الضغط|Blood Pressure Monitors', 'موازين|Scales', 'أجهزة التدليك|Massagers', 'موازين حرارة|Thermometers']],
            ]],
            ['الرياضة واللياقة|Sports & Fitness|sports', [
                ['معدات اللياقة|Fitness Equipment|fitness', ['اليوغا|Yoga|yoga', 'أوزان ودمبل|Weights & Dumbbells', 'أجهزة المشي والجري|Treadmills', 'دراجات تمارين|Exercise Bikes', 'حبال المقاومة|Resistance Bands', 'سجاد التمارين|Exercise Mats']],
                ['كرة القدم|Football', ['كرات القدم|Footballs', 'أحذية كرة القدم|Football Boots', 'قمصان الأندية|Club Jerseys', 'قفازات الحراسة|Goalkeeper Gloves', 'واقيات الساق|Shin Guards']],
                ['الرياضات المائية|Water Sports', ['ملابس السباحة|Swimwear', 'نظارات السباحة|Swim Goggles', 'معدات الغوص|Diving Gear', 'ألواح التزلج المائي|Paddleboards']],
                ['التخييم والرحلات|Camping & Hiking', ['خيام|Tents', 'أكياس النوم|Sleeping Bags', 'حقائب التخييم|Camping Backpacks', 'معدات الطبخ الخارجي|Camp Cooking', 'مصابيح الرحلات|Camping Lights']],
                ['الدراجات|Cycling', ['دراجات هوائية|Bicycles', 'خوذ الدراجات|Cycling Helmets', 'ملحقات الدراجات|Bike Accessories', 'ملابس الدراجات|Cycling Apparel']],
                ['رياضات القتال|Combat Sports', ['قفازات الملاكمة|Boxing Gloves', 'أكياس التدريب|Punching Bags', 'ملابس الفنون القتالية|Martial Arts Wear']],
                ['ملابس رياضية|Sportswear', ['تيشيرتات رياضية|Sport T-Shirts', 'شورتات|Shorts', 'ليقنز|Leggings', 'أحذية رياضية|Athletic Shoes']],
                ['مستلزمات رياضية|Sports Accessories', ['زجاجات المياه|Water Bottles', 'حقائب رياضية|Gym Bags', 'مكملات غذائية|Supplements', 'ساعات رياضية|Sport Watches']],
            ]],
            ['ألعاب وهدايا|Toys & Gifts|toys', [
                ['ألعاب الأطفال|Kids Toys', ['مكعبات البناء|Building Blocks', 'دمى|Dolls', 'سيارات ومركبات|Toy Vehicles', 'ألعاب تعليمية|Educational Toys', 'ألعاب الألغاز|Puzzles', 'ألعاب خارجية|Outdoor Toys']],
                ['ألعاب الطاولة|Board Games', ['ألعاب عائلية|Family Games', 'ألعاب الورق|Card Games', 'ألعاب الذكاء|Strategy Games']],
                ['الهدايا|Gifts', ['صناديق الهدايا|Gift Boxes', 'بطاقات التهنئة|Greeting Cards', 'هدايا المناسبات|Occasion Gifts', 'هدايا شخصية|Personalized Gifts', 'باقات الورد|Flowers']],
                ['مستلزمات الحفلات|Party Supplies', ['زينة وبالونات|Decorations & Balloons', 'أدوات المائدة للحفلات|Party Tableware', 'أزياء تنكرية|Costumes']],
                ['الألعاب التحكم عن بعد|RC Toys', ['سيارات تحكم|RC Cars', 'طائرات تحكم|RC Aircraft', 'روبوتات|Robots']],
            ]],
            ['الأطفال والرضع|Baby & Kids|baby-kids', [
                ['ملابس الأطفال|Kids Clothing', ['ملابس البنات|Girls\' Clothing', 'ملابس الأولاد|Boys\' Clothing', 'ملابس حديثي الولادة|Newborn Clothing', 'أزياء المدرسة|School Uniforms', 'ملابس نوم الأطفال|Kids\' Sleepwear']],
                ['مستلزمات الرضع|Baby Essentials', ['حفاضات|Diapers', 'مناديل مبللة|Baby Wipes', 'الرضاعة|Feeding', 'مستلزمات الاستحمام للرضع|Baby Bath', 'العناية بالرضيع|Baby Care']],
                ['عربات ومقاعد|Strollers & Car Seats', ['عربات الأطفال|Strollers', 'مقاعد السيارة|Car Seats', 'حقائب الأمهات|Diaper Bags', 'حاملات الأطفال|Baby Carriers']],
                ['غرف الأطفال|Kids Room', ['أسرّة الأطفال|Kids Beds', 'أثاث الأطفال|Kids Furniture', 'ديكور الأطفال|Kids Decor', 'أفرشة الأطفال|Kids Bedding']],
                ['مستلزمات المدرسة|School Supplies', ['حقائب مدرسية|School Bags', 'دفاتر وكراريس|Notebooks', 'أقلام وأدوات|Stationery', 'علب الغداء|Lunch Boxes']],
            ]],
            ['السيارات والدراجات النارية|Automotive|automotive', [
                ['إكسسوارات داخلية|Interior Accessories', ['أغطية المقاعد|Seat Covers', 'حمالات الجوال|Phone Holders', 'معطرات السيارات|Car Fresheners', 'سجاد السيارة|Floor Mats', 'منظمات السيارة|Car Organizers']],
                ['إكسسوارات خارجية|Exterior Accessories', ['أغطية السيارة|Car Covers', 'ملمعات وتنظيف|Car Care', 'إضاءة السيارة|Car Lights', 'ديكورات خارجية|Exterior Styling']],
                ['الإلكترونيات للسيارات|Car Electronics', ['كاميرات القيادة|Dash Cams', 'أجهزة GPS|GPS Devices', 'شواحن السيارة|Car Chargers', 'أنظمة الصوت|Car Audio']],
                ['قطع الغيار والزيوت|Parts & Oils', ['زيوت المحرك|Engine Oil', 'بطاريات|Batteries', 'إطارات|Tires', 'فلاتر|Filters', 'تيل الفرامل|Brake Pads']],
                ['أدوات وصيانة|Tools & Maintenance', ['كوابل الشحن|Jumper Cables', 'نفاخات الإطارات|Tire Inflators', 'عدد يدوية|Hand Tools', 'مستلزمات الطوارئ|Emergency Kits']],
                ['الدراجات النارية|Motorcycles', ['خوذ|Motorcycle Helmets', 'ملابس الراكبين|Riding Gear', 'قطع الدراجات|Motorcycle Parts']],
            ]],
            ['الأدوات والبناء|Tools & Home Improvement|tools', [
                ['العدد الكهربائية|Power Tools', ['مثاقب|Drills', 'مناشير|Saws', 'صنفرة وجلخ|Sanders & Grinders', 'أدوات اللحام|Welding']],
                ['العدد اليدوية|Hand Tools', ['مفكات|Screwdrivers', 'مطارق|Hammers', 'مفاتيح ربط|Wrenches', 'أطقم عدد|Tool Sets', 'أدوات القياس|Measuring Tools']],
                ['السباكة والكهرباء|Plumbing & Electrical', ['خلاطات وحنفيات|Faucets', 'مواسير وتوصيلات|Pipes & Fittings', 'مفاتيح وأفياش|Switches & Sockets', 'كابلات وأسلاك|Cables & Wires']],
                ['الدهانات والتشطيبات|Paint & Finishing', ['دهانات|Paints', 'فرش ورولات|Brushes & Rollers', 'ورق جدران|Wallpaper', 'أرضيات|Flooring']],
                ['الأمان والسلامة|Safety', ['خوذ وقفازات|Helmets & Gloves', 'أحذية السلامة|Safety Shoes', 'طفايات الحريق|Fire Extinguishers', 'أقفال وأمان|Locks & Security']],
            ]],
            ['الحدائق والخارجيات|Garden & Outdoor|garden', [
                ['أثاث الحدائق|Patio Furniture', ['طقم جلسات خارجية|Outdoor Sets', 'مظلات|Umbrellas & Canopies', 'أراجيح|Swings', 'كراسي استرخاء|Lounge Chairs']],
                ['النباتات والبذور|Plants & Seeds', ['نباتات داخلية|Indoor Plants', 'بذور|Seeds', 'أحواض وأصص|Pots & Planters', 'أسمدة وتربة|Soil & Fertilizer']],
                ['أدوات الحدائق|Garden Tools', ['مقصات|Pruners', 'خراطيم ورشاشات|Hoses & Sprinklers', 'جزازات العشب|Lawn Mowers', 'أدوات الزراعة|Planting Tools']],
                ['الشواء والتخييم|BBQ & Grilling', ['شوايات|Grills', 'فحم وأخشاب|Charcoal & Wood', 'أدوات الشواء|Grill Tools', 'ثلاجات متنقلة|Coolers']],
                ['المسابح والترفيه|Pools & Leisure', ['مسابح قابلة للنفخ|Inflatable Pools', 'ألعاب الماء|Pool Toys', 'مستلزمات المسابح|Pool Supplies']],
            ]],
            ['الأطعمة والمشروبات|Food & Beverages|food', [
                ['القهوة والشاي|Coffee & Tea', ['قهوة عربية|Arabic Coffee', 'قهوة مختصة|Specialty Coffee', 'شاي|Tea', 'كبسولات القهوة|Coffee Capsules', 'مشروبات ساخنة|Hot Drinks']],
                ['الحلويات والتمور|Sweets & Dates', ['تمور|Dates', 'شوكولاتة|Chocolate', 'حلويات عربية|Arabic Sweets', 'عسل|Honey', 'مربى ومحليات|Jams & Spreads']],
                ['المواد الغذائية|Pantry', ['أرز وحبوب|Rice & Grains', 'معلبات|Canned Food', 'زيوت وسمن|Oils & Ghee', 'بهارات وتوابل|Spices', 'مكرونة وصلصات|Pasta & Sauces']],
                ['المكسرات والوجبات الخفيفة|Nuts & Snacks', ['مكسرات|Nuts', 'رقائق وبسكويت|Chips & Biscuits', 'فواكه مجففة|Dried Fruits', 'ألواح الطاقة|Energy Bars']],
                ['المشروبات|Beverages', ['مياه|Water', 'عصائر|Juices', 'مشروبات غازية|Soft Drinks', 'مشروبات الطاقة|Energy Drinks']],
                ['الأطعمة الصحية|Health Foods', ['أغذية عضوية|Organic', 'خالي من الغلوتين|Gluten Free', 'بروتين ومكملات|Protein', 'بذور وحبوب|Seeds & Superfoods']],
            ]],
            ['الصحة والمكملات|Health & Supplements|health', [
                ['الفيتامينات والمكملات|Vitamins', ['فيتامينات متعددة|Multivitamins', 'أوميغا 3|Omega 3', 'معادن|Minerals', 'مكملات الأطفال|Kids Supplements']],
                ['العناية الطبية|Medical Care', ['أجهزة السكر|Glucose Monitors', 'الإسعافات الأولية|First Aid', 'الكمامات والمعقمات|Masks & Sanitizers', 'دعامات وجبائر|Braces & Supports']],
                ['الأعشاب والطب البديل|Herbal', ['أعشاب طبيعية|Natural Herbs', 'زيوت طبيعية|Natural Oils', 'مستخلصات|Extracts']],
                ['العناية بالنظر|Vision Care', ['نظارات طبية|Eyeglasses', 'عدسات لاصقة|Contact Lenses', 'محاليل العدسات|Lens Solutions']],
            ]],
            ['الكتب والمكتبة|Books & Stationery|books', [
                ['الكتب|Books', ['كتب عربية|Arabic Books', 'كتب إنجليزية|English Books', 'كتب الأطفال|Children\'s Books', 'روايات|Novels', 'كتب دينية|Religious Books', 'كتب تعليمية|Educational Books']],
                ['القرطاسية|Stationery', ['أقلام|Pens', 'دفاتر|Notebooks', 'أدوات مكتبية|Office Supplies', 'ملفات وحافظات|Files & Folders', 'ألوان وفنون|Art Supplies']],
                ['مستلزمات المكتب|Office Supplies', ['كراسي مكاتب|Office Chairs', 'منظمات المكتب|Desk Organizers', 'آلات حاسبة|Calculators', 'سبورات|Whiteboards']],
                ['الآلات الموسيقية|Musical Instruments', ['جيتارات|Guitars', 'كيبورد وبيانو|Keyboards & Pianos', 'آلات إيقاعية|Percussion', 'عود وآلات شرقية|Oud & Oriental']],
            ]],
            ['الحيوانات الأليفة|Pet Supplies|pets', [
                ['القطط|Cats', ['طعام القطط|Cat Food', 'رمل القطط|Cat Litter', 'ألعاب القطط|Cat Toys', 'أسرّة وبيوت القطط|Cat Beds', 'خدّاشات|Scratchers']],
                ['الكلاب|Dogs', ['طعام الكلاب|Dog Food', 'أطواق وأربطة|Collars & Leashes', 'ألعاب الكلاب|Dog Toys', 'أسرّة الكلاب|Dog Beds', 'ملابس الكلاب|Dog Clothing']],
                ['الطيور والأسماك|Birds & Fish', ['أقفاص الطيور|Bird Cages', 'طعام الطيور|Bird Food', 'أحواض الأسماك|Aquariums', 'طعام الأسماك|Fish Food']],
                ['العناية والصحة|Pet Care', ['شامبو الحيوانات|Pet Shampoo', 'فرش ومقصات|Grooming Tools', 'مكملات وعلاجات|Pet Health']],
            ]],
            ['الحقائب والسفر|Luggage & Travel|travel', [
                ['حقائب السفر|Suitcases', ['حقائب كبيرة|Large Suitcases', 'حقائب متوسطة|Medium Suitcases', 'حقائب اليد للطائرة|Carry-on', 'أطقم حقائب|Luggage Sets']],
                ['إكسسوارات السفر|Travel Accessories', ['وسائد السفر|Travel Pillows', 'منظمات الحقائب|Packing Cubes', 'أقفال الحقائب|Luggage Locks', 'محولات كهرباء|Travel Adapters', 'محافظ جواز السفر|Passport Holders']],
                ['حقائب الظهر|Backpacks', ['حقائب الجامعة|College Backpacks', 'حقائب التسلق|Hiking Packs', 'حقائب اللابتوب|Laptop Backpacks']],
            ]],
            ['العناية بالأسرة والمناسبات|Occasions|occasions', [
                ['رمضان والعيد|Ramadan & Eid', ['زينة رمضان|Ramadan Decor', 'هدايا العيد|Eid Gifts', 'مستلزمات الإفطار|Iftar Essentials', 'فوانيس|Lanterns']],
                ['الأعراس والزواج|Weddings', ['توزيعات الأعراس|Wedding Favors', 'ديكور الأعراس|Wedding Decor', 'هدايا العروس|Bridal Gifts']],
                ['المولود الجديد|Newborn', ['هدايا المواليد|Newborn Gifts', 'توزيعات المواليد|Baby Shower Favors', 'ألبومات الذكريات|Memory Books']],
                ['العمرة والحج|Hajj & Umrah', ['إحرام|Ihram', 'سجاد صلاة|Prayer Mats', 'مسابح ومصاحف|Prayer Beads & Quran', 'مستلزمات الحج|Hajj Essentials']],
            ]],
        ];
    }
}
