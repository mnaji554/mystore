<x-layouts.app title="من نحن" :description="'تعرف على '.setting('store_name').' ورسالتنا وقيمنا.'">
    <x-breadcrumbs :items="['من نحن' => null]" />
    <div class="container-x max-w-4xl pb-8">
        <article class="card prose-store p-6 sm:p-10">
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white">من نحن</h1>
            <p>{{ setting('store_name') }} متجر إلكتروني عربي يهدف إلى توفير تجربة تسوق سهلة وآمنة، بمنتجات مختارة بعناية وأسعار منافسة وخدمة عملاء تهتم بكل التفاصيل.</p>
            <h2>رسالتنا</h2>
            <p>أن نكون وجهتك الأولى للتسوق عبر الإنترنت، من خلال جودة المنتجات، سرعة التوصيل، وسهولة الاسترجاع والاستبدال.</p>
            <h2>لماذا نحن؟</h2>
            <ul class="list-disc space-y-2 ps-6">
                <li>منتجات أصلية بضمان الجودة.</li>
                <li>شحن سريع لجميع المدن مع إمكانية تتبع الطلب.</li>
                <li>طرق دفع آمنة تشمل الدفع عند الاستلام والبطاقات الائتمانية.</li>
                <li>دعم عملاء يرد على استفساراتكم بسرعة.</li>
            </ul>
            <h2>تواصل معنا</h2>
            <p>البريد: {{ setting('contact_email') }} · الهاتف: <span dir="ltr">{{ setting('contact_phone') }}</span> · العنوان: {{ setting('contact_address') }}</p>
        </article>
    </div>
</x-layouts.app>
