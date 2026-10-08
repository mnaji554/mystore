<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request, WishlistService $wishlist)
    {
        return ProductResource::collection($wishlist->products($request->user()));
    }

    public function store(Request $request, int $product, WishlistService $wishlist): JsonResponse
    {
        if (! in_array($product, $wishlist->productIds($request->user()), true)) {
            $wishlist->toggle($request->user(), $product);
        }

        return response()->json(['message' => 'أضيف إلى المفضلة.'], 201);
    }

    public function destroy(Request $request, int $product, WishlistService $wishlist): JsonResponse
    {
        $wishlist->remove($request->user(), $product);

        return response()->json(['message' => 'أزيل من المفضلة.']);
    }
}
