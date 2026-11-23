<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\WarehouseRequest;
use App\Services\InventoryTransaction;
use App\Services\OrderProcurement;
use App\Services\PurchaseReceiving;
use App\Services\SalesOrderInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function getWarehouseRequests(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => 'nullable|string|max:255', 'status' => 'nullable|string|max:50']);
        $query = WarehouseRequest::with('order');
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['search'])) {
            $query->where(function ($q) use ($data) {
                $q->where('request_code', 'like', '%'.$data['search'].'%')->orWhere('project_name', 'like', '%'.$data['search'].'%');
            });
        }

        return response()->json(['success' => true, 'data' => $query->latest('id')->get()]);
    }

    public function storeWarehouseRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sales_order_id' => 'required_without:order_number|nullable|integer',
            'order_number' => 'required_without:sales_order_id|nullable|string|max:80',
            'purpose' => 'required|string|max:500', 'notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1|max:200', 'items.*.sku' => 'required|string|max:50|distinct:ignore_case',
            'items.*.qty' => 'required|integer|min:1|max:1000000',
            'items.*.est_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000',
            'items.*.est_discount' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000',
            'procurement_draft' => 'sometimes|required|array:transaction_date,invoice_number,supplier_id,payment_method,discount',
            'procurement_draft.transaction_date' => 'required_with:procurement_draft|date_format:Y-m-d',
            'procurement_draft.invoice_number' => 'nullable|string|max:100',
            'procurement_draft.supplier_id' => 'required_with:procurement_draft|integer',
            'procurement_draft.payment_method' => 'required_with:procurement_draft|in:Tunai,Transfer,Termin',
            'procurement_draft.discount' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
        ]);
        $record = InventoryTransaction::run(function () use ($request, $data) {
            $order = SalesOrder::query()->when(! empty($data['sales_order_id']), fn ($q) => $q->whereKey($data['sales_order_id']), fn ($q) => $q->where('order_number', $data['order_number']))->lockForUpdate()->first();
            abort_unless($order && $order->status === 'Draft', 422, 'Pilih pesanan Draft yang tersimpan di server.');
            abort_if(! empty($data['order_number']) && $order->order_number !== $data['order_number'], 422, 'Referensi pesanan tidak sama.');
            $inventory = app(SalesOrderInventory::class);
            $inventory->allocate($order);
            $needs = collect($inventory->summaries(collect([$order]))[$order->id]['items'])->keyBy('sku');
            $pending = $this->pendingQuantities($order->id);
            $draft = null;
            if (array_key_exists('procurement_draft', $data)) {
                $supplier = Supplier::whereKey($data['procurement_draft']['supplier_id'])->where('is_active', true)->lockForUpdate()->first();
                abort_unless($supplier, 422, 'Pilih supplier aktif yang terdaftar.');
                $draft = [
                    'purchase_code' => 'PB'.now(config('app.business_timezone'))->format('Ymd').strtoupper((string) Str::ulid()),
                    'transaction_date' => $data['procurement_draft']['transaction_date'],
                    'invoice_number' => trim($data['procurement_draft']['invoice_number'] ?? ''),
                    'supplier_id' => $supplier->id, 'default_supplier' => $supplier->name,
                    'payment_method' => $data['procurement_draft']['payment_method'],
                ];
            }
            $items = [];
            $total = 0;
            foreach ($data['items'] as $line) {
                $need = $needs->get($line['sku']);
                abort_unless($need && $need['catalogFound'], 422, 'SKU harus terdaftar pada pesanan dan katalog.');
                $availableNeed = max(0, $need['shortage'] - ($pending[$line['sku']] ?? 0));
                abort_if((int) $line['qty'] > $availableNeed, 422, 'Jumlah pengadaan melebihi kekurangan yang belum diajukan. Muat ulang pesanan.');
                $price = (int) round(($line['est_price'] ?? 0) * 100);
                $lineDiscount = (int) round(($line['est_discount'] ?? 0) * 100);
                abort_if($lineDiscount > $price, 422, 'Diskon per satuan tidak boleh melebihi harga barang.');
                $subtotal = (int) $line['qty'] * ($price - $lineDiscount);
                $total += $subtotal;
                abort_if($total > 100_000_000_000_000, 422, 'Estimasi biaya melebihi batas sistem.');
                $items[] = ['sku' => $line['sku'], 'name' => $need['name'], 'unit' => $need['unit'], 'qty' => (int) $line['qty'], 'est_price' => $price / 100, 'est_discount' => $lineDiscount / 100, 'subtotal' => $subtotal / 100];
            }
            if ($draft !== null) {
                $transactionDiscount = (int) round(($data['procurement_draft']['discount'] ?? 0) * 100);
                abort_if($transactionDiscount > $total, 422, 'Diskon transaksi tidak boleh melebihi jumlah harga barang.');
                $draft['subtotal'] = $total / 100;
                $draft['discount'] = $transactionDiscount / 100;
                $total -= $transactionDiscount;
                $draft['total_price'] = $total / 100;
            }

            return WarehouseRequest::create([
                'request_code' => 'REQ-'.now(config('app.business_timezone'))->year.'-'.strtoupper((string) Str::ulid()), 'request_date' => now(config('app.business_timezone'))->toDateString(),
                'sales_order_id' => $order->id, 'order_items_hash' => WarehouseRequest::itemsHash($order->items),
                'requested_by_id' => $request->user()->id, 'requested_by' => $request->user()->name,
                'project_id' => $order->details['projectId'] ?? null, 'project_name' => $order->details['projectName'],
                'purpose' => $data['purpose'], 'status' => 'MENUNGGU_FINANCE', 'total_est_cost' => $total / 100,
                'items' => $items, 'notes' => $data['notes'] ?? null,
                'procurement_draft' => $draft,
                'purchase_code' => null,
            ])->load('order');
        });

        return response()->json(['success' => true, 'data' => $record], 201);
    }

    public function cancelWarehouseRequest(string $code): JsonResponse
    {
        $record = InventoryTransaction::run(function () use ($code) {
            $record = WarehouseRequest::where('request_code', $code)->lockForUpdate()->firstOrFail();
            abort_if($record->purchase_code || PurchaseTransaction::where('warehouse_request_id', $record->id)->exists(), 409, 'Pengajuan sudah memiliki PO dan tidak dapat dibatalkan.');
            abort_if($record->status === 'DIBATALKAN', 409, 'Pengajuan sudah dibatalkan.');
            $record->update(['status' => 'DIBATALKAN']);

            return $record->load('order');
        });

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function pendingQuantities(int $orderId): array
    {
        return app(OrderProcurement::class)->summaries([$orderId])[$orderId]['pending'];
    }

    public function getPurchases(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validate(['search' => 'nullable|string|max:255']);
            $query = PurchaseTransaction::with('receipts.lines')->sharedLock();
            if (strtoupper((string) $request->user()->role) === 'SUPPLIER') {
                $supplier = Supplier::whereRaw('LOWER(email) = ?', [strtolower($request->user()->email)])->first();
                $supplier ? $query->where(fn ($q) => $q->where('supplier_id', $supplier->id)->orWhere(fn ($legacy) => $legacy->whereNull('supplier_id')->where('default_supplier', $supplier->name))) : $query->whereRaw('1 = 0');
            }
            if (! empty($data['search'])) {
                $query->where(function ($q) use ($data) {
                    foreach (['purchase_code', 'invoice_number', 'project_name', 'default_supplier'] as $field) {
                        $q->orWhere($field, 'like', '%'.$data['search'].'%');
                    }
                });
            }
            $purchases = $query->latest('id')->get();
            $orders = WarehouseRequest::with('order')->whereIn('id', $purchases->pluck('warehouse_request_id'))->get()->keyBy('id');
            foreach ($purchases as $purchase) {
                $purchase->setAttribute('order_number', $orders->get($purchase->warehouse_request_id)?->order?->order_number);
            }

            return response()->json(['success' => true, 'data' => $purchases]);
        });
    }

    public function storePurchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_code' => 'nullable|string|max:80|regex:/^[A-Za-z][A-Za-z0-9-]{0,79}$/|unique:purchase_transactions,purchase_code', 'transaction_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:100', 'supplier_id' => 'nullable|integer', 'default_supplier' => 'nullable|string|max:255',
            'warehouse_request_id' => 'required_without:warehouse_request_code|nullable|integer', 'warehouse_request_code' => 'required_without:warehouse_request_id|nullable|string|max:100',
            'discount' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000', 'payment_method' => 'required|string|max:50', 'notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1|max:200', 'items.*.sku' => 'required|string|max:50|distinct:ignore_case',
            'items.*.barang' => 'nullable|string|max:300', 'items.*.unit' => 'nullable|string|max:50', 'items.*.supplier' => 'nullable|string|max:255',
            'items.*.qty' => 'required|integer|min:1|max:1000000', 'items.*.harga_beli' => 'required|numeric|decimal:0,2|min:0|max:1000000000',
            'items.*.diskon_percent' => 'nullable|numeric|min:0|max:100', 'items.*.diskon_nominal' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000',
        ]);
        $purchase = InventoryTransaction::run(function () use ($data, $request) {
            abort_if(! empty($data['purchase_code']) && PurchaseTransaction::where('purchase_code', $data['purchase_code'])->exists(), 409, 'Kode PO sudah digunakan. Muat ulang sebelum menerbitkan PO.');
            $mr = WarehouseRequest::query()->when(! empty($data['warehouse_request_id']), fn ($q) => $q->whereKey($data['warehouse_request_id']), fn ($q) => $q->where('request_code', $data['warehouse_request_code']))->lockForUpdate()->first();
            abort_unless($mr, 422, 'Pengajuan Gudang tidak ditemukan.');
            abort_if($mr->purchase_code || PurchaseTransaction::where('warehouse_request_id', $mr->id)->exists(), 409, 'Pengajuan Gudang sudah diterbitkan PO.');
            abort_if(! empty($data['warehouse_request_code']) && $data['warehouse_request_code'] !== $mr->request_code, 422, 'Referensi pengajuan tidak sama.');
            $order = SalesOrder::whereKey($mr->sales_order_id)->lockForUpdate()->first();
            abort_unless($order && $order->status === 'Draft' && $mr->status === 'MENUNGGU_FINANCE' && $mr->order_items_hash === WarehouseRequest::itemsHash($order->items), 422, 'Kebutuhan pesanan berubah atau pengajuan lama belum terhubung. Batalkan lalu ajukan kembali dari pesanan.');
            $supplierQuery = Supplier::where('is_active', true);
            ! empty($data['supplier_id']) ? $supplierQuery->whereKey($data['supplier_id']) : $supplierQuery->where('name', $data['default_supplier'] ?? '');
            $suppliers = $supplierQuery->lockForUpdate()->get();
            abort_unless($suppliers->count() === 1, 422, 'Pilih satu Supplier aktif yang terdaftar.');
            $supplier = $suppliers->first();
            abort_if(! empty($data['default_supplier']) && $data['default_supplier'] !== $supplier->name, 422, 'Supplier tidak sama dengan pilihan master.');
            $expected = collect($mr->items)->keyBy('sku');
            app(SalesOrderInventory::class)->allocate($order);
            $shortages = collect(app(SalesOrderInventory::class)->summaries(collect([$order]))[$order->id]['items'])->keyBy('sku');
            foreach ($expected as $line) {
                abort_if((int) $line['qty'] > ($shortages->get($line['sku'])['shortage'] ?? 0), 422, 'Kekurangan stok sudah berubah. Warehouse perlu membatalkan dan menyesuaikan pengajuan.');
            }
            abort_unless(count($data['items']) === $expected->count(), 422, 'PO harus memuat seluruh barang dari pengajuan Gudang.');
            $items = [];
            $subtotal = 0;
            foreach ($data['items'] as $line) {
                $required = $expected->get($line['sku']);
                abort_unless($required && (int) $required['qty'] === (int) $line['qty'], 422, 'SKU dan jumlah PO harus sama dengan pengajuan Gudang.');
                abort_if(isset($line['unit']) && $line['unit'] !== $required['unit'], 422, 'Satuan PO tidak sama dengan pengajuan.');
                abort_if(! empty($line['supplier']) && $line['supplier'] !== $supplier->name, 422, 'Satu PO hanya untuk Supplier yang dipilih.');
                $product = Product::where('sku', $line['sku'])->lockForUpdate()->first();
                abort_unless($product, 422, 'Barang pengajuan tidak tersedia pada katalog aktif.');
                $price = (int) round($line['harga_beli'] * 100);
                $discount = isset($line['diskon_nominal']) ? (int) round($line['diskon_nominal'] * 100) : (int) round($price * ($line['diskon_percent'] ?? 0) / 100);
                abort_if($discount > $price, 422, 'Diskon per unit tidak boleh melebihi harga beli.');
                $value = (int) $line['qty'] * ($price - $discount);
                $subtotal += $value;
                abort_if($subtotal > 100_000_000_000_000, 422, 'Nilai pembelian melebihi batas sistem.');
                $items[] = ['sku' => $product->sku, 'barang' => $required['name'], 'unit' => $required['unit'], 'supplier' => $supplier->name,
                    'qty' => (int) $line['qty'], 'harga_beli' => $price / 100, 'diskon_nominal' => $discount / 100,
                    'diskon_percent' => $price ? $discount * 100 / $price : 0, 'subtotal' => $value / 100];
                $product->update(['last_buy_price' => $price / 100, 'hpp' => $product->hpp > 0 ? $product->hpp : $price / 100]);
            }
            $discount = (int) round(($data['discount'] ?? 0) * 100);
            abort_if($discount > $subtotal, 422, 'Diskon transaksi melebihi subtotal.');
            $purchase = PurchaseTransaction::create([
                'purchase_code' => $data['purchase_code'] ?? 'PO-'.strtoupper((string) Str::ulid()), 'transaction_date' => $data['transaction_date'],
                'invoice_number' => $data['invoice_number'] ?? null, 'project_id' => $mr->project_id, 'project_name' => $mr->project_name,
                'supplier_id' => $supplier->id, 'default_supplier' => $supplier->name, 'warehouse_request_id' => $mr->id, 'warehouse_request_code' => $mr->request_code,
                'subtotal' => $subtotal / 100, 'discount' => $discount / 100, 'total_price' => ($subtotal - $discount) / 100,
                'payment_method' => $data['payment_method'], 'status' => 'DIPESAN', 'supplier_progress' => ['supplier_status' => 'Menunggu Konfirmasi'],
                'progress_version' => 1, 'items' => $items, 'created_by' => $request->user()->name, 'notes' => $data['notes'] ?? null,
            ]);
            $mr->update(['status' => 'DIPROSES_PEMBELIAN', 'purchase_code' => $purchase->purchase_code]);

            return $purchase->load('receipts.lines');
        });

        return response()->json(['success' => true, 'data' => $purchase], 201);
    }

    public function receivePurchaseShipment(Request $request, string $id): JsonResponse
    {
        return app(PurchaseReceiving::class)->receive($request, $id);
    }
}
