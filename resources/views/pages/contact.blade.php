<x-layouts.app title="تواصل معنا" description="راسلنا لأي استفسار أو مساعدة بخصوص طلباتك.">
    <x-breadcrumbs :items="['تواصل معنا' => null]" />
    <div class="container-x pb-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card p-6 lg:col-span-2">
                <h1 class="mb-1 text-2xl font-extrabold">تواصل معنا</h1>
                <p class="mb-5 text-sm text-slate-500">املأ النموذج وسنرد عليك في أقرب وقت.</p>
                <form method="POST" action="{{ route('contact.send') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <x-field label="الاسم" name="name" :value="old('name', auth()->user()?->name)" required />
                    <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" :value="old('email', auth()->user()?->email)" required />
                    <x-field label="رقم الجوال (اختياري)" name="phone" type="tel" dir="ltr" />
                    <x-field label="عنوان الرسالة" name="subject" required />
                    <div class="sm:col-span-2">
                        <label class="label" for="message">الرسالة</label>
                        <textarea id="message" name="message" rows="6" required maxlength="3000" class="input">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="sm:col-span-2"><button class="btn-primary !px-8">إرسال الرسالة</button></div>
                </form>
            </div>
            <aside class="card space-y-4 p-6 text-sm">
                <h2 class="font-extrabold">معلومات التواصل</h2>
                <p class="flex items-start gap-3"><x-icon name="phone" class="mt-0.5 text-brand-600" /> <span dir="ltr">{{ setting('contact_phone') }}</span></p>
                <p class="flex items-start gap-3"><x-icon name="mail" class="mt-0.5 text-brand-600" /> {{ setting('contact_email') }}</p>
                <p class="flex items-start gap-3"><x-icon name="map-pin" class="mt-0.5 text-brand-600" /> {{ setting('contact_address') }}</p>
            </aside>
        </div>
    </div>
</x-layouts.app>
