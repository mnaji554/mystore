<form wire:submit="save" class="mx-auto max-w-4xl space-y-5">
    <section class="card space-y-4 p-5">
        <h2 class="font-extrabold">بيانات المتجر</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="اسم المتجر *" model="form.store_name" />
            <x-field label="الشعار النصي" model="form.store_tagline" />
            <x-field label="الرقم الضريبي" model="form.vat_number" dir="ltr" />
        </div>
        <div><label class="label" for="meta">وصف المتجر (SEO)</label><textarea id="meta" wire:model="form.meta_description" rows="2" class="input"></textarea></div>
        <div>
            <p class="label">الشعار (يظهر في الموقع والفواتير)</p>
            <div class="flex items-center gap-4">
                @if($logo && $logo->isPreviewable())<img src="{{ $logo->temporaryUrl() }}" class="h-14 rounded-lg" alt="">
                @elseif($url = \App\Models\Setting::logoUrl())<img src="{{ $url }}" class="h-14 rounded-lg" alt=""><button type="button" wire:click="removeLogo" class="btn-ghost btn-sm text-rose-600">حذف</button>@endif
                <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="block text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
            </div>
            @error('logo')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="card space-y-4 p-5">
        <h2 class="font-extrabold">العملة والضريبة والمخزون</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-field label="رمز العملة (ISO) *" model="form.currency" dir="ltr" maxlength="3" />
            <x-field label="رمز العرض *" model="form.currency_symbol" />
            <x-field label="حد المخزون المنخفض *" model="form.low_stock_threshold" type="number" min="0" dir="ltr" />
            <x-field label="نسبة الضريبة (%) *" model="form.tax_rate" type="number" step="0.01" min="0" dir="ltr" />
            <label class="flex items-end gap-2 pb-2 text-sm font-bold"><input type="checkbox" wire:model="form.tax_enabled" class="rounded text-brand-600"> تفعيل الضريبة</label>
        </div>
    </section>

    <section class="card space-y-4 p-5">
        <h2 class="font-extrabold">التواصل</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="البريد الإلكتروني" model="form.contact_email" type="email" dir="ltr" />
            <x-field label="الهاتف" model="form.contact_phone" dir="ltr" />
            <x-field label="العنوان" model="form.contact_address" wrapper="sm:col-span-2" />
        </div>
    </section>

    <button class="btn-primary !px-10" wire:loading.attr="disabled" wire:target="save,logo">حفظ الإعدادات</button>
</form>
