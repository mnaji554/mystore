<x-layouts.app title="تسجيل الدخول" :noindex="true">
    <div class="container-x grid min-h-[60vh] place-items-center py-12">
        <div class="card w-full max-w-md animate-slide-up p-7">
            <h1 class="text-2xl font-extrabold">تسجيل الدخول</h1>
            <p class="mt-1 text-sm text-slate-500">مرحباً بعودتك! أدخل بياناتك للمتابعة.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" required autofocus autocomplete="username" />
                <x-field label="كلمة المرور" name="password" type="password" dir="ltr" required autocomplete="current-password" />
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="rounded text-brand-600"> تذكرني</label>
                    <a href="{{ route('password.request') }}" class="font-bold text-brand-600 hover:underline">نسيت كلمة المرور؟</a>
                </div>
                <button class="btn-primary w-full !py-3">دخول</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">ليس لديك حساب؟ <a href="{{ route('register') }}" class="font-extrabold text-brand-600">إنشاء حساب</a></p>
        </div>
    </div>
</x-layouts.app>
