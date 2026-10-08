<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', setting('store_name'))</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#4f46e5;padding:22px 28px;color:#ffffff;font-size:22px;font-weight:bold;">{{ setting('store_name') }}</td></tr>
            <tr><td style="padding:28px;color:#1e293b;font-size:15px;line-height:1.9;">
                @yield('content')
            </td></tr>
            <tr><td style="padding:18px 28px;background:#f8fafc;color:#64748b;font-size:12px;text-align:center;">
                {{ setting('contact_email') }} · {{ setting('contact_phone') }}<br>
                © {{ date('Y') }} {{ setting('store_name') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
