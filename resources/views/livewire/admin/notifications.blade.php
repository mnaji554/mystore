<div class="space-y-3">
    <div class="flex justify-end"><button type="button" wire:click="markAllRead" class="btn-outline btn-sm">تعليم الكل كمقروء</button></div>
    @forelse($notifications as $n)
        <button type="button" wire:key="n-{{ $n->id }}" wire:click="open('{{ $n->id }}')" class="card flex w-full items-center justify-between gap-3 p-4 text-start text-sm {{ $n->read_at ? 'opacity-70' : '' }}">
            <span class="flex items-center gap-3">
                @unless($n->read_at)<span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>@endunless
                <span><b>{{ $n->data['title'] ?? 'إشعار' }}</b><br><span class="text-slate-500">{{ $n->data['message'] ?? '' }}</span></span>
            </span>
            <span class="shrink-0 text-xs text-slate-500">{{ $n->created_at->diffForHumans() }}</span>
        </button>
    @empty
        <x-empty-state icon="bell" title="لا توجد إشعارات" />
    @endforelse
    {{ $notifications->links() }}
</div>
