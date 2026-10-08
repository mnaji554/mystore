@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">تم شحن طلبك 🚚</h2>
    <p>أهلاً {{ $order->customer_name }}، طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> في الطريق إليك عبر {{ $order->shipping_method_name }}.</p>
    @if($order->tracking_number)
        <p>رقم التتبع: <b dir="ltr">{{ $order->tracking_number }}</b></p>
        @if($order->tracking_url)<p><a href="{{ $order->tracking_url }}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">تتبع الشحنة</a></p>@endif
    @endif
    @include('emails.partials.order-summary')
@endsection
