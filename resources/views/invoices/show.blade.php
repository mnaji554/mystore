<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: {{ $forPdf ? "'dejavusans', sans-serif" : "Tahoma, Arial, sans-serif" }}; color: #1e293b; font-size: 13px; direction: rtl; text-align: right; margin: 0; padding: {{ $forPdf ? '0' : '24px' }}; }
        .wrap { max-width: 800px; margin: 0 auto; }
        h1 { font-size: 24px; margin: 0; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 8px; text-align: right; font-size: 12px; border-bottom: 2px solid #cbd5e1; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .totals td { border: 0; padding: 4px 8px; }
        .muted { color: #64748b; }
        .num { direction: ltr; text-align: left; }
        .box { background: #f8fafc; padding: 10px 14px; border-radius: 6px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="wrap">
    @unless($forPdf)
        <p class="no-print"><button onclick="window.print()" style="padding:8px 18px;cursor:pointer;">طباعة الفاتورة</button></p>
    @endunless

    <table>
        <tr>
            <td style="border:0;padding:0;">
                @if($forPdf && $logoPath)
                    <img src="{{ $logoPath }}" style="height:50px;"><br>
                @elseif(! $forPdf && $logoUrl)
                    <img src="{{ $logoUrl }}" style="height:50px;"><br>
                @endif
                <h1>{{ $storeName }}</h1>
                <div class="muted">{{ $storeAddress }}<br>{{ $storePhone }} · {{ $storeEmail }}@if($vatNumber)<br>الرقم الضريبي: {{ $vatNumber }}@endif</div>
            </td>
            <td style="border:0;padding:0;text-align:left;" class="num">
                <div style="font-size:20px;font-weight:bold;">فاتورة ضريبية</div>
                <div>رقم الفاتورة: {{ $invoice->invoice_number }}</div>
                <div>رقم الطلب: {{ $order->order_number }}</div>
                <div>التاريخ: {{ ($order->placed_at ?? $order->created_at)->format('Y/m/d') }}</div>
            </td>
        </tr>
    </table>

    <br>
    <div class="box">
        <b>بيانات العميل</b><br>
        {{ $order->customer_name }} · {{ $order->customer_email }} · {{ $order->customer_phone }}<br>
        <span class="muted">{{ $order->address_line }}</span>
    </div>
    <br>

    <table>
        <thead>
            <tr><th>#</th><th>المنتج</th><th>SKU</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->name }}@if($item->variant_label) <span class="muted">({{ $item->variant_label }})</span>@endif</td>
                    <td class="num">{{ $item->sku }}</td>
                    <td class="num">{{ money($item->sale_price) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td class="num">{{ money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <table class="totals" style="width:45%;margin-right:0;margin-left:auto;">
        <tr><td>المجموع الفرعي</td><td class="num">{{ money($order->subtotal) }}</td></tr>
        @if($order->discount_total > 0)<tr><td>خصم المنتجات</td><td class="num">-{{ money($order->discount_total) }}</td></tr>@endif
        @if($order->coupon_discount > 0)<tr><td>خصم الكوبون {{ $order->coupon_code }}</td><td class="num">-{{ money($order->coupon_discount) }}</td></tr>@endif
        <tr><td>الشحن</td><td class="num">{{ money($order->shipping_cost) }}</td></tr>
        <tr><td>الضريبة</td><td class="num">{{ money($order->tax_total) }}</td></tr>
        <tr><td style="font-size:15px;font-weight:bold;border-top:2px solid #1e293b;">الإجمالي</td><td class="num" style="font-size:15px;font-weight:bold;border-top:2px solid #1e293b;">{{ money($order->grand_total) }}</td></tr>
    </table>

    <p class="muted" style="margin-top:30px;text-align:center;">شكراً لتسوقكم من {{ $storeName }}</p>
</div>
</body>
</html>
