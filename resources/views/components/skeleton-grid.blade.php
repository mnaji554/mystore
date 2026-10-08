@props(['count' => 8])
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
    @for($i = 0; $i < $count; $i++)
        <div class="card overflow-hidden p-0">
            <div class="skeleton aspect-square !rounded-none"></div>
            <div class="space-y-2 p-3.5">
                <div class="skeleton h-3 w-1/3"></div>
                <div class="skeleton h-4 w-full"></div>
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton mt-3 h-9 w-full"></div>
            </div>
        </div>
    @endfor
</div>
