<?php

namespace App\Livewire\Admin;

use App\Models\Review;
use App\Services\ReviewService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Reviews extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    public function mount(): void
    {
        $this->authorize('moderate', Review::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function moderate(int $id, string $status, ReviewService $reviews): void
    {
        $this->authorize('moderate', Review::class);
        abort_unless(in_array($status, [Review::APPROVED, Review::REJECTED, Review::PENDING], true), 422);

        $reviews->moderate(Review::with('product')->findOrFail($id), $status);
        $this->dispatch('notify', message: 'تم تحديث حالة التقييم', type: 'success');
    }

    public function delete(int $id, ReviewService $reviews): void
    {
        $review = Review::with(['product', 'images'])->findOrFail($id);
        $this->authorize('delete', $review);
        $reviews->delete($review);
        $this->dispatch('notify', message: 'تم حذف التقييم', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.reviews', [
            'reviews' => Review::query()->with(['product:id,name_ar,slug', 'user:id,name', 'images'])
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->latest('id')->paginate(12),
            'pendingCount' => Review::where('status', Review::PENDING)->count(),
        ])->layout('components.layouts.admin', ['title' => 'التقييمات']);
    }
}
