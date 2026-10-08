@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">تم تأكيد طلبك ✅</h2>
    <p>أهلاً {{ $order->customer_name }}، تم تأكيد طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> وسنبدأ بتجهيزه قريباً.</p>
    @include('emails.partials.order-summary')
@endsection
