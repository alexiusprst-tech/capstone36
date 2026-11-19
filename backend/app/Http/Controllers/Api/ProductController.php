<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\PurchaseTransaction;
use App\Models\WarehouseRequest;
use App\Services\InventoryTransaction;
use App\Services\SalesOrderInventory;
use App\Services\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    /**
     * Get paginated products with search, filtering, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $unit = $request->query('unit');
        $status = $request->query('status');
        $sortBy = $request->query('sort_by', 'sku');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->query('per_page', 10);

        // Allowed sort columns to prevent SQL injection
        $allowedSorts = ['id', 'sku', 'name', 'category', 'unit', 'stock', 'last_buy_price', 'hpp', 'sell_price', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'sku';
        }

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category && $category !== 'Semua Kategori') {
            $query->where('category', $category);
        }

        if ($unit && $unit !== 'Semua Satuan') {
            $query->where('unit', $unit);
        }

        if ($status && $status !== 'Semua Status') {
            $query->where('status', $status);
        }

        $totalStock = (clone $query)->sum('stock');
        $totalItems = (clone $query)->count();

        if ($perPage === -1 || $perPage > 500) {
            $products = $query->orderBy($sortBy, $sortDir)->get();
            $paginated = [
                'current_page' => 1,
                'data' => $products,
                'from' => 1,
                'last_page' => 1,
                'per_page' => $totalItems,
                'to' => $totalItems,
                'total' => $totalItems,
            ];
        } else {
            $paginated = $query->orderBy($sortBy, $sortDir)->paginate(max(1, $perPage));
        }

        $items = is_array($paginated) ? $paginated['data'] : $paginated->items();
        $currentPage = is_array($paginated) ? $paginated['current_page'] : $paginated->currentPage();
        $lastPage = is_array($paginated) ? $paginated['last_page'] : $paginated->lastPage();
        $perPageCount = is_array($paginated) ? $paginated['per_page'] : $paginated->perPage();
        $from = is_array($paginated) ? $paginated['from'] : ($paginated->firstItem() ?? 0);
        $to = is_array($paginated) ? $paginated['to'] : ($paginated->lastItem() ?? 0);
        $allocated = DB::table('sales_order_allocations')->whereIn('product_id', collect($items)->pluck('id'))
            ->groupBy('product_id')->selectRaw('product_id, SUM(qty) as allocated')->pluck('allocated', 'product_id');
        foreach ($items as $product) {
            $product->setAttribute('allocated_stock', (int) ($allocated[$product->id] ?? 0));
            $product->setAttribute('available_stock', max(0, $product->stock - ($allocated[$product->id] ?? 0)));
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPageCount,
                'total' => $totalItems,
                'from' => $from,
                'to' => $to,
                'total_stock' => (int) $totalStock,
            ],
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:150',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string',
            'stock' => 'nullable|integer|min:0|max:1000000000',
            'min_stock' => 'nullable|integer|min:0|max:1000000000',
            'last_buy_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'hpp' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'sell_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'status' => 'nullable|string|max:30',
        ]);

        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['min_stock'] = $validated['min_stock'] ?? 10;
        foreach (['last_buy_price', 'hpp', 'sell_price'] as $price) {
            $validated[$price] = $validated[$price] ?? 0;
        }
        $validated['status'] = Product::stockStatus($validated['stock'], $validated['min_stock']);
        $validated['created_by'] = auth()->user()->name ?? 'Pengguna';
        $validated['updated_by'] = auth()->user()->name ?? 'Pengguna';

        $product = InventoryTransaction::run(function () use ($validated) {
            abort_if(Product::withTrashed()->where('sku', $validated['sku'])->exists(), 409, 'SKU sudah digunakan. Muat ulang katalog sebelum menambah barang.');
            ProductCategory::firstOrCreate(['name' => $validated['category']]);
            ProductUnit::firstOrCreate(['name' => $validated['unit']]);
            $product = Product::create($validated);
            StockLedger::record($product, 0, $product->stock, auth()->user(), 'OPENING', 'product', (string) $product->id, 'Saldo awal barang.');
            app(SalesOrderInventory::class)->reallocateDrafts();

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan.',
            'data' => $product,
        ], 201);
    }

    /**
     * Show a product.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku' => "required|string|max:50|unique:products,sku,{$id}",
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:150',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string',
            'stock' => 'sometimes|integer|min:0|max:1000000000',
            'min_stock' => 'sometimes|integer|min:0|max:1000000000',
            'last_buy_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'hpp' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'sell_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'status' => 'nullable|string|max:30',
            'adjustment_reason' => 'nullable|string|max:1000',
        ]);

        $validated['updated_by'] = auth()->user()->name ?? 'Pengguna';
        foreach (['last_buy_price', 'hpp', 'sell_price'] as $price) {
            if (array_key_exists($price, $validated) && $validated[$price] === null) {
                $validated[$price] = 0;
            }
        }
        $reason = $validated['adjustment_reason'] ?? 'Perubahan stok melalui pengelolaan barang.';
        unset($validated['adjustment_reason']);

        InventoryTransaction::run(function () use ($product, $validated, $reason) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $allocated = (int) DB::table('sales_order_allocations')->where('product_id', $locked->id)->sum('qty');
            abort_if(($validated['stock'] ?? $locked->stock) < $allocated, 409, 'Stok fisik tidak boleh kurang dari alokasi aktif.');
            abort_if($validated['sku'] !== $locked->sku, 409, 'SKU permanen setelah barang disimpan dan tidak dapat diubah.');
            abort_if($validated['unit'] !== $locked->unit && ($allocated > 0 || $this->hasOpenProcurement($locked->sku)), 409, 'Satuan barang tidak dapat diubah selama ada alokasi atau pengadaan aktif.');
            ProductCategory::firstOrCreate(['name' => $validated['category']]);
            ProductUnit::firstOrCreate(['name' => $validated['unit']]);
            $before = $locked->stock;
            $validated['status'] = Product::stockStatus($validated['stock'] ?? $locked->stock, $validated['min_stock'] ?? $locked->min_stock);
            $locked->update($validated);
            if ($before !== $locked->stock) {
                StockLedger::record($locked, $before, $locked->stock, auth()->user(), 'ADJUSTMENT', 'product', (string) $locked->id, $reason);
                app(SalesOrderInventory::class)->reallocateDrafts();
            }
        });
        $product->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil diperbarui.',
            'data' => $product,
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $sku = $product->sku;
        InventoryTransaction::run(function () use ($product) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_if(DB::table('sales_order_allocations')->where('product_id', $locked->id)->exists(), 409, 'Barang sedang dialokasikan ke pesanan dan tidak dapat dihapus.');
            abort_if($this->hasOpenProcurement($locked->sku), 409, 'Barang masih digunakan pengadaan aktif dan tidak dapat diarsipkan.');
            $locked->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Barang {$sku} berhasil diarsipkan. Riwayat stok tetap tersimpan.",
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $product = InventoryTransaction::run(function () use ($id) {
            $product = Product::onlyTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
            ProductCategory::firstOrCreate(['name' => $product->category]);
            ProductUnit::firstOrCreate(['name' => $product->unit]);
            $product->restore();
            app(SalesOrderInventory::class)->reallocateDrafts();

            return $product;
        });

        return response()->json(['success' => true, 'message' => 'Barang berhasil dipulihkan.', 'data' => $product]);
    }

    /**
     * Get categories list with counts.
     */
    public function getCategories(): JsonResponse
    {
        $categories = ProductCategory::orderBy('name')->get();

        // Count products per category
        $counts = Product::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        $result = $categories->map(function ($cat) use ($counts) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $cat->code,
                'description' => $cat->description,
                'count' => $counts[$cat->name] ?? 0,
            ];
        });

        // Add any categories present in products but not yet in categories table
        foreach ($counts as $catName => $count) {
            if (! $categories->contains('name', $catName)) {
                $created = ProductCategory::create(['name' => $catName]);
                $result->push([
                    'id' => $created->id,
                    'name' => $catName,
                    'code' => null,
                    'description' => null,
                    'count' => $count,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result->sortBy('name')->values(),
        ]);
    }

    /**
     * Store a category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:product_categories,name',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $category = ProductCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat.',
            'data' => $category,
        ], 201);
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);
        $oldName = $category->name;

        $validated = $request->validate([
            'name' => "required|string|max:150|unique:product_categories,name,{$id}",
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        if ($oldName !== $validated['name']) {
            Product::where('category', $oldName)->update(['category' => $validated['name']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category,
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);
        $productCount = Product::where('category', $category->name)->count();

        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat menghapus kategori karena masih digunakan oleh {$productCount} barang.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /**
     * Get units list with counts.
     */
    public function getUnits(): JsonResponse
    {
        $units = ProductUnit::orderBy('name')->get();

        $counts = Product::select('unit', DB::raw('count(*) as count'))
            ->groupBy('unit')
            ->pluck('count', 'unit');

        $result = $units->map(function ($u) use ($counts) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'description' => $u->description,
                'count' => $counts[$u->name] ?? 0,
            ];
        });

        foreach ($counts as $unitName => $count) {
            if (! $units->contains('name', $unitName)) {
                $created = ProductUnit::create(['name' => $unitName]);
                $result->push([
                    'id' => $created->id,
                    'name' => $unitName,
                    'description' => null,
                    'count' => $count,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result->sortBy('name')->values(),
        ]);
    }

    /**
     * Store a unit.
     */
    public function storeUnit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:product_units,name',
            'description' => 'nullable|string',
        ]);

        $unit = ProductUnit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dibuat.',
            'data' => $unit,
        ], 201);
    }

    /**
     * Update a unit.
     */
    public function updateUnit(Request $request, int $id): JsonResponse
    {
        $unit = ProductUnit::findOrFail($id);
        $oldName = $unit->name;

        $validated = $request->validate([
            'name' => "required|string|max:50|unique:product_units,name,{$id}",
            'description' => 'nullable|string',
        ]);

        InventoryTransaction::run(function () use ($unit, $oldName, $validated) {
            if ($oldName !== $validated['name']) {
                foreach (Product::where('unit', $oldName)->orderBy('id')->lockForUpdate()->get() as $product) {
                    abort_if(DB::table('sales_order_allocations')->where('product_id', $product->id)->exists() || $this->hasOpenProcurement($product->sku), 409, 'Satuan barang tidak dapat diubah selama ada alokasi atau pengadaan aktif.');
                }
                Product::where('unit', $oldName)->update(['unit' => $validated['name']]);
            }
            $unit->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil diperbarui.',
            'data' => $unit,
        ]);
    }

    /**
     * Delete a unit.
     */
    public function destroyUnit(int $id): JsonResponse
    {
        $unit = ProductUnit::findOrFail($id);
        $productCount = Product::where('unit', $unit->name)->count();

        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat menghapus satuan karena masih digunakan oleh {$productCount} barang.",
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dihapus.',
        ]);
    }

    /**
     * Bulk import validated products.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1|max:10000',
            'items.*.sku' => 'nullable|string|max:50',
            'items.*.stock' => 'nullable|integer|min:0|max:1000000000',
            'items.*.min_stock' => 'nullable|integer|min:0|max:1000000000',
            'items.*.last_buy_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'items.*.hpp' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'items.*.sell_price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'items.*.price' => 'nullable|numeric|decimal:0,2|min:0|max:1000000000000',
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'required|string|max:150',
            'items.*.unit' => 'required|string|max:50',
            'duplicate_mode' => 'nullable|string|in:update,skip,auto_id',
        ]);

        $items = $request->input('items', []);
        $duplicateMode = $request->input('duplicate_mode', 'update');
        $user = auth()->user()->name ?? 'Import Excel';

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $now = now();

        InventoryTransaction::run(function () use ($items, $duplicateMode, $user, $now, &$imported, &$updated, &$skipped) {
            // A deadlock retry must not carry counts from a rolled-back attempt.
            $imported = $updated = $skipped = 0;
            // Collect unique categories and units
            $categories = [];
            $units = [];

            foreach ($items as $item) {
                $cat = trim($item['category'] ?? '');
                $unit = trim($item['unit'] ?? '');
                if ($cat) {
                    $categories[$cat] = true;
                }
                if ($unit) {
                    $units[$unit] = true;
                }
            }

            foreach (array_keys($categories) as $catName) {
                ProductCategory::firstOrCreate(['name' => $catName]);
            }

            foreach (array_keys($units) as $unitName) {
                ProductUnit::firstOrCreate(['name' => $unitName]);
            }

            // Existing SKUs
            $lockedProducts = Product::withTrashed()->orderBy('id')->lockForUpdate()->get();
            $existingSkus = $lockedProducts->pluck('id', 'sku')->all();
            // MySQL's SKU unique index is case insensitive. Resolve the saved SKU before guards/upsert.
            $canonicalSkus = $lockedProducts->mapWithKeys(fn ($product) => [mb_strtolower($product->sku) => $product->sku])->all();
            $allocations = DB::table('sales_order_allocations')->groupBy('product_id')->selectRaw('product_id, SUM(qty) as qty')->pluck('qty', 'product_id');

            $upsertRows = [];

            foreach ($items as $idx => $item) {
                $sku = trim($item['sku'] ?? $item['id'] ?? '');
                $name = trim($item['name'] ?? '');
                $category = trim($item['category'] ?? 'Umum');
                $unit = trim($item['unit'] ?? 'PCS');
                $stock = (int) ($item['stock'] ?? 0);
                $minStock = (int) ($item['min_stock'] ?? 10);
                $lastBuy = (float) ($item['last_buy_price'] ?? 0);
                $hpp = (float) ($item['hpp'] ?? 0);
                $sellPrice = (float) ($item['sell_price'] ?? $item['price'] ?? 0);
                $description = $item['description'] ?? null;
                $status = Product::stockStatus($stock, $minStock);

                if (! $sku) {
                    $sequence = count($existingSkus) + $idx + 1;
                    do {
                        $sku = 'BRG-'.str_pad((string) $sequence++, 6, '0', STR_PAD_LEFT);
                    } while (isset($existingSkus[$sku]));
                }

                $sku = $canonicalSkus[mb_strtolower($sku)] ?? $sku;

                $isExisting = isset($existingSkus[$sku]);
                if ($isExisting && $duplicateMode === 'update') {
                    $existing = $lockedProducts->firstWhere('sku', $sku);
                    abort_if($existing?->trashed(), 409, 'SKU '.$sku.' telah diarsipkan. Pulihkan barang sebelum mengimpor ulang.');
                    abort_if($stock < ($allocations[$existingSkus[$sku]] ?? 0), 409, 'Stok impor tidak boleh kurang dari alokasi aktif untuk '.$sku.'.');
                    abort_if($existing && $existing->unit !== $unit && (($allocations[$existing->id] ?? 0) > 0 || $this->hasOpenProcurement($sku)), 409, 'Satuan barang tidak dapat diubah selama ada alokasi atau pengadaan aktif.');
                }

                if ($isExisting && $duplicateMode === 'skip') {
                    $skipped++;

                    continue;
                }

                if ($isExisting && $duplicateMode === 'auto_id') {
                    $sku = 'BRG-'.uniqid();
                    $isExisting = false;
                }

                if ($isExisting) {
                    $updated++;
                } else {
                    $imported++;
                    $existingSkus[$sku] = 0;
                    $canonicalSkus[mb_strtolower($sku)] = $sku;
                }

                $upsertRows[$sku] = [
                    'sku' => $sku,
                    'name' => $name,
                    'category' => $category,
                    'unit' => $unit,
                    'description' => $description,
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'last_buy_price' => $lastBuy,
                    'hpp' => $hpp,
                    'sell_price' => $sellPrice,
                    'status' => $status,
                    'created_by' => $user,
                    'updated_by' => $user,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($upsertRows)) {
                foreach (array_chunk(array_values($upsertRows), 400) as $chunk) {
                    Product::upsert($chunk, ['sku'], [
                        'name', 'category', 'unit', 'description', 'stock', 'min_stock',
                        'last_buy_price', 'hpp', 'sell_price', 'status', 'updated_by', 'updated_at',
                    ]);
                }
                $oldBySku = $lockedProducts->keyBy('sku');
                foreach (Product::whereIn('sku', array_keys($upsertRows))->get() as $product) {
                    $old = $oldBySku->get($product->sku);
                    if (! $old || $old->stock !== $product->stock) {
                        StockLedger::record($product, $old?->stock ?? 0, $product->stock, auth()->user(), $old ? 'ADJUSTMENT' : 'OPENING', 'import', null, 'Impor katalog barang.');
                    }
                }
                app(SalesOrderInventory::class)->reallocateDrafts();
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Proses import selesai. Berhasil: {$imported} baru, {$updated} diperbarui, {$skipped} dilewati.",
            'data' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => $imported + $updated,
            ],
        ]);
    }

    private function hasOpenProcurement(string $sku): bool
    {
        foreach (PurchaseTransaction::whereNotIn('status', ['DITERIMA_GUDANG', 'DIBATALKAN'])->get(['items']) as $purchase) {
            if (collect($purchase->items)->contains(fn ($item) => ($item['sku'] ?? null) === $sku)) {
                return true;
            }
        }
        foreach (WarehouseRequest::whereNotIn('status', ['SELESAI', 'DIBATALKAN'])->get(['items']) as $request) {
            if (collect($request->items)->contains(fn ($item) => ($item['sku'] ?? null) === $sku)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Download Excel template.
     */
    public function downloadTemplate(): BinaryFileResponse|JsonResponse
    {
        $paths = [
            public_path('templates/data_barang_template.xlsx'),
            public_path('templates/data_barang_template (2).xlsx'),
            base_path('../data_barang_template (2).xlsx'),
            base_path('../data_barang_template.xlsx'),
            base_path('../data_barang_20261014_103611.xlsx'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return response()->download($path, 'template_import_data_barang.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            }
        }

        return response()->json(['error' => 'File template tidak ditemukan'], 404);
    }
}
