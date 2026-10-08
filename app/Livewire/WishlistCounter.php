<?php

namespace App\Livewire;

use App\Services\WishlistService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistCounter extends Component
{
    #[On('wishlist-updated')]
    public function refresh(): void
    {
        unset($this->count);
    }

    #[Computed]
    public function count(): int
    {
        return app(WishlistService::class)->count(auth()->user());
    }

    public function render()
    {
        return <<<'HTML'
        <a href="{{ route('wishlist') }}" class="btn-ghost relative !p-2" aria-label="المفضلة">
            <x-icon name="heart" class="h-6 w-6" />
            @if($this->count > 0)
                <span class="absolute -end-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white">{{ $this->count }}</span>
            @endif
        </a>
        HTML;
    }
}
