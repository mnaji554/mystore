@extends('emails.layout')
@section('content')
    <h2 style="margin:0 0 8px;">إعادة تعيين كلمة المرور</h2>
    <p>مرحباً {{ $user->name }}، تلقينا طلباً لإعادة تعيين كلمة مرور حسابك. اضغط على الزر أدناه لاختيار كلمة مرور جديدة. ينتهي الرابط خلال {{ $expire }} دقيقة.</p>
    <p style="margin-top:24px;"><a href="{{ $url }}" style="background:#4f46e5;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:bold;">تعيين كلمة مرور جديدة</a></p>
    <p style="margin-top:24px;color:#64748b;font-size:13px;">إذا لم تطلب ذلك فتجاهل هذه الرسالة، ولن يتم تغيير كلمة مرورك.</p>
@endsection
