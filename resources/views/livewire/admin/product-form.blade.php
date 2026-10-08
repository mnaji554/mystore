<form wire:submit="save" class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.products.index') }}" class="btn-ghost btn-sm"><x-icon name="chevron-right" class="h-4 w-4" /> رجوع للمنتجات</a>
        <button class="btn-primary !px-8" wire:loading.attr="disabled" wire:target="save,uploads">
            <span wire:loading.remove wire:target="save">حفظ المنتج</span><span wire:loading wire:target="save">جارٍ الحفظ…</span>
        </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card space-y-4 p-5">
                <h2 class="font-extrabold">المعلومات الأساسية</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="الاسم بالعربية *" model="form.name_ar" />
                    <x-field label="الاسم بالإنجليزية" model="form.name_en" dir="ltr" />
                    <x-field label="SKU *" model="form.sku" dir="ltr" />
                    <x-field label="الرابط (Slug) — يُولّد تلقائياً إن تُرك فارغاً" model="form.slug" dir="ltr" />
                </div>
                <div>
                    <label class="label" for="short">وصف مختصر</label>
                    <textarea id="short" wire:model="form.short_description" rows="2" class="input"></textarea>
                    @error('form.short_description')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="desc">الوصف</label>
                    <textarea id="desc" wire:model="form.description" rows="7" class="input"></textarea>
                    @error('form.description')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="font-extrabold">التسعير والمخزون</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="السعر *" model="form.price" type="number" step="0.01" min="0" dir="ltr" />
                    <x-field label="سعر الخصم" model="form.sale_price" type="number" step="0.01" min="0" dir="ltr" hint="نسبة الخصم تُحسب تلقائياً." />
                    <x-field label="سعر التكلفة" model="form.cost_price" type="number" step="0.01" min="0" dir="ltr" hint="لحساب الأرباح." />
                    <x-field label="المخزون *" model="form.stock" type="number" min="0" dir="ltr" :disabled="count($variants) > 0" hint="{{ count($variants) ? 'يُدار من المتغيرات' : '' }}" />
                    <x-field label="الوزن (كجم)" model="form.weight" type="number" step="0.001" min="0" dir="ltr" />
                </div>
            </section>

            <section class="card space-y-4 p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-extrabold">المتغيرات (الألوان / المقاسات)</h2>
                    <button type="button" wire:click="addVariant" class="btn-outline btn-sm"><x-icon name="plus" class="h-4 w-4" /> إضافة متغير</button>
                </div>
                @if(empty($variants))
                    <p class="text-sm text-slate-500">لا توجد متغيرات — المنتج يُباع كوحدة واحدة بمخزون واحد. أضف متغيراً لتفعيل اللون/المقاس بسعر ومخزون وSKU مستقل.</p>
                @endif
                @foreach($variants as $i => $variant)
                    <div wire:key="variant-{{ $variant['id'] ?? 'new' }}-{{ $i }}" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            <x-field label="SKU" model="variants.{{ $i }}.sku" dir="ltr" />
                            <x-field label="السعر" model="variants.{{ $i }}.price" type="number" step="0.01" dir="ltr" />
                            <x-field label="سعر الخصم" model="variants.{{ $i }}.sale_price" type="number" step="0.01" dir="ltr" />
                            <x-field label="المخزون" model="variants.{{ $i }}.stock" type="number" min="0" dir="ltr" />
                            <x-field label="اللون" model="variants.{{ $i }}.color" />
                            <div>
                                <label class="label">درجة اللون</label>
                                <input type="color" wire:model="variants.{{ $i }}.color_hex" class="h-10 w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
                            </div>
                            <x-field label="المقاس" model="variants.{{ $i }}.size" />
                            <div class="flex items-end justify-between gap-2">
                                <label class="flex items-center gap-2 pb-2 text-sm font-bold"><input type="checkbox" wire:model="variants.{{ $i }}.is_active" class="rounded text-brand-600"> مفعّل</label>
                                <button type="button" wire:click="removeVariant({{ $i }})" class="btn-ghost btn-sm text-rose-600" aria-label="حذف المتغير"><x-icon name="trash" class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="font-extrabold">تحسين محركات البحث (SEO)</h2>
                <x-field label="عنوان SEO" model="form.seo_title" />
                <div><label class="label" for="seod">وصف SEO</label><textarea id="seod" wire:model="form.seo_description" rows="2" class="input"></textarea></div>
                <x-field label="كلمات مفتاحية (مفصولة بفواصل)" model="form.seo_keywords" />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-4 p-5">
                <h2 class="font-extrabold">النشر</h2>
                <div>
                    <label class="label" for="status">الحالة</label>
                    <select id="status" wire:model="form.status" class="input"><option value="active">منشور</option><option value="draft">مسودة</option></select>
                </div>
                <div>
                    <label class="label" for="cat">التصنيف</label>
                    <select id="cat" wire:model="form.category_id" class="input">
                        <option value="">— بدون تصنيف —</option>
                        @foreach($categoryOptions as $opt)<option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>@endforeach
                    </select>
                    @error('form.category_id')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_featured" class="rounded text-brand-600"> منتج مميز</label>
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_bestseller" class="rounded text-brand-600"> الأكثر مبيعاً</label>
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" wire:model="form.is_new" class="rounded text-brand-600"> منتج جديد</label>
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="font-extrabold">الصور</h2>

                @if($images->isNotEmpty())
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($images as $image)
                            <div wire:key="img-{{ $image->id }}" class="group relative overflow-hidden rounded-xl border {{ $image->is_primary ? 'border-brand-600 ring-2 ring-brand-500/30' : 'border-slate-200 dark:border-slate-700' }}">
                                <img src="{{ $image->thumb_url }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
                                @if($image->is_primary)<span class="badge absolute start-1 top-1 bg-brand-600 text-white">الرئيسية</span>@endif
                                <div class="flex items-center justify-between gap-1 bg-white/95 p-1 dark:bg-slate-900/95">
                                    <button type="button" wire:click="moveImage({{ $image->id }}, 'up')" class="btn-ghost btn-sm !p-1" aria-label="تقديم"><x-icon name="chevron-right" class="h-4 w-4" /></button>
                                    <button type="button" wire:click="moveImage({{ $image->id }}, 'down')" class="btn-ghost btn-sm !p-1" aria-label="تأخير"><x-icon name="chevron-left" class="h-4 w-4" /></button>
                                    @unless($image->is_primary)<button type="button" wire:click="setPrimary({{ $image->id }})" class="btn-ghost btn-sm !p-1" title="جعلها رئيسية"><x-icon name="star" class="h-4 w-4" /></button>@endunless
                                    <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="حذف الصورة؟" class="btn-ghost btn-sm !p-1 text-rose-600" aria-label="حذف"><x-icon name="trash" class="h-4 w-4" /></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($uploads)
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($uploads as $i => $upload)
                            <div wire:key="up-{{ $i }}" class="relative">
                                @if($upload->isPreviewable())<img src="{{ $upload->temporaryUrl() }}" alt="" class="aspect-square w-full rounded-lg object-cover">@else<span class="grid aspect-square w-full place-items-center rounded-lg bg-rose-50 text-xs font-bold text-rose-600">ملف غير صالح</span>@endif
                                <button type="button" wire:click="removeUpload({{ $i }})" class="absolute end-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-rose-600 text-white" aria-label="إزالة"><x-icon name="close" class="h-3.5 w-3.5" /></button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <label class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 hover:border-brand-400 dark:border-slate-700">
                    <x-icon name="photo" class="h-8 w-8" />
                    <span class="font-bold">اضغط لرفع صور (حتى {{ \App\Livewire\Admin\ProductForm::MAX_IMAGES }})</span>
                    <span class="text-xs">JPG / PNG / WebP — حتى 5MB — تُحوّل تلقائياً إلى WebP</span>
                    <input type="file" wire:model="uploads" multiple accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
                </label>
                <div wire:loading wire:target="uploads" class="text-sm font-bold text-brand-600">جارٍ رفع الصور…</div>
                @error('uploads')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                @error('uploads.*')<p class="text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                @if(! $productId)<p class="text-xs text-slate-500">ستُحفظ الصور مع المنتج، وأول صورة تصبح الرئيسية.</p>@endif
            </section>
        </div>
    </div>
</form>
