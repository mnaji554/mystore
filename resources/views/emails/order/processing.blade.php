@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">طلبك قيد التجهيز 📦</h2>
    <p>أهلاً {{ $order->customer_name }}، نقوم الآن بتجهيز طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> للشحن.</p>
    @include('emails.partials.order-summary')
@endsection
