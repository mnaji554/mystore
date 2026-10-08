<x-account-shell title="عناويني">
    <div class="mb-4 flex justify-end">
        @unless($showForm)<button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> عنوان جديد</button>@endunless
    </div>

    @if($showForm)
        <form wire:submit="save" class="card mb-6 animate-fade-in space-y-4 p-6">
            <h2 class="font-extrabold">{{ $editingId ? 'تعديل العنوان' : 'إضافة عنوان' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="تسمية العنوان (المنزل، العمل…)" model="label" />
                <x-field label="اسم المستلم" model="full_name" />
                <x-field label="رقم الجوال" model="phone" type="tel" dir="ltr" />
                <x-field label="الدولة" model="country" />
                <x-field label="المدينة" model="city" />
                <x-field label="الحي" model="district" />
                <x-field label="الشارع" model="street" />
                <x-field label="رقم المبنى / الشقة" model="building" />
                <x-field label="الرمز البريدي" model="postal_code" dir="ltr" />
            </div>
            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="is_default" class="rounded text-brand-600"> جعله العنوان الافتراضي</label>
            <div class="flex gap-2">
                <button class="btn-primary" wire:loading.attr="disabled">حفظ</button>
                <button type="button" wire:click="resetForm" class="btn-outline">إلغاء</button>
            </div>
        </form>
    @endif

    @if($addresses->isEmpty() && ! $showForm)
        <x-empty-state icon="map-pin" title="لا توجد عناوين محفوظة" text="أضف عنواناً لتسريع عملية الدفع." />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="card p-5 text-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-extrabold">{{ $address->label ?: $address->full_name }}</p>
                        @if($address->is_default)<span class="badge badge-emerald">الافتراضي</span>@endif
                    </div>
                    <p>{{ $address->full_name }}</p>
                    <p class="text-slate-500">{{ $address->summary }}</p>
                    <p class="text-slate-500" dir="ltr" style="text-align:right">{{ $address->phone }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="edit({{ $address->id }})" class="btn-outline btn-sm"><x-icon name="edit" class="h-4 w-4" /> تعديل</button>
                        @unless($address->is_default)<button type="button" wire:click="makeDefault({{ $address->id }})" class="btn-ghost btn-sm">جعله افتراضياً</button>@endunless
                        <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="حذف هذا العنوان؟" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="h-4 w-4" /></button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-account-shell>
