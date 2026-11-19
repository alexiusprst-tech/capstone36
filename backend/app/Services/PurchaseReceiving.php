<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\WarehouseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReceiving
{
    public function receive(Request $request, string $identifier): JsonResponse
    {
        $data = $request->validate([
            'version' => 'required|integer|min:0', 'idempotency_key' => 'required|uuid',
            'received_date' => 'nullable|date|before_or_equal:'.now(config('app.business_timezone'))->toDateString(), 'notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1|max:200', 'items.*.line_index' => 'required|integer|min:0|max:199|distinct',
            'items.*.accepted_qty' => 'required|integer|min:0|max:1000000', 'items.*.rejected_qty' => 'required|integer|min:0|max:1000000',
            'items.*.reason' => 'nullable|string|max:2000',
        ]);
        // Normalize only business values. The same payload may be retried with its original stale version.
        $lines = collect($data['items'])->sortBy('line_index')->map(fn ($line) => ['line_index' => (int) $line['line_index'],
            'accepted_qty' => (int) $line['accepted_qty'], 'rejected_qty' => (int) $line['rejected_qty'], 'reason' => trim($line['reason'] ?? '')])->values()->all();
        $hash = hash('sha256', json_encode(['items' => $lines, 'received_date' => $data['received_date'] ?? null, 'notes' => trim($data['notes'] ?? '')]));

        return InventoryTransaction::run(function () use ($request, $identifier, $data, $lines, $hash) {
            $byCode = PurchaseTransaction::where('purchase_code', $identifier)->lockForUpdate()->first();
            $byId = ctype_digit($identifier) ? PurchaseTransaction::whereKey($identifier)->lockForUpdate()->first() : null;
            abort_if($byCode && $byId && $byCode->id !== $byId->id, 409, 'Referensi PO ambigu. Gunakan kode PO yang tidak bertabrakan dengan ID.');
            $purchase = $byCode ?? $byId;
            abort_unless($purchase, 404, 'PO tidak ditemukan.');
            $purchase->load('receipts.lines');
            $existing = $purchase->receipts->firstWhere('idempotency_key', $data['idempotency_key']);
            if ($existing) {
                abort_unless(hash_equals($existing->payload_hash, $hash), 409, 'Kunci penerimaan sudah digunakan untuk data berbeda.');

                return response()->json(['success' => true, 'data' => ['purchase' => $purchase, 'receipt' => $existing, 'items_received' => [], 'replayed' => true]]);
            }
            abort_unless((int) $purchase->progress_version === (int) $data['version'], 409, 'PO berubah. Muat ulang sebelum mencatat penerimaan.');
            abort_if($purchase->status === 'DITERIMA_GUDANG', 409, 'PO sudah diterima seluruhnya.');
            abort_if(in_array($purchase->status, ['DIBATALKAN', 'DITOLAK', 'CANCELLED'], true), 409, 'PO dibatalkan atau ditolak dan tidak dapat diterima.');
            abort_if($purchase->pending_followup || in_array($purchase->supplier_progress['supplier_status'] ?? '', ['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian', 'Ditolak Supplier'], true), 409, 'Finance harus menyelesaikan tindak lanjut Supplier terlebih dahulu.');
            $summary = collect($purchase->receiptSummary())->keyBy('line_index');
            $positive = 0;
            $rejected = 0;
            $products = [];
            foreach ($lines as $line) {
                $expected = $summary->get($line['line_index']);
                abort_unless($expected, 422, 'Baris penerimaan tidak ada pada PO.');
                abort_if($expected['remaining_qty'] < $line['accepted_qty'] + $line['rejected_qty'], 422, 'Jumlah diperiksa melebihi sisa barang pada PO.');
                abort_if($line['rejected_qty'] > 0 && $line['reason'] === '', 422, 'Alasan wajib untuk barang yang ditolak.');
                $product = Product::where('sku', $expected['sku'])->lockForUpdate()->first();
                abort_unless($product, 422, 'SKU PO belum terhubung ke katalog aktif. Perbaiki master barang terlebih dahulu.');
                $products[$line['line_index']] = $product;
                $positive += $line['accepted_qty'] + $line['rejected_qty'];
                $rejected += $line['rejected_qty'];
            }
            abort_if($positive === 0, 422, 'Isi setidaknya satu barang yang diterima atau ditolak.');
            $receipt = $purchase->receipts()->create([
                'idempotency_key' => $data['idempotency_key'], 'payload_hash' => $hash, 'received_date' => $data['received_date'] ?? now(config('app.business_timezone'))->toDateString(),
                'received_by_id' => $request->user()->id, 'notes' => trim($data['notes'] ?? ''),
            ]);
            $received = [];
            foreach ($lines as $line) {
                if ($line['accepted_qty'] + $line['rejected_qty'] === 0) {
                    continue;
                }
                $expected = $summary[$line['line_index']];
                $product = $products[$line['line_index']];
                $receipt->lines()->create([...$line, 'product_id' => $product->id, 'sku' => $product->sku, 'name' => $expected['name'], 'unit' => $expected['unit']]);
                if ($line['accepted_qty'] > 0) {
                    // Refresh also protects legacy duplicate SKU lines in the same PO.
                    $product->refresh();
                    $before = (int) $product->stock;
                    $after = $before + $line['accepted_qty'];
                    abort_if($after > 1_000_000_000, 422, 'Stok fisik melebihi batas sistem.');
                    $product->update(['stock' => $after, 'status' => Product::stockStatus($after, (int) $product->min_stock)]);
                    StockLedger::record($product, $before, $after, $request->user(), 'RECEIPT', 'purchase_receipt', (string) $receipt->id, 'Penerimaan '.$purchase->purchase_code);
                    $received[] = ['sku' => $product->sku, 'name' => $product->name, 'qty_received' => $line['accepted_qty'], 'current_stock' => $after];
                }
            }
            $purchase->unsetRelation('receipts')->load('receipts.lines');
            $remaining = collect($purchase->receiptSummary())->sum('remaining_qty');
            $beforeProgress = $purchase->supplier_progress ?? [];
            $purchase->pending_followup = $rejected > 0;
            $purchase->status = $rejected > 0 ? 'PERLU_TINDAK_LANJUT' : ($remaining === 0 ? 'DITERIMA_GUDANG' : 'DITERIMA_SEBAGIAN');
            $purchase->supplier_progress = [...$beforeProgress, 'supplier_status' => $rejected > 0 ? 'Ketidaksesuaian Fisik (Menunggu Retur)' : ($remaining === 0 ? 'Diterima Gudang (Lengkap & Sesuai)' : 'Diterima Sebagian'),
                'warehouse_arrived' => true, 'received_at' => $receipt->received_date];
            $purchase->progress_version++;
            $purchase->progress_history = [...($purchase->progress_history ?? []), [
                'action' => 'receipt', 'receipt_id' => $receipt->id, 'version' => $purchase->progress_version,
                'user_id' => $request->user()->id, 'user' => $request->user()->name, 'role' => 'WAREHOUSE', 'timestamp' => now()->toIso8601String(),
                'before' => $beforeProgress, 'after' => $purchase->supplier_progress,
            ]];
            $purchase->save();
            $mr = WarehouseRequest::whereKey($purchase->warehouse_request_id)->first();
            if ($mr) {
                $mr->update(['status' => $purchase->pending_followup ? 'PERLU_TINDAK_LANJUT' : ($remaining === 0 ? 'SELESAI' : 'DITERIMA_SEBAGIAN')]);
            }
            app(SalesOrderInventory::class)->reallocateDrafts($mr?->sales_order_id);

            return response()->json(['success' => true, 'data' => ['purchase' => $purchase, 'receipt' => $receipt->load('lines'), 'items_received' => $received]]);
        });
    }
}
