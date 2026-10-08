<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\StripeGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, PaymentService $payments): Response
    {
        if (! $stripe->isAvailable() || blank(config('services.stripe.webhook_secret'))) {
            return response('Webhook not configured', 503);
        }

        try {
            $event = $stripe->constructEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Invalid signature', 400);
        }

        $object = $event->data->object;
        $payment = Payment::query()->with('order')
            ->where('id', (int) ($object->metadata['payment_id'] ?? 0))
            ->where('gateway', 'stripe')->first();

        if (! $payment) {
            return response('ok');
        }

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if (($object->payment_status ?? null) === 'paid') {
                    $payments->markPaid($payment, $object->payment_intent ?? $object->id, ['event' => $event->id]);
                }
                break;
            case 'checkout.session.async_payment_failed':
            case 'checkout.session.expired':
                if ($payment->status !== PaymentStatus::Paid) {
                    $payments->markFailed($payment, $event->type);
                }
                break;
        }

        return response('ok');
    }
}
