<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="mb-4 flex justify-end">
            @can('create', App\Models\Category::class)<button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> تصنيف جديد</button>@endcan
        </div>
        <div class="card overflow-hidden">
            @forelse($tree as $row)
                @php $cat = $categories[$row['id']]; @endphp
                <div wire:key="cat-{{ $cat->id }}" class="flex items-center justify-between gap-3 border-b border-slate-100 p-3 last:border-0 dark:border-slate-800" style="padding-inline-start: {{ 0.75 + $row['depth'] * 1.5 }}rem">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                            @if($cat->image_url)<img src="{{ $cat->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">@else<x-icon name="grid" class="h-5 w-5 text-slate-400" />@endif
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-bold">{{ $cat->name_ar }} @unless($cat->is_active)<span class="badge badge-slate">مخفي</span>@endunless</p>
                            <p class="text-xs text-slate-500"><span dir="ltr">{{ $cat->slug }}</span> · {{ $counts[$cat->id] ?? 0 }} منتج</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button type="button" wire:click="move({{ $cat->id }}, 'up')" class="btn-ghost btn-sm !p-1.5" aria-label="أعلى"><x-icon name="chevron-up" class="h-4 w-4" /></button>
                        <button type="button" wire:click="move({{ $cat->id }}, 'down')" class="btn-ghost btn-sm !p-1.5" aria-label="أسفل"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                        <button type="button" wire:click="edit({{ $cat->id }})" class="btn-ghost btn-sm !p-1.5" aria-label="تعديل"><x-icon name="edit" class="h-4 w-4" /></button>
                        @can('delete', $cat)<button type="button" wire:click="delete({{ $cat->id }})" wire:confirm="حذف التصنيف «{{ $cat->name_ar }}»؟" class="btn-ghost btn-sm !p-1.5 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>@endcan
                    </div>
                </div>
            @empty
                <x-empty-state icon="grid" title="لا توجد تصنيفات" text="أنشئ أول تصنيف لتنظيم منتجاتك." class="!border-0 !shadow-none" />
            @endforelse
        </div>
    </div>

    <div>
        @if($showForm)
            <form wire:submit="save" class="card sticky top-24 animate-fade-in space-y-3 p-5">
                <h2 class="font-extrabold">{{ $editingId ? 'تعديل التصنيف' : 'تصنيف جديد' }}</h2>
                <x-field label="الاسم بالعربية *" model="form.name_ar" />
                <x-field label="الاسم بالإنجليزية" model="form.name_en" dir="ltr" />
                <x-field label="الرابط (Slug)" model="form.slug" dir="ltr" hint="يُولّد تلقائياً إن تُرك فارغاً." />
                <div>
                    <label class="label" for="parent">التصنيف الأب</label>
                    <select id="parent" wire:model="form.parent_id" class="input">
                        <option value="">— تصنيف رئيسي —</option>
                        @foreach($parents as $p)<option value="{{ $p['id'] }}">{{ $p['name'] }}</option>@endforeach
                    </select>
                    @error('form.parent_id')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="label" for="cdesc">الوصف</label><textarea id="cdesc" wire:model="form.description" rows="3" class="input"></textarea></div>
                <x-field label="الترتيب" model="form.sort_order" type="number" min="0" dir="ltr" />
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_active" class="rounded text-brand-600"> مفعّل</label>

                <div>
                    <p class="label">الصورة</p>
                    @if($image && $image->isPreviewable())<img src="{{ $image->temporaryUrl() }}" alt="" class="mb-2 h-20 w-20 rounded-lg object-cover">
                    @elseif($currentImage)<div class="mb-2 flex items-center gap-2"><img src="{{ Storage::disk('public')->url($currentImage) }}" alt="" class="h-20 w-20 rounded-lg object-cover"><button type="button" wire:click="removeImage" class="btn-ghost btn-sm text-rose-600">حذف الصورة</button></div>@endif
                    <input type="file" wire:model="image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm file:me-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-bold file:text-brand-700">
                    @error('image')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <details class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                    <summary class="cursor-pointer text-sm font-bold">إعدادات SEO</summary>
                    <div class="mt-3 space-y-3">
                        <x-field label="عنوان SEO" model="form.seo_title" />
                        <div><label class="label" for="cseod">وصف SEO</label><textarea id="cseod" wire:model="form.seo_description" rows="2" class="input"></textarea></div>
                        <x-field label="كلمات مفتاحية" model="form.seo_keywords" />
                    </div>
                </details>

                <div class="flex gap-2">
                    <button class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save,image">حفظ</button>
                    <button type="button" wire:click="resetForm" class="btn-outline">إلغاء</button>
                </div>
            </form>
        @else
            <div class="card p-5 text-sm text-slate-500">اختر تصنيفاً للتعديل أو أنشئ تصنيفاً جديداً. يدعم النظام تصنيفات متداخلة (رئيسي وفرعي) وترتيباً يدوياً.</div>
        @endif
    </div>
</div>
