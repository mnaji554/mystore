<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function shippingMethods(ShippingService $shipping): JsonResponse
    {
        return response()->json(['data' => $shipping->availableMethods()->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'company' => $m->company?->name,
            'price' => $m->is_free ? 0.0 : (float) $m->price,
            'free_shipping_threshold' => $m->free_shipping_threshold !== null ? (float) $m->free_shipping_threshold : null,
            'delivery_estimate' => $m->delivery_estimate,
        ])->values()]);
    }

    public function paymentMethods(PaymentService $payments): JsonResponse
    {
        return response()->json(['data' => $payments->available()->map(fn ($g, $key) => [
            'key' => $key, 'label' => $g->label(), 'description' => $g->description(),
        ])->values()]);
    }
}
