<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\InvoiceService;

/** Staff invoice printing / PDF download. */
class AdminInvoiceController extends Controller
{
    public function show(Order $order, InvoiceService $invoices)
    {
        $this->authorize('view', $order);

        return response($invoices->html($order));
    }

    public function pdf(Order $order, InvoiceService $invoices)
    {
        $this->authorize('view', $order);

        return response($invoices->pdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$order->order_number.'.pdf"',
        ]);
    }
}
