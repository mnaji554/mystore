<x-layouts.app title="استعادة كلمة المرور" :noindex="true">
    <div class="container-x grid min-h-[60vh] place-items-center py-12">
        <div class="card w-full max-w-md animate-slide-up p-7">
            <h1 class="text-2xl font-extrabold">نسيت كلمة المرور؟</h1>
            <p class="mt-1 text-sm text-slate-500">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة التعيين.</p>
            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf
                <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" required autofocus />
                <button class="btn-primary w-full !py-3">إرسال الرابط</button>
            </form>
            <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-bold text-brand-600">العودة لتسجيل الدخول</a></p>
        </div>
    </div>
</x-layouts.app>
