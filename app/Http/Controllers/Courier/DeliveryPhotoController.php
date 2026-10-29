<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryPhotoController extends Controller
{
    public function __invoke(Order $order, int $index, string $variant): StreamedResponse
    {
        abort_unless(auth()->user()?->hasRole(Role::COURIER), 403);
        abort_unless((int) $order->courier_id === (int) auth()->id(), 404);
        abort_unless(in_array($variant, ['image', 'thumb'], true), 404);

        $photo = $order->delivery_photos[$index] ?? null;
        abort_unless(is_array($photo), 404);

        $path = $photo[$variant === 'thumb' ? 'thumb_path' : 'path'] ?? null;
        abort_unless(
            is_string($path) && Str::startsWith($path, "delivery-proofs/{$order->id}/"),
            404,
        );

        return Storage::disk('private_documents')->response($path, headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
