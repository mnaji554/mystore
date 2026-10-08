<div x-data class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4"
     @notify.window="$store.toasts.add($event.detail.message, $event.detail.type ?? 'success')"
     aria-live="polite">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="pointer-events-auto flex w-full max-w-sm animate-toast items-start gap-3 rounded-xl border bg-white p-3.5 text-sm font-bold shadow-xl dark:bg-slate-900"
             :class="{
                'border-emerald-200 text-emerald-800 dark:border-emerald-900 dark:text-emerald-300': toast.type === 'success',
                'border-rose-200 text-rose-800 dark:border-rose-900 dark:text-rose-300': toast.type === 'error',
                'border-sky-200 text-sky-800 dark:border-sky-900 dark:text-sky-300': toast.type === 'info',
             }" role="status">
            <span class="flex-1" x-text="toast.message"></span>
            <button @click="$store.toasts.remove(toast.id)" class="opacity-60 hover:opacity-100" aria-label="إغلاق">✕</button>
        </div>
    </template>
</div>
