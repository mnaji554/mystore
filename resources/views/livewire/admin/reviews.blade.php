<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach(['pending' => 'بانتظار الموافقة ('.$pendingCount.')', 'approved' => 'معتمدة', 'rejected' => 'مرفوضة', '' => 'الكل'] as $key => $label)
            <button type="button" wire:click="$set('status', '{{ $key }}')" class="badge !px-4 !py-2 text-sm {{ $status === (string) $key ? 'bg-brand-600 text-white' : 'badge-slate' }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($reviews as $review)
            <article wire:key="rv-{{ $review->id }}" class="card p-5 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><a href="{{ route('products.show', $review->product->slug) }}" target="_blank" class="font-extrabold hover:text-brand-600">{{ $review->product->name_ar }}</a>
                        <span class="text-slate-500">· {{ $review->user->name }} · {{ $review->created_at->format('Y/m/d') }}</span></div>
                    <span class="badge badge-{{ ['approved' => 'emerald', 'pending' => 'amber', 'rejected' => 'rose'][$review->status] }}">{{ ['approved' => 'معتمد', 'pending' => 'بانتظار', 'rejected' => 'مرفوض'][$review->status] }}</span>
                </div>
                <x-rating :value="$review->rating" class="mt-2" />
                @if($review->title)<p class="mt-2 font-bold">{{ $review->title }}</p>@endif
                @if($review->comment)<p class="mt-1 whitespace-pre-line text-slate-600 dark:text-slate-400">{{ $review->comment }}</p>@endif
                @if($review->images->isNotEmpty())
                    <div class="mt-3 flex gap-2">@foreach($review->images as $img)<a href="{{ $img->url }}" target="_blank" rel="noopener"><img src="{{ $img->url }}" alt="" loading="lazy" class="h-16 w-16 rounded-lg object-cover"></a>@endforeach</div>
                @endif
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($review->status !== 'approved')<button type="button" wire:click="moderate({{ $review->id }}, 'approved')" class="btn-primary btn-sm"><x-icon name="check" class="h-4 w-4" /> موافقة</button>@endif
                    @if($review->status !== 'rejected')<button type="button" wire:click="moderate({{ $review->id }}, 'rejected')" class="btn-outline btn-sm">رفض</button>@endif
                    <button type="button" wire:click="delete({{ $review->id }})" wire:confirm="حذف التقييم نهائياً؟" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="h-4 w-4" /> حذف</button>
                </div>
            </article>
        @empty
            <x-empty-state icon="star" title="لا توجد تقييمات في هذه القائمة" />
        @endforelse
    </div>
    {{ $reviews->links() }}
</div>
