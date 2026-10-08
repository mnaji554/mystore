<x-account-layout title="الملف الشخصي">
    <form method="POST" action="{{ route('account.profile.update') }}" class="card max-w-xl space-y-4 p-6">
        @csrf @method('PUT')
        <x-field label="الاسم الكامل" name="name" :value="old('name', $user->name)" required />
        <x-field label="البريد الإلكتروني" name="email" type="email" dir="ltr" :value="old('email', $user->email)" required />
        <x-field label="رقم الجوال" name="phone" type="tel" dir="ltr" :value="old('phone', $user->phone)" />
        <button class="btn-primary">حفظ التغييرات</button>
    </form>
</x-account-layout>
