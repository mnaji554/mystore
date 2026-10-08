@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">تم تسليم طلبك 🎉</h2>
    <p>أهلاً {{ $order->customer_name }}، تم تسليم طلبك رقم <b dir="ltr">{{ $order->order_number }}</b>. نأمل أن ينال إعجابك!</p>
    <p>يسعدنا معرفة رأيك — يمكنك تقييم المنتجات من حسابك.</p>
    <p style="margin-top:20px;"><a href="{{ route('account.orders.show', $order->order_number) }}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">قيّم مشترياتك</a></p>
@endsection
