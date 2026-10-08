@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">تم إلغاء طلبك</h2>
    <p>أهلاً {{ $order->customer_name }}، تم إلغاء طلبك رقم <b dir="ltr">{{ $order->order_number }}</b>. إن كنت قد دفعت مسبقاً فسيتم رد المبلغ وفق سياسة الاسترجاع.</p>
    <p>لأي استفسار تواصل معنا على {{ setting('contact_email') }}.</p>
@endsection
