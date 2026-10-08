@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">أهلاً {{ $user->name }} 👋</h2>
    <p>يسعدنا انضمامك إلى {{ setting('store_name') }}. تم إنشاء حسابك بنجاح ويمكنك الآن التسوق وتتبع طلباتك وحفظ مفضلاتك.</p>
    <p style="margin-top:24px;"><a href="{{ route('products.index') }}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">ابدأ التسوق</a></p>
@endsection
