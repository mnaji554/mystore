<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\StoreException;
use App\Models\Order;
use App\Services\Payments\StripeGateway;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutResultController extends Controller
{
    private function accessibleOrder(Request $request, string $number): Order
    {
        $order = Order::query()->with(['items', 'payment'])->where('order_number', $number)->firstOrFail();

        $allowed = ($request->user() && $request->user()->id === $order->user_id)
            || $request->session()->get('last_order_number') === $order->order_number;

        abort_unless($allowed, 404);

        return $order;
    }

    public function success(Request $request, string $number)
    {
        $order = $this->accessibleOrder($request, $number);

        return view('pages.order-success', ['order' => $order]);
    }

    public function stripeReturn(Request $request, string $number, PaymentService $payments, StripeGateway $stripe): RedirectResponse
    {
        $order = $this->accessibleOrder($request, $number);
        $sessionId = (string) $request->query('session_id');

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('checkout.success', $order->order_number);
        }

        [$paid, $reference] = $sessionId !== '' ? $stripe->verifySession($sessionId, $order) : [false, null];

        if ($paid && $order->payment) {
            $payments->markPaid($order->payment, $reference, ['session' => $sessionId]);

            return redirect()->route('checkout.success', $order->order_number);
        }

        return redirect()->route('checkout.payment-cancelled', $order->order_number);
    }

    public function cancelled(Request $request, string $number)
    {
        $order = $this->accessibleOrder($request, $number);

        return view('pages.payment-cancelled', ['order' => $order]);
    }

    public function retryPayment(Request $request, string $number, PaymentService $payments): RedirectResponse
    {
        $order = $this->accessibleOrder($request, $number);

        if ($order->payment_status === PaymentStatus::Paid || ! $order->status->customerCanCancel()) {
            return redirect()->route('checkout.success', $order->order_number);
        }

        try {
            $result = $payments->initiate($order);
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $result->requiresRedirect()
            ? redirect()->away($result->redirectUrl)
            : redirect()->route('checkout.success', $order->order_number);
    }
}
