<?php

namespace Tests\Support;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\WarehouseRequest;

trait ProcurementFixture
{
    private function linkedRequest(array $items, string $supplier = 'Supplier Uji', string $code = 'REQ-FIXTURE'): WarehouseRequest
    {
        Supplier::firstOrCreate(['name' => $supplier], ['code' => 'SUP-'.substr(hash('sha256', $supplier), 0, 12), 'is_active' => true]);
        foreach ($items as $item) {
            Product::firstOrCreate(['sku' => $item['sku']], ['name' => $item['name'] ?? $item['barang'] ?? 'Barang', 'unit' => $item['unit'] ?? 'UNIT', 'category' => 'QA', 'stock' => 0]);
        }
        $order = SalesOrder::create(['order_number' => 'ORD-'.$code, 'created_by' => auth()->id(), 'details' => ['projectName' => 'Proyek fixture'], 'items' => $items, 'history' => [], 'subtotal' => 0, 'tax' => 0, 'grand_total' => 0]);

        return WarehouseRequest::create(['request_code' => $code, 'request_date' => now()->toDateString(), 'sales_order_id' => $order->id,
            'order_items_hash' => WarehouseRequest::itemsHash($items), 'requested_by' => 'Warehouse QA', 'status' => 'MENUNGGU_FINANCE',
            'items' => collect($items)->map(fn ($line) => [...$line, 'name' => $line['name'] ?? $line['barang'] ?? 'Barang', 'unit' => $line['unit'] ?? 'UNIT'])->all()]);
    }
}
