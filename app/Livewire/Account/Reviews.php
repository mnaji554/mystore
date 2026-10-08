<?php

namespace App\Livewire\Account;

use App\Models\Review;
use App\Services\ReviewService;
use Livewire\Component;
use Livewire\WithPagination;

class Reviews extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public int $rating = 5;

    public string $title = '';

    public string $comment = '';

    public function edit(int $id): void
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        $this->editingId = $id;
        $this->rating = $review->rating;
        $this->title = (string) $review->title;
        $this->comment = (string) $review->comment;
    }

    public function save(): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::with('product')->findOrFail($this->editingId);
        $this->authorize('update', $review);

        // Editing sends the review back to moderation.
        $review->update([
            'rating' => $this->rating,
            'title' => $this->title ?: null,
            'comment' => $this->comment ?: null,
            'status' => Review::PENDING,
            'approved_at' => null,
        ]);
        app(ReviewService::class)->refreshRating($review->product);

        $this->editingId = null;
        $this->dispatch('notify', message: 'تم تحديث تقييمك وسيُراجع قبل النشر.', type: 'success');
    }

    public function delete(int $id): void
    {
        $review = Review::with(['product', 'images'])->findOrFail($id);
        $this->authorize('delete', $review);
        app(ReviewService::class)->delete($review);
        $this->dispatch('notify', message: 'تم حذف التقييم.', type: 'success');
    }

    public function render()
    {
        return view('livewire.account.reviews', [
            'reviews' => Review::query()->with(['product:id,name_ar,slug', 'images'])
                ->where('user_id', auth()->id())->latest('id')->paginate(10),
        ])->layout('components.layouts.app', ['title' => 'تقييماتي', 'noindex' => true]);
    }
}
