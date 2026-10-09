# متجري — متجر إلكتروني متكامل بـ Laravel

متجر إلكتروني عربي (RTL) كامل مبني بالكامل داخل Laravel باستخدام **Blade + Livewire + Alpine.js + Tailwind CSS**، بدون React أو Vue.

| التقنية | الإصدار المستخدم |
|---|---|
| Laravel | 13 |
| PHP | 8.4 (يتطلب 8.3+) |
| Livewire | 4 |
| Tailwind CSS / Vite | 4 / 8 |
| قاعدة البيانات | MySQL 8+ / MariaDB 10.4+ (مُختبر على MariaDB الخاصة بـ XAMPP) |
| PDF | mPDF (يدعم العربية وRTL) |
| الدفع | الدفع عند الاستلام + Stripe (قابل للتوسع) |

## المزايا

**الواجهة:** الرئيسية، المنتجات، التصنيفات المتداخلة، تفاصيل المنتج (ألوان/مقاسات/Variants)، بحث متقدم + فلاتر + ترتيب، الأكثر مبيعاً، الجديد، المخفض، المفضلة، سلة (زائر + مستخدم مع دمج تلقائي عند الدخول)، Checkout من 6 خطوات، تتبع الطلب، الكوبونات، التقييمات، حساب العميل (ملف شخصي، كلمة المرور، العناوين، الطلبات، التقييمات)، صفحات ثابتة، تواصل معنا، وضع داكن، Skeleton Loading، Toasts، Modals.

**لوحة التحكم:** إحصائيات ومخططات (Chart.js)، المنتجات (CRUD، رفع صور متعددة، ترتيب، صورة رئيسية، Variants، مخزون، بحث/فلترة/ترتيب، حذف جماعي)، التصنيفات، الطلبات (تغيير الحالة + سجل + تتبع + فاتورة PDF + إشعار)، العملاء، الكوبونات، الشحن، التقييمات (موافقة/رفض)، الإعدادات، الرسائل، الإشعارات.

**الأدوار:** Super Admin · Admin · Manager · Courier · Customer (Gates + Policies، وكل أكشن Livewire يُفوَّض مرة أخرى على الخادم).

**المعمارية:** `Services` (Cart / Order / Payment / Coupon / Shipping / Invoice / Image / Report / Review) + `Repositories` + `Events/Listeners` + `Jobs` + `Notifications` + `Mail` + `Policies` + `Form Requests`. كل الأسعار تُحسب **على الخادم** من قاعدة البيانات ولا يُثق بأي قيمة قادمة من المتصفح.

**API:** `/api/v1` (Sanctum) — Auth, Products, Categories, Cart, Wishlist, Orders, Reviews, Profile.

---

## 1) المتطلبات

- macOS مع **XAMPP** (للـ MySQL/MariaDB وphpMyAdmin)
- **PHP 8.4** (أو 8.3+) مع الإضافات: `pdo_mysql, mbstring, gd (مع WebP), intl, fileinfo, zip, exif`
- **Composer 2**
- **Node.js 20+** و npm

### تثبيت PHP / Composer / Node (macOS عبر Homebrew)

```bash
brew install php@8.4 composer node
php -v && composer -V && node -v
```

> حزمة XAMPP قد تأتي بنسخة PHP أقدم؛ استخدم PHP من Homebrew لتشغيل أوامر `php artisan` (الأوامر أدناه تستخدم أول `php` في الـ PATH).

## 2) إعداد قاعدة البيانات على MySQL الخاص بـ XAMPP

1. افتح **XAMPP Control Panel** (أو `sudo /Applications/XAMPP/xamppfiles/xampp start`) وشغّل **MySQL**.
2. أنشئ قاعدة البيانات (phpMyAdmin على `http://localhost/phpmyadmin` أو من الطرفية):

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -uroot -e "CREATE DATABASE mystore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

3. مستخدم XAMPP الافتراضي هو `root` بدون كلمة مرور على المنفذ `3306`. للإنتاج أنشئ مستخدماً مخصصاً بصلاحيات محدودة على قاعدة `mystore` فقط.

## 3) التثبيت والتشغيل

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/mystore

composer install
npm install

cp .env.example .env          # إن لم يكن .env موجوداً
php artisan key:generate
```

عدّل `.env`:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mystore
DB_USERNAME=root
DB_PASSWORD=
```

ثم:

```bash
php artisan migrate            # أو: php artisan migrate:fresh --seed
php artisan db:seed            # بيانات تجريبية كاملة
php artisan storage:link       # ربط storage/app/public بـ public/storage (لصور المنتجات)
npm run build                  # بناء الواجهة (Tailwind + Alpine + Chart.js)
php artisan serve              # http://localhost:8000
```

للتطوير مع إعادة التحميل التلقائي: `npm run dev` في طرفية أخرى بدلاً من `npm run build`.

> **ملاحظة:** يمكن أيضاً تشغيل المشروع عبر Apache الخاص بـ XAMPP على `http://localhost/mystore/public` (اضبط `APP_URL` وفقاً لذلك).

### ما يضيفه `db:seed`

Super Admin وAdmin وManager وعميل تجريبي و12 عميلاً · 10 تصنيفات (بينها تصنيفات فرعية) · 50 منتجاً بصور مولّدة وVariants (ألوان/مقاسات) · 31 طلباً بحالات مختلفة · تقييمات · 5 كوبونات · 3 طرق شحن وشركتا شحن.

### الحسابات التجريبية (كلمة المرور للجميع `Password123`)

| الدور | البريد |
|---|---|
| Super Admin | `super@mystore.test` |
| Admin | `admin@mystore.test` |
| Manager | `manager@mystore.test` |
| عميل | `customer@mystore.test` |

لوحة التحكم: `http://localhost:8000/admin` — **غيّر كلمات المرور هذه قبل أي نشر حقيقي.**

الكوبونات التجريبية: `WELCOME10`، `SAVE50` (حد أدنى 300)، `VIP20`، `SPORT15` (تصنيف الرياضة فقط)، `EXPIRED5` (منتهي).

| الدور | الصلاحيات |
|---|---|
| Super Admin | كل شيء بما فيها تعيين الأدوار |
| Admin | كل شيء عدا إدارة الأدوار |
| Manager | المنتجات، التصنيفات، الطلبات، التقييمات (بدون حذف) |
| Courier | الطلبات المسندة إليه وتحديث مراحل التوصيل |
| Customer | حسابه فقط |

## 4) الطابور (Queue) والجدولة (Scheduler)

الإشعارات والبريد وتوليد الفواتير تعمل في الخلفية عبر طابور قاعدة البيانات.

```bash
php artisan queue:work            # شغّله في طرفية منفصلة (الإشعارات/البريد)
php artisan queue:failed          # المهام الفاشلة (جدول failed_jobs)
php artisan queue:retry all

php artisan schedule:work         # تطوير: يشغّل المجدول كل دقيقة
# إنتاج: أضف إلى cron
# * * * * * cd /path/to/mystore && php artisan schedule:run >> /dev/null 2>&1
```

المهام المجدولة: إلغاء الطلبات الإلكترونية غير المدفوعة بعد 24 ساعة وإرجاع المخزون (كل ساعة)، تقرير المخزون المنخفض (يومياً 08:00)، حذف سلال الزوار المهجورة (أسبوعياً)، تنظيف المهام الفاشلة وتوكنات Sanctum المنتهية.

> للتجربة دون طابور مستقل اجعل `QUEUE_CONNECTION=sync` في `.env`.

## 5) إعداد الدفع (Stripe)

لا تُخزَّن أي بيانات بطاقات في قاعدة البيانات؛ الدفع يتم عبر **Stripe Checkout** المستضاف.

```dotenv
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

- عند تعبئة المفتاحين تظهر طريقة «بطاقة ائتمانية» تلقائياً في Checkout (وتختفي بدونهما).
- Webhook: أضف في لوحة Stripe نقطة `https://your-domain.com/webhooks/stripe` للأحداث `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `checkout.session.expired`. محلياً: `stripe listen --forward-to localhost:8000/webhooks/stripe`.
- التحقق من التوقيع إلزامي، وتأكيد الدفع **idempotent** (آمن عند تكرار الإشعار).
- استخدم مفاتيح الاختبار أولاً، واجعل `APP_URL` عنواناً عاماً عبر HTTPS عند تجربة Webhook في بيئة منشورة. لا تضع المفاتيح السرية في Git.

## 6) إعداد التوصيل والمناديب

- من **لوحة التحكم ← الشحن ← طرق الشحن** أضف طريقة التوصيل وحدد رسومها، مدة التوصيل، وحد الشحن المجاني الاختياري. تُحسب الرسوم على الخادم وتُحفظ مع الطلب، فلا تتغير رسوم الطلبات القديمة عند تعديل الطريقة.
- من تبويب **المناديب** أنشئ حساباً لكل مندوب أو أوقف حسابه. حساب المندوب لا يملك صلاحية لوحة الإدارة، ويُحوَّل بعد تسجيل الدخول إلى صفحة **طلبات التوصيل**.
- يمكن حفظ رقم الهوية (10 أرقام)، ولوحة المركبة، ورقم رخصة القيادة، مع صور الهوية واستمارة المركبة والرخصة. الأرقام مشفّرة في قاعدة البيانات، والوثائق محفوظة على قرص خاص ولا يمكن تنزيلها إلا لمستخدم يملك صلاحية إدارة الشحن.
- بعد بدء تجهيز الطلب، افتح تفاصيله من **الطلبات** وأسند مندوباً نشطاً. يستطيع المندوب رؤية طلباته المسندة فقط، وتحديث الحالة من «قيد التجهيز» إلى «تم الشحن» ثم «تم التسليم».
- عند تسليم طلب الدفع عند الاستلام يُسجَّل الدفع تلقائياً، كما في تدفق الطلبات الحالي.

**إضافة بوابة جديدة** (Moyasar / Tap / PayTabs / Apple Pay / STC Pay): أنشئ كلاساً يطبّق `App\Contracts\PaymentGateway` (`key, label, description, isAvailable, initiate, refund`) وسجّله في `config/payments.php`. لا حاجة لتعديل أي كود آخر.

## 7) إعداد البريد

الافتراضي `MAIL_MAILER=log` (الرسائل تُكتب في `storage/logs/laravel.log`). للإرسال الفعلي:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="no-reply@your-domain.com"
STORE_ADMIN_EMAIL=orders@your-domain.com   # يستلم إشعارات الطلبات الجديدة والمخزون المنخفض
```

قوالب البريد (عربية RTL) في `resources/views/emails`: ترحيب، إنشاء الطلب، تأكيد، تجهيز، شحن، تسليم، إلغاء، استرجاع، إعادة تعيين كلمة المرور. لإضافة SMS/WhatsApp أضف قناة في `config/store.php → extra_notification_channels`.

## 8) الاختبارات والفحص

```bash
php artisan test                  # (Auth, Products, Cart, Coupon, Checkout, Orders, Payments, Couriers, Permissions, Reviews, Admin, API)
vendor/bin/pint                   # تنسيق الكود
php artisan route:list
```

تعمل الاختبارات على SQLite في الذاكرة ولا تمس قاعدة بياناتك.

## 9) الإنتاج

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

قائمة التحقق:

- `APP_ENV=production`, `APP_DEBUG=false`, و`APP_URL` بـ **https**.
- `SESSION_SECURE_COOKIE=true` و`SESSION_SAME_SITE=lax`.
- مستخدم قاعدة بيانات مخصص وكلمة مرور قوية، وغيّر كلمات مرور الحسابات التجريبية أو احذفها.
- شغّل `queue:work` تحت Supervisor/systemd، وcron للمجدول.
- اجعل مجلد الويب `public/` فقط، وصلاحيات الكتابة لـ `storage/` و`bootstrap/cache/`.
- ضع `.env` خارج المستودع (الملف مُدرج في `.gitignore`) ولا تضع مفاتيح حقيقية في Git.
- استخدم Redis للكاش والطابور عند ارتفاع الحمل (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`).
- للانتقال إلى Meilisearch/Algolia: اربط تطبيقاً جديداً للواجهة `App\Contracts\ProductSearch` في `AppServiceProvider`.

## 10) الأمان — ما هو مطبّق

CSRF · حماية XSS (تهريب Blade + تنظيف المدخلات) · Eloquent/Query Builder (بدون SQL خام من مدخلات) · حماية Mass Assignment (`role_id` و`is_active` غير قابلين للتعبئة الجماعية) · Policies/Gates على المسارات **وعلى كل أكشن Livewire** · Rate Limiting (دخول، تسجيل، API، Checkout، نماذج) · تشفير كلمات المرور (bcrypt) · Form Requests · رفع آمن للصور (إعادة ترميز إلى WebP، التحقق من MIME الحقيقي، أسماء عشوائية) · Honeypot في نموذج التواصل · ترويسات أمان · حماية مسارات الإدارة · عدم كشف وجود البريد عند استعادة كلمة المرور · تتبع الطلب للزوار برقم الطلب + البريد.

## 11) هيكل المشروع

```
app/
  Contracts/      PaymentGateway, ProductSearch
  Enums/          OrderStatus, PaymentStatus
  Events/ Listeners/ Jobs/ Notifications/ Mail/
  Http/Controllers (+ Api/V1), Requests, Resources, Middleware
  Livewire/       الواجهة + Account/ + Admin/
  Models/ Policies/ Repositories/
  Services/       Cart, Order, Payment(+Payments/), Coupon, Shipping, Invoice, Image, Report, Review, Wishlist
database/         migrations, factories, seeders
resources/views/  components/ (layouts, product-card ...) livewire/ emails/ invoices/ pages/
tests/Feature/
```
