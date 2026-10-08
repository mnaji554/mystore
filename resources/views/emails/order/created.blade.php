@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">شكراً لطلبك {{ $order->customer_name }}!</h2>
    <p>استلمنا طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> وجارٍ مراجعته. سنوافيك بأي تحديثات على حالته.</p>
    @include('emails.partials.order-summary')
    <p style="margin-top:20px;">طريقة الدفع: {{ app(\App\Services\PaymentService::class)->gateways()->get($order->payment_method)?->label() }}<br>الشحن: {{ $order->shipping_method_name }}<br>العنوان: {{ $order->address_line }}</p>
    <p style="margin-top:24px;"><a href="{{ route('orders.track') }}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">تتبع الطلب</a></p>
@endsection
