<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    private function respond(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve(true, $request->user());

        return response()->json(['data' => $this->carts->summary($cart, null, $request->user())->toArray()]);
    }

    public function show(Request $request): JsonResponse
    {
        return $this->respond($request);
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $this->carts->add($this->carts->resolve(true, $request->user()), $data['product_id'], $data['variant_id'] ?? null, $data['quantity'] ?? 1);

        return $this->respond($request);
    }

    public function update(Request $request, int $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:50']]);

        $this->carts->updateQuantity($this->carts->resolve(true, $request->user()), $item, $data['quantity']);

        return $this->respond($request);
    }

    public function remove(Request $request, int $item): JsonResponse
    {
        $this->carts->remove($this->carts->resolve(true, $request->user()), $item);

        return $this->respond($request);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $this->carts->applyCoupon($this->carts->resolve(true, $request->user()), $data['code'], $request->user());

        return $this->respond($request);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $this->carts->removeCoupon($this->carts->resolve(true, $request->user()));

        return $this->respond($request);
    }
}
