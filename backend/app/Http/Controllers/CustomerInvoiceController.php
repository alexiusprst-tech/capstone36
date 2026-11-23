<?php

namespace App\Http\Controllers;

use App\Models\InvoiceDelivery;
use App\Models\SalesOrder;
use App\Services\InvoiceEmailSender;
use Illuminate\Http\Response;

class CustomerInvoiceController extends Controller
{
    public function __invoke(string $token): Response
    {
        $headers = [
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
        $delivery = strlen($token) === 64 && ctype_xdigit($token)
            ? InvoiceDelivery::active()->with(['order', 'customer:id,email'])->where('token_hash', hash('sha256', $token))->first()
            : null;
        $order = $delivery?->order;
        if (! $order || ! $delivery->customer
            || ! InvoiceEmailSender::recipientMatches($delivery, $delivery->customer->email)
            || (int) $delivery->customer_id !== (int) $order->customer_id
            || $delivery->invoice_number !== InvoiceDelivery::invoiceNumberFor($order)) {
            return response('Tautan invoice tidak tersedia.', 404, $headers);
        }

        return response()->view('invoice.customer', ['invoice' => $this->invoiceData($order)], 200, $headers);
    }

    private function invoiceData(SalesOrder $order): array
    {
        $invoice = $order->invoice;
        $total = $invoice['totalAmount'] ?? $order->grand_total;
        $methods = ['Virtual Account', 'QRIS'];

        return [
            'invoiceNumber' => InvoiceDelivery::invoiceNumberFor($order),
            'orderNumber' => $order->order_number,
            'clientName' => $invoice['clientName'] ?? $order->details['clientName'] ?? '',
            'invoiceDate' => $invoice['invoiceDate'] ?? null,
            'dueDate' => $invoice['dueDate'] ?? null,
            'totalAmount' => $total,
            'remainingAmount' => $invoice['remainingAmount'] ?? (isset($invoice['paidAmount']) ? max(0, $total - $invoice['paidAmount']) : null),
            'status' => $invoice['status'] ?? null,
            'paymentMethods' => array_values(array_intersect($methods, $invoice['paymentMethods'] ?? $methods)),
            'paymentInstruction' => null,
            'items' => array_map(static fn (array $item) => [
                'name' => $item['name'] ?? null, 'qty' => $item['qty'] ?? null,
                'unit' => $item['unit'] ?? null, 'price' => $item['price'] ?? null,
                'total' => $item['total'] ?? (isset($item['qty'], $item['price']) ? $item['qty'] * (int) round($item['price'] * 100) / 100 : null),
            ], $invoice['items'] ?? $order->items),
        ];
    }
}
