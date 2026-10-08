<x-layouts.app title="إنشاء حساب" :noindex="true">
    <div class="container-x grid min-h-[60vh] place-items-center py-12">
        <div class="card w-full max-w-md animate-slide-up p-7">
            <h1 class="text-2xl font-extrabold">إنشاء حساب جديد</h1>
            <p class="mt-1 text-sm text-slate-500">سجّل لتتبع طلباتك وحفظ مفضلاتك.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf
                <x-field label="الاسم الكامل" name="name" required autocomplete="name" />
                <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" required autocomplete="email" />
                <x-field label="رقم الجوال (اختياري)" name="phone" type="tel" dir="ltr" autocomplete="tel" />
                <x-field label="كلمة المرور" name="password" type="password" dir="ltr" required autocomplete="new-password" hint="8 أحرف على الأقل مع أحرف وأرقام." />
                <x-field label="تأكيد كلمة المرور" name="password_confirmation" type="password" dir="ltr" required autocomplete="new-password" />
                <button class="btn-primary w-full !py-3">إنشاء الحساب</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">لديك حساب؟ <a href="{{ route('login') }}" class="font-extrabold text-brand-600">تسجيل الدخول</a></p>
        </div>
    </div>
</x-layouts.app>
