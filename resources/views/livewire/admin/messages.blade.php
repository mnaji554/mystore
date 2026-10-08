<div class="space-y-3">
    @forelse($messages as $message)
        <article wire:key="msg-{{ $message->id }}" class="card p-4 text-sm">
            <button type="button" wire:click="open({{ $message->id }})" class="flex w-full items-center justify-between gap-3 text-start">
                <span class="flex items-center gap-2">
                    @unless($message->read_at)<span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>@endunless
                    <b>{{ $message->subject }}</b> <span class="text-slate-500">— {{ $message->name }}</span>
                </span>
                <span class="text-xs text-slate-500">{{ $message->created_at->diffForHumans() }}</span>
            </button>
            @if($openId === $message->id)
                <div class="mt-3 space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <p class="whitespace-pre-line leading-7">{{ $message->message }}</p>
                    <p class="text-xs text-slate-500"><span dir="ltr">{{ $message->email }}</span>@if($message->phone) · <span dir="ltr">{{ $message->phone }}</span>@endif</p>
                    <div class="flex gap-2">
                        <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('رد: '.$message->subject) }}" class="btn-primary btn-sm"><x-icon name="mail" class="h-4 w-4" /> رد بالبريد</a>
                        @can('delete-records')<button type="button" wire:click="delete({{ $message->id }})" wire:confirm="حذف الرسالة؟" class="btn-ghost btn-sm text-rose-600">حذف</button>@endcan
                    </div>
                </div>
            @endif
        </article>
    @empty
        <x-empty-state icon="mail" title="لا توجد رسائل" />
    @endforelse
    {{ $messages->links() }}
</div>
