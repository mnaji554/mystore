<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return OrderResource::collection(
            $request->user()->orders()->with('items')->latest('id')->paginate(15)
        );
    }

    public function show(Request $request, string $orderNumber)
    {
        $order = Order::with(['items', 'histories'])->where('order_number', $orderNumber)->firstOrFail();
        $this->authorize('view', $order);

        return new OrderResource($order);
    }

    public function store(Request $request, CartService $carts, OrderService $orders, PaymentService $payments): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'address' => ['required_without:address_id', 'array'],
            'address.full_name' => ['required_without:address_id', 'string', 'max:120'],
            'address.phone' => ['required_without:address_id', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address.country' => ['nullable', 'string', 'max:80'],
            'address.city' => ['required_without:address_id', 'string', 'max:100'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.street' => ['required_without:address_id', 'string', 'max:190'],
            'address.building' => ['nullable', 'string', 'max:60'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_method_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($data['address_id'])) {
            $address = $user->addresses()->find($data['address_id']);

            if (! $address) {
                throw ValidationException::withMessages(['address_id' => 'العنوان غير موجود.']);
            }

            $snapshot = $address->toSnapshot();
        } else {
            $snapshot = collect($data['address'])->map(fn ($v) => is_string($v) ? strip_tags(trim($v)) : $v)->all() + ['country' => 'المملكة العربية السعودية'];
        }

        $cart = $carts->resolve(false, $user);
        abort_unless($cart && $cart->items()->exists(), 422, 'سلة المشتريات فارغة.');

        $order = $orders->createFromCart($cart, [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $snapshot['phone'] ?? (string) $user->phone,
            'address' => $snapshot,
            'shipping_method_id' => $data['shipping_method_id'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
        ], $user);

        $result = $payments->initiate($order);

        return (new OrderResource($order->load('items')))
            ->additional(['payment_url' => $result->redirectUrl])
            ->response()->setStatusCode(201);
    }

    public function cancel(Request $request, string $orderNumber, OrderService $orders)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorize('cancel', $order);

        $orders->cancelByCustomer($order, $request->user());

        return new OrderResource($order->fresh()->load('items'));
    }
}
