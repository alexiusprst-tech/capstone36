<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use App\Models\WarehouseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * List all suppliers
     */
    public function getSuppliers(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $query = Supplier::query()->where('is_active', true);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Create a new supplier
     */
    public function storeSupplier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
        ]);

        $code = 'SUP'.str_pad((string) (Supplier::count() + 1), 5, '0', STR_PAD_LEFT);

        $supplier = Supplier::create([
            'code' => $code,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil ditambahkan.',
            'data' => $supplier,
        ], 201);
    }

    /**
     * List all warehouse requests
     */
    public function getWarehouseRequests(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = WarehouseRequest::query();

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('request_code', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Create a new warehouse request
     */
    public function storeWarehouseRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|string|max:100',
            'project_name' => 'nullable|string|max:255',
            'purpose' => 'required|string|max:500',
            'requested_by' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'nullable|string',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit' => 'nullable|string',
            'items.*.est_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $nextId = WarehouseRequest::count() + 1;
        $requestCode = 'REQ-2026-'.str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

        $totalEstCost = 0;
        $items = [];
        foreach ($validated['items'] as $item) {
            $qty = (float) $item['qty'];
            $price = (float) ($item['est_price'] ?? 0);
            $subtotal = $qty * $price;
            $totalEstCost += $subtotal;

            $items[] = [
                'sku' => $item['sku'] ?? '-',
                'name' => $item['name'],
                'qty' => $qty,
                'unit' => $item['unit'] ?? 'UNIT',
                'est_price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        $reqRecord = WarehouseRequest::create([
            'request_code' => $requestCode,
            'request_date' => now()->toDateString(),
            'project_id' => $validated['project_id'] ?? null,
            'project_name' => $validated['project_name'] ?? 'Pengadaan Material Bebas',
            'purpose' => $validated['purpose'],
            'requested_by' => $validated['requested_by'],
            'status' => 'MENUNGGU_FINANCE',
            'total_est_cost' => $totalEstCost,
            'items' => $items,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan kebutuhan barang berhasil diajukan ke Finance.',
            'data' => $reqRecord,
        ], 201);
    }

    /**
     * List all purchase transactions
     */
    public function getPurchases(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $query = PurchaseTransaction::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_code', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('project_name', 'like', "%{$search}%")
                    ->orWhere('default_supplier', 'like', "%{$search}%");
            });
        }

        $purchases = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    /**
     * Create a purchase transaction matching the user's form
     */
    public function storePurchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_code' => 'required|string|unique:purchase_transactions,purchase_code',
            'transaction_date' => 'required|string',
            'invoice_number' => 'nullable|string|max:100',
            'project_id' => 'nullable|string|max:100',
            'project_name' => 'required|string|max:255',
            'default_supplier' => 'nullable|string|max:255',
            'warehouse_request_id' => 'nullable|integer',
            'warehouse_request_code' => 'nullable|string|max:100',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'created_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.supplier' => 'nullable|string',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'items.*.diskon_percent' => 'nullable|numeric|min:0',
            'items.*.diskon_nominal' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $purchase = PurchaseTransaction::create([
                'purchase_code' => $validated['purchase_code'],
                'transaction_date' => $validated['transaction_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'project_id' => $validated['project_id'] ?? null,
                'project_name' => $validated['project_name'],
                'default_supplier' => $validated['default_supplier'] ?? null,
                'warehouse_request_id' => $validated['warehouse_request_id'] ?? null,
                'warehouse_request_code' => $validated['warehouse_request_code'] ?? null,
                'subtotal' => $validated['subtotal'],
                'discount' => $validated['discount'] ?? 0,
                'total_price' => $validated['total_price'],
                'payment_method' => $validated['payment_method'],
                'status' => 'LUNAS',
                'items' => $validated['items'],
                'created_by' => $validated['created_by'] ?? 'Dewi Lestari (Finance)',
                'notes' => $validated['notes'] ?? null,
            ]);

            // If linked to a warehouse request, update the request status
            if (! empty($validated['warehouse_request_id'])) {
                WarehouseRequest::where('id', $validated['warehouse_request_id'])
                    ->update([
                        'status' => 'DIPROSES_PEMBELIAN',
                        'purchase_code' => $validated['purchase_code'],
                    ]);
            } elseif (! empty($validated['warehouse_request_code'])) {
                WarehouseRequest::where('request_code', $validated['warehouse_request_code'])
                    ->update([
                        'status' => 'DIPROSES_PEMBELIAN',
                        'purchase_code' => $validated['purchase_code'],
                    ]);
            }

            // Update product stock and last buy price in database
            foreach ($validated['items'] as $item) {
                $sku = $item['sku'] ?? null;
                $hargaBeli = (float) $item['harga_beli'];
                $qty = (float) $item['qty'];

                if (! empty($sku)) {
                    $prod = Product::where('sku', $sku)->first();
                    if ($prod) {
                        $newStock = $prod->stock + $qty;
                        $updateData = [
                            'stock' => $newStock,
                            'status' => $newStock > 10 ? 'Tersedia' : ($newStock > 0 ? 'Menipis' : 'Habis'),
                        ];
                        if ($hargaBeli > 0) {
                            $updateData['last_buy_price'] = $hargaBeli;
                            if ($prod->hpp <= 0) {
                                $updateData['hpp'] = $hargaBeli;
                            }
                        }
                        $prod->update($updateData);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembelian '.$purchase->purchase_code.' berhasil disimpan.',
                'data' => $purchase,
            ], 201);
        });
    }
}
