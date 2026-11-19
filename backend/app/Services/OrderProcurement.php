<?php

namespace App\Services;

use App\Models\PurchaseTransaction;
use App\Models\WarehouseRequest;

class OrderProcurement
{
    /** Fetch once for the entire orders list; no database reads inside its item loop. */
    public function summaries(array $orderIds): array
    {
        $requests = WarehouseRequest::with('order')->whereIn('sales_order_id', $orderIds)->get();
        $purchases = PurchaseTransaction::with('receipts.lines')->whereIn('warehouse_request_id', $requests->pluck('id'))->get()->keyBy('warehouse_request_id');
        $result = [];
        foreach ($orderIds as $id) {
            $result[$id] = ['requests' => [], 'pending' => []];
        }
        foreach ($requests as $record) {
            $id = $record->sales_order_id;
            $result[$id]['requests'][] = $record->toArray();
            if (in_array($record->status, ['DIBATALKAN', 'SELESAI'], true)) {
                continue;
            }
            $received = collect($purchases->get($record->id)?->receiptSummary() ?? [])->keyBy('sku');
            foreach ($record->items as $line) {
                $sku = $line['sku'];
                $result[$id]['pending'][$sku] = ($result[$id]['pending'][$sku] ?? 0) + max(0, (int) $line['qty'] - ($received->get($sku)['accepted_qty'] ?? 0));
            }
        }

        return $result;
    }
}
