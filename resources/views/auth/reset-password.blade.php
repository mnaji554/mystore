<x-layouts.app title="إعادة تعيين كلمة المرور" :noindex="true">
    <div class="container-x grid min-h-[60vh] place-items-center py-12">
        <div class="card w-full max-w-md animate-slide-up p-7">
            <h1 class="text-2xl font-extrabold">كلمة مرور جديدة</h1>
            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" :value="$email" required />
                <x-field label="كلمة المرور الجديدة" name="password" type="password" dir="ltr" required autocomplete="new-password" hint="8 أحرف على الأقل مع أحرف وأرقام." />
                <x-field label="تأكيد كلمة المرور" name="password_confirmation" type="password" dir="ltr" required autocomplete="new-password" />
                <button class="btn-primary w-full !py-3">حفظ كلمة المرور</button>
            </form>
        </div>
    </div>
</x-layouts.app>
