<x-account-shell title="تقييماتي">
    @if($reviews->isEmpty())
        <x-empty-state icon="star" title="لم تكتب أي تقييم بعد" text="بعد استلام طلباتك يمكنك تقييم المنتجات من صفحة المنتج.">
            <a href="{{ route('account.orders') }}" class="btn-primary">طلباتي</a>
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach($reviews as $review)
                <article wire:key="rv-{{ $review->id }}" class="card p-5 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <a href="{{ route('products.show', $review->product->slug) }}" class="font-extrabold hover:text-brand-600">{{ $review->product->name_ar }}</a>
                        <span class="badge badge-{{ ['approved' => 'emerald', 'pending' => 'amber', 'rejected' => 'rose'][$review->status] }}">{{ ['approved' => 'معتمد', 'pending' => 'بانتظار الموافقة', 'rejected' => 'مرفوض'][$review->status] }}</span>
                    </div>

                    @if($editingId === $review->id)
                        <form wire:submit="save" class="mt-3 space-y-3">
                            <div class="flex gap-1" dir="ltr">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('rating', {{ $i }})" aria-label="{{ $i }}"><x-icon name="star-solid" class="h-7 w-7 {{ $i <= $rating ? 'text-amber-400' : 'text-slate-300' }}" /></button>
                                @endfor
                            </div>
                            <x-field label="العنوان" model="title" maxlength="120" />
                            <div><label class="label" for="c{{ $review->id }}">التعليق</label><textarea id="c{{ $review->id }}" wire:model="comment" rows="3" class="input"></textarea>
                                @error('comment')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror</div>
                            <div class="flex gap-2"><button class="btn-primary btn-sm">حفظ</button><button type="button" wire:click="$set('editingId', null)" class="btn-outline btn-sm">إلغاء</button></div>
                        </form>
                    @else
                        <x-rating :value="$review->rating" class="mt-2" />
                        @if($review->title)<p class="mt-2 font-bold">{{ $review->title }}</p>@endif
                        @if($review->comment)<p class="mt-1 whitespace-pre-line text-slate-600 dark:text-slate-400">{{ $review->comment }}</p>@endif
                        <div class="mt-3 flex gap-2">
                            <button type="button" wire:click="edit({{ $review->id }})" class="btn-outline btn-sm"><x-icon name="edit" class="h-4 w-4" /> تعديل</button>
                            <button type="button" wire:click="delete({{ $review->id }})" wire:confirm="حذف هذا التقييم؟" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="h-4 w-4" /> حذف</button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</x-account-shell>
