@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">تم استرجاع طلبك</h2>
    <p>أهلاً {{ $order->customer_name }}، تمت معالجة استرجاع طلبك رقم <b dir="ltr">{{ $order->order_number }}</b> بمبلغ {{ money($order->grand_total) }}. قد تستغرق عملية رد المبلغ من 5 إلى 14 يوم عمل.</p>
@endsection
