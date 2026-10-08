<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(string $slug)
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();

        return ReviewResource::collection(
            $product->reviews()->approved()->with(['user:id,name', 'images'])->latest('id')->paginate(15)
        );
    }

    private function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function store(Request $request, string $slug, ReviewService $reviews): JsonResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();
        $data = $request->validate($this->rules());

        $review = $reviews->save($request->user(), $product, $data, $request->file('photos', []));

        return (new ReviewResource($review->load('images')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Review $review, ReviewService $reviews)
    {
        $this->authorize('update', $review);
        $data = $request->validate($this->rules());

        return new ReviewResource($reviews->save($request->user(), $review->product, $data, $request->file('photos', []))->load('images'));
    }

    public function destroy(Review $review, ReviewService $reviews): JsonResponse
    {
        $this->authorize('delete', $review);
        $reviews->delete($review);

        return response()->json(['message' => 'تم حذف التقييم.']);
    }
}
