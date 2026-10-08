<table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="margin-top:16px;border-top:1px solid #e2e8f0;font-size:14px;">
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->name }}@if($item->variant_label) ({{ $item->variant_label }})@endif × {{ $item->quantity }}</td>
            <td style="text-align:left;" dir="ltr">{{ money($item->line_total) }}</td>
        </tr>
    @endforeach
    <tr><td style="border-top:1px solid #e2e8f0;color:#64748b;">الشحن</td><td style="border-top:1px solid #e2e8f0;text-align:left;" dir="ltr">{{ money($order->shipping_cost) }}</td></tr>
    <tr><td style="color:#64748b;">الضريبة</td><td style="text-align:left;" dir="ltr">{{ money($order->tax_total) }}</td></tr>
    @if($order->coupon_discount > 0)<tr><td style="color:#059669;">الكوبون</td><td style="text-align:left;color:#059669;" dir="ltr">-{{ money($order->coupon_discount) }}</td></tr>@endif
    <tr><td style="font-weight:bold;font-size:16px;">الإجمالي</td><td style="text-align:left;font-weight:bold;font-size:16px;" dir="ltr">{{ money($order->grand_total) }}</td></tr>
</table>
