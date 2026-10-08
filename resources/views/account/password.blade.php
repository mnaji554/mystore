<x-account-layout title="تغيير كلمة المرور">
    <form method="POST" action="{{ route('account.password.update') }}" class="card max-w-xl space-y-4 p-6">
        @csrf @method('PUT')
        <x-field label="كلمة المرور الحالية" name="current_password" type="password" dir="ltr" required autocomplete="current-password" />
        <x-field label="كلمة المرور الجديدة" name="password" type="password" dir="ltr" required autocomplete="new-password" hint="8 أحرف على الأقل مع أحرف وأرقام." />
        <x-field label="تأكيد كلمة المرور الجديدة" name="password_confirmation" type="password" dir="ltr" required autocomplete="new-password" />
        <button class="btn-primary">تغيير كلمة المرور</button>
    </form>
</x-account-layout>
