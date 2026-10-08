<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class InvoiceService
{
    public function ensureInvoice(Order $order): Invoice
    {
        return Invoice::firstOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => 'INV-'.now()->format('Y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'issued_at' => now(),
            ],
        );
    }

    public function viewData(Order $order): array
    {
        $order->loadMissing(['items', 'invoice', 'user']);
        $invoice = $order->invoice ?? $this->ensureInvoice($order);

        $logo = Setting::get('logo');

        return [
            'order' => $order,
            'invoice' => $invoice,
            'storeName' => Setting::get('store_name'),
            'logoPath' => $logo && Storage::disk('public')->exists($logo) ? Storage::disk('public')->path($logo) : null,
            'logoUrl' => Setting::logoUrl(),
            'vatNumber' => Setting::get('vat_number'),
            'storeAddress' => Setting::get('contact_address'),
            'storePhone' => Setting::get('contact_phone'),
            'storeEmail' => Setting::get('contact_email'),
        ];
    }

    public function html(Order $order, bool $forPdf = false): string
    {
        return view('invoices.show', $this->viewData($order) + ['forPdf' => $forPdf])->render();
    }

    public function pdf(Order $order): string
    {
        $tempDir = storage_path('app/mpdf');
        is_dir($tempDir) || mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $mpdf->SetTitle('فاتورة '.$order->order_number);
        $mpdf->WriteHTML($this->html($order, true));

        return $mpdf->Output('', 'S');
    }
}
