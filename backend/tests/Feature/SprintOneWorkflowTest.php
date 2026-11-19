<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/** Independent request-level acceptance coverage for Sprint 1's persisted business flow. */
class SprintOneWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function role(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function order(int $qty = 10, int $stock = 2, ?array $items = null): array
    {
        $this->role('MARKETING');
        $customer = Customer::factory()->create();
        Product::create(['sku' => 'SP1-ITEM', 'name' => 'Barang Sprint 1', 'unit' => 'PCS',
            'category' => 'Pengujian', 'stock' => $stock, 'sell_price' => 12500, 'hpp' => 0, 'last_buy_price' => 0]);

        return $this->putJson('/api/orders/ORD-SP1', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => 'Nama yang tidak dipercaya', 'projectName' => 'Proyek Sprint 1'],
            'items' => $items ?? [['sku' => 'SP1-ITEM', 'name' => 'Barang Sprint 1', 'unit' => 'PCS', 'qty' => $qty, 'price' => 12500]],
        ])->assertOk()->json('data');
    }

    private function requestPayload(int $qty = 8): array
    {
        return ['order_number' => 'ORD-SP1', 'purpose' => 'Memenuhi kekurangan order tersimpan',
            'requested_by' => 'Tidak boleh menyamar sebagai pengguna lain',
            'items' => [['sku' => 'SP1-ITEM', 'name' => 'Nama palsu', 'unit' => 'UNIT PALSU', 'qty' => $qty, 'est_price' => 10000]]];
    }

    private function materialRequest(int $qty = 8): array
    {
        $this->role('WAREHOUSE');

        return $this->postJson('/api/warehouse/requests', $this->requestPayload($qty))->assertCreated()->json('data');
    }

    private function purchasePayload(array $request, string $code = 'PO-SP1'): array
    {
        return ['purchase_code' => $code, 'transaction_date' => '2026-11-19',
            'project_name' => 'Proyek Sprint 1', 'warehouse_request_code' => $request['request_code'],
            'default_supplier' => 'Supplier Terdaftar Sprint 1', 'payment_method' => 'Transfer',
            'subtotal' => 1, 'discount' => 0, 'total_price' => 1,
            'items' => [['sku' => 'SP1-ITEM', 'barang' => 'SP1-ITEM - Barang Sprint 1', 'unit' => 'PCS',
                'supplier' => 'Supplier Terdaftar Sprint 1', 'qty' => $request['items'][0]['qty'],
                'harga_beli' => 10000, 'diskon_percent' => 0, 'diskon_nominal' => 0, 'subtotal' => 1]]];
    }

    private function purchase(array $request): array
    {
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-SP1', 'name' => 'Supplier Terdaftar Sprint 1', 'is_active' => true]);

        return $this->postJson('/api/purchases', $this->purchasePayload($request))->assertCreated()->json('data');
    }

    private function receipt(array $purchase, int $accepted, int $rejected = 0, string $key = '9ac7ad63-68df-44a6-9c63-f973114ebba1'): TestResponse
    {
        return $this->postJson('/api/purchases/'.$purchase['purchase_code'].'/receive', [
            'version' => $purchase['progress_version'], 'idempotency_key' => $key,
            'items' => [['line_index' => 0, 'accepted_qty' => $accepted, 'rejected_qty' => $rejected,
                'reason' => $rejected ? 'Kemasan rusak dan spesifikasi tidak sesuai' : null]],
        ]);
    }

    private function persistedPurchase(string $code = 'PO-SP1'): array
    {
        return collect($this->getJson('/api/purchases')->assertOk()->json('data'))->firstWhere('purchase_code', $code);
    }

    private function persistedOrder(): array
    {
        return collect($this->getJson('/api/orders')->assertOk()->json('data'))->firstWhere('orderNumber', 'ORD-SP1');
    }

    public function test_existing_stock_is_allocated_and_ready_without_increasing_physical_stock(): void
    {
        $order = $this->order(qty: 3, stock: 3);

        $this->assertSame('Draft', $order['status']);
        $this->assertSame('Siap Diajukan', $order['stockReadiness']['status']);
        $this->assertSame(41625, $order['grandTotal']);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 3]);
        $this->assertDatabaseHas('sales_order_allocations', ['sales_order_id' => SalesOrder::where('order_number', 'ORD-SP1')->value('id'), 'qty' => 3]);

        $this->role('WAREHOUSE');
        $this->postJson('/api/warehouse/requests', $this->requestPayload(1))->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_requests', 0);
        $this->assertSame('Siap Diajukan', $this->persistedOrder()['stockReadiness']['status']);
    }

    public function test_partial_receipt_rejection_followup_and_replacement_persist_across_roles(): void
    {
        $order = $this->order();
        $this->assertSame('Menunggu Stok', $order['stockReadiness']['status']);
        $request = $this->materialRequest();
        $this->assertSame('ORD-SP1', $request['order_number']);
        $this->assertSame('Barang Sprint 1', $request['items'][0]['name']);
        $this->assertSame('PCS', $request['items'][0]['unit']);
        $this->assertSame(80000.0, (float) $request['total_est_cost']);
        $purchase = $this->purchase($request);
        $this->assertSame(80000.0, (float) $purchase['total_price']);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2]);

        $warehouse = $this->role('WAREHOUSE');
        $receipt = $this->receipt($purchase, 3, 2)->assertOk()->json('data.purchase');
        $this->assertSame(true, $receipt['pending_followup']);
        $this->assertSame(3, (int) $receipt['receipt_summary'][0]['accepted']);
        $this->assertSame(2, (int) $receipt['receipt_summary'][0]['rejected']);
        $this->assertSame(5, (int) $receipt['receipt_summary'][0]['remaining']);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 5]);
        $this->assertDatabaseHas('sales_order_allocations', ['sales_order_id' => SalesOrder::where('order_number', 'ORD-SP1')->value('id'), 'qty' => 5]);
        $this->receipt($receipt, 5, key: 'fa73b343-5c41-4195-aae9-57356c2f4333')->assertConflict();
        $this->assertDatabaseCount('purchase_receipts', 1);

        $finance = $this->role('FINANCE');
        $persisted = $this->persistedPurchase();
        $this->assertSame(true, $persisted['pending_followup']);
        $followup = $this->postJson('/api/purchases/PO-SP1/supplier-progress', ['action' => 'followup',
            'version' => $persisted['progress_version'], 'progress' => ['supplier_status' => 'Dalam Pengiriman',
                'followup_reference' => 'RETUR-SP1-01', 'followup_notes' => 'Supplier mengganti dua unit rusak dan melengkapi tiga unit tersisa.'],
        ])->assertOk()->json('data');
        $this->assertSame(false, $followup['pending_followup']);

        Sanctum::actingAs($warehouse);
        $complete = $this->receipt($followup, 5, key: 'f98963a7-41ee-41e8-b09a-32801a115d14')->assertOk()->json('data.purchase');
        $this->assertSame('DITERIMA_GUDANG', $complete['status']);
        $this->assertSame(8, (int) $complete['receipt_summary'][0]['accepted']);
        $this->assertSame(2, (int) $complete['receipt_summary'][0]['rejected']);
        $this->assertSame(0, (int) $complete['receipt_summary'][0]['remaining']);
        $this->assertCount(2, $complete['receipts']);
        $this->assertDatabaseHas('warehouse_requests', ['id' => $request['id'], 'status' => 'SELESAI']);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 10]);
        $this->assertDatabaseCount('purchase_receipts', 2);
        $movements = DB::table('inventory_movements')->where('type', 'RECEIPT')->orderBy('id')->get();
        $this->assertCount(2, $movements);
        $this->assertSame([3, 5], $movements->pluck('delta')->map(fn ($value) => (int) $value)->all());
        $this->assertSame([2, 5], $movements->pluck('before_stock')->map(fn ($value) => (int) $value)->all());
        $this->assertSame([5, 10], $movements->pluck('after_stock')->map(fn ($value) => (int) $value)->all());
        $this->assertSame([$warehouse->id, $warehouse->id], $movements->pluck('actor_id')->all());
        $this->assertSame(['SP1-ITEM', 'SP1-ITEM'], $movements->pluck('sku_snapshot')->all());

        $this->role('MARKETING');
        $ready = $this->persistedOrder();
        $this->assertSame('Siap Diajukan', $ready['stockReadiness']['status']);
        $this->assertSame(10, (int) $ready['stockReadiness']['items'][0]['allocated']);
        $this->assertSame(0, (int) $ready['stockReadiness']['items'][0]['shortage']);
        $this->assertSame(138750, $ready['grandTotal']);
        $this->assertSame('Draft', $ready['status']);
        Sanctum::actingAs($finance);
        $this->assertCount(2, $this->persistedPurchase()['receipts']);
    }

    public function test_receipt_replay_is_exactly_once_and_changed_payload_or_stale_version_cannot_write(): void
    {
        $this->order();
        $purchase = $this->purchase($this->materialRequest());
        $this->role('WAREHOUSE');
        $accepted = $this->receipt($purchase, 3)->assertOk()->json('data.purchase');

        $this->receipt($purchase, 3)->assertOk();
        $this->receipt($purchase, 4)->assertConflict();
        $this->receipt($purchase, 1, key: '90d0513b-4fa7-4dbe-a136-d97ee848a130')->assertConflict();
        $this->receipt($accepted, 6, key: 'b5a4dce7-80a5-4a66-99b2-dc3bb2b47860')->assertUnprocessable();

        $this->assertDatabaseCount('purchase_receipts', 1);
        $this->assertDatabaseCount('purchase_receipt_lines', 1);
        $this->assertSame(3, (int) DB::table('inventory_movements')->where('type', 'RECEIPT')->sum('delta'));
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 5]);
        $this->assertSame($accepted['progress_version'], $this->persistedPurchase()['progress_version']);
    }

    public function test_pending_request_blocks_duplicate_need_and_duplicate_po_cannot_change_inventory(): void
    {
        $this->order();
        $request = $this->materialRequest();
        $this->postJson('/api/warehouse/requests', $this->requestPayload())->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_requests', 1);
        $purchase = $this->purchase($request);
        $this->postJson('/api/purchases', $this->purchasePayload($request, 'PO-DUPLICATE'))->assertConflict();

        $this->assertDatabaseCount('purchase_transactions', 1);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2]);
        $this->assertSame(0, (int) $purchase['receipt_summary'][0]['accepted']);
        $this->assertSame(8, (int) $purchase['receipt_summary'][0]['remaining']);
    }

    public function test_edited_order_request_mismatch_is_recoverable_by_cancel_and_recreate(): void
    {
        $order = $this->order();
        $request = $this->materialRequest();
        $this->role('MARKETING');
        $model = SalesOrder::where('order_number', 'ORD-SP1')->firstOrFail();
        $this->putJson('/api/orders/ORD-SP1', ['version' => $order['version'], 'details' => $model->details,
            'items' => [['sku' => 'SP1-ITEM', 'name' => 'Barang Sprint 1', 'unit' => 'PCS', 'qty' => 6, 'price' => 12500]],
        ])->assertOk();
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-SP1', 'name' => 'Supplier Terdaftar Sprint 1', 'is_active' => true]);
        $this->postJson('/api/purchases', $this->purchasePayload($request))->assertUnprocessable();
        $this->assertDatabaseCount('purchase_transactions', 0);

        $this->role('WAREHOUSE');
        $this->postJson('/api/warehouse/requests/'.$request['request_code'].'/cancel')->assertOk();
        $replacement = $this->postJson('/api/warehouse/requests', $this->requestPayload(4))->assertCreated()->json('data');
        $this->assertNotSame($request['request_code'], $replacement['request_code']);
        $this->role('FINANCE');
        $this->postJson('/api/purchases', $this->purchasePayload($replacement))->assertCreated();
        $this->assertDatabaseCount('warehouse_requests', 2);
        $this->assertDatabaseCount('purchase_transactions', 1);
        $this->role('WAREHOUSE');
        $this->postJson('/api/warehouse/requests/'.$replacement['request_code'].'/cancel')->assertConflict();
    }

    #[TestWith(['qty', 1.5])]
    #[TestWith(['qty', 2147483648])]
    #[TestWith(['est_price', 'NaN'])]
    #[TestWith(['sku', 'UNKNOWN-SP1'])]
    public function test_invalid_material_request_is_422_without_partial_records(string $field, mixed $value): void
    {
        $this->order();
        $this->role('WAREHOUSE');
        $payload = $this->requestPayload();
        $payload['items'][0][$field] = $value;

        $this->postJson('/api/warehouse/requests', $payload)->assertUnprocessable();

        $this->assertDatabaseCount('warehouse_requests', 0);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2]);
    }

    public function test_duplicate_request_skus_cannot_double_count_an_order_shortage(): void
    {
        $this->order();
        $this->role('WAREHOUSE');
        $payload = $this->requestPayload(5);
        $payload['items'][] = $payload['items'][0];

        $this->postJson('/api/warehouse/requests', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_requests', 0);
    }

    public function test_repeated_order_skus_have_one_aggregated_shortage_and_allocation(): void
    {
        $line = ['sku' => 'SP1-ITEM', 'name' => 'Barang Sprint 1', 'unit' => 'PCS', 'price' => 12500];
        $order = $this->order(items: [[...$line, 'qty' => 4], [...$line, 'qty' => 6]]);
        $this->assertCount(1, $order['stockReadiness']['items']);
        $this->assertSame(8, (int) $order['stockReadiness']['items'][0]['shortage']);
        $this->assertSame(2, (int) $order['stockReadiness']['items'][0]['allocated']);

        $this->materialRequest();
        $this->assertDatabaseCount('warehouse_requests', 1);
        $this->assertDatabaseCount('sales_order_allocations', 1);
    }

    #[TestWith(['qty', 7])]
    #[TestWith(['sku', 'UNKNOWN-SP1'])]
    #[TestWith(['unit', 'BOX'])]
    #[TestWith(['supplier', 'Supplier tidak terdaftar'])]
    public function test_po_cannot_substitute_or_underbuy_the_linked_request(string $field, mixed $value): void
    {
        $this->order();
        $request = $this->materialRequest();
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-SP1', 'name' => 'Supplier Terdaftar Sprint 1', 'is_active' => true]);
        $payload = $this->purchasePayload($request);
        $payload['items'][0][$field] = $value;

        $this->postJson('/api/purchases', $payload)->assertUnprocessable();

        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->assertDatabaseHas('warehouse_requests', ['id' => $request['id'], 'status' => 'MENUNGGU_FINANCE']);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2, 'last_buy_price' => 0]);
    }

    public function test_missing_session_and_wrong_roles_cannot_create_or_receive_or_followup(): void
    {
        $this->postJson('/api/warehouse/requests', [])->assertUnauthorized();
        $this->postJson('/api/purchases', [])->assertUnauthorized();
        $this->postJson('/api/purchases/PO-SP1/receive', [])->assertUnauthorized();
        $this->order();
        $request = $this->materialRequest();
        $purchase = $this->purchase($request);
        $this->receipt($purchase, 8)->assertForbidden();
        $this->role('MARKETING');
        $this->postJson('/api/warehouse/requests', $this->requestPayload())->assertForbidden();
        $this->postJson('/api/purchases', $this->purchasePayload($request, 'PO-ILLEGAL'))->assertForbidden();
        $this->postJson('/api/purchases/PO-SP1/supplier-progress', ['action' => 'followup', 'version' => $purchase['progress_version'], 'progress' => []])->assertForbidden();

        $this->assertDatabaseCount('warehouse_requests', 1);
        $this->assertDatabaseCount('purchase_transactions', 1);
        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2]);
    }

    public function test_numeric_po_codes_are_rejected_and_ambiguous_legacy_identifiers_cannot_receive_the_wrong_po(): void
    {
        $this->order();
        $request = $this->materialRequest();
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-SP1', 'name' => 'Supplier Terdaftar Sprint 1', 'is_active' => true]);
        $this->postJson('/api/purchases', $this->purchasePayload($request, '12345'))->assertUnprocessable();
        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->postJson('/api/purchases', $this->purchasePayload($request))->assertCreated();
        $purchase = PurchaseTransaction::firstOrFail();
        $legacy = $purchase->replicate();
        $legacy->purchase_code = (string) $purchase->id;
        $legacy->save();
        $this->role('WAREHOUSE');

        $this->postJson('/api/purchases/'.$purchase->id.'/receive', ['version' => 1,
            'idempotency_key' => 'd1fd2872-d47f-4b27-aa99-55a2c2834267',
            'items' => [['line_index' => 0, 'accepted_qty' => 8, 'rejected_qty' => 0]],
        ])->assertConflict();

        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 2]);
    }

    public function test_supplier_cannot_clear_a_receipt_discrepancy_before_finance_acknowledges_followup(): void
    {
        $this->order();
        $purchase = $this->purchase($this->materialRequest());
        $this->role('WAREHOUSE');
        $rejected = $this->receipt($purchase, 3, 2)->assertOk()->json('data.purchase');
        $supplier = $this->role('SUPPLIER');
        Supplier::where('code', 'SUP-SP1')->update(['email' => $supplier->email]);

        $this->postJson('/api/purchases/PO-SP1/supplier-progress', [
            'action' => 'replacement', 'version' => $rejected['progress_version'],
            'progress' => ['delivery_info' => ['surat_jalan' => 'SJ-REPLACE', 'courier' => 'Kurir QA',
                'tracking_number' => 'RESI-REPLACE', 'dispatch_date' => '2026-11-19']],
        ])->assertConflict();

        $this->assertDatabaseHas('purchase_transactions', ['purchase_code' => 'PO-SP1', 'pending_followup' => true,
            'progress_version' => $rejected['progress_version']]);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 5]);
        $this->assertDatabaseCount('purchase_receipts', 1);
    }

    public function test_supplier_rejection_exposes_finance_followup_without_hiding_the_blocked_po(): void
    {
        $this->order();
        $request = $this->materialRequest();
        $purchase = $this->purchase($request);
        $supplier = $this->role('SUPPLIER');
        Supplier::where('code', 'SUP-SP1')->update(['email' => $supplier->email]);
        $rejected = $this->postJson('/api/purchases/PO-SP1/supplier-progress', [
            'action' => 'reject', 'version' => $purchase['progress_version'],
            'progress' => ['rejection_reason' => 'Barang belum tersedia pada supplier'],
        ])->assertOk()->json('data');

        $this->assertSame(true, $rejected['pending_followup']);
        $this->assertSame('PERLU_TINDAK_LANJUT', $rejected['status']);
        $this->assertDatabaseHas('warehouse_requests', ['id' => $request['id'], 'status' => 'PERLU_TINDAK_LANJUT']);
        $this->role('FINANCE');
        $this->assertSame(true, $this->persistedPurchase()['pending_followup']);
        $this->postJson('/api/purchases/PO-SP1/supplier-progress', ['action' => 'followup',
            'version' => $rejected['progress_version'], 'progress' => ['followup_reference' => 'KONFIRMASI-SP1',
                'followup_notes' => 'Supplier setuju menyediakan pesanan pada pengiriman berikutnya.'],
        ])->assertOk()->assertJsonPath('data.pending_followup', false);
        $this->assertDatabaseCount('purchase_receipts', 0);
    }

    public function test_po_cannot_buy_a_stale_request_after_stock_replenishment_removes_the_shortage(): void
    {
        $this->order();
        $request = $this->materialRequest();
        $product = Product::where('sku', 'SP1-ITEM')->firstOrFail();
        $this->putJson('/api/products/'.$product->id, ['sku' => $product->sku, 'name' => $product->name,
            'category' => $product->category, 'unit' => $product->unit, 'stock' => 10,
            'adjustment_reason' => 'Stok ditemukan pada penghitungan ulang sebelum pembelian'],
        )->assertOk();
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-SP1', 'name' => 'Supplier Terdaftar Sprint 1', 'is_active' => true]);

        $this->postJson('/api/purchases', $this->purchasePayload($request))->assertUnprocessable();

        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 10, 'last_buy_price' => 0]);
    }

    public function test_active_po_locks_required_quantities_but_allows_prices_and_unlocks_after_receipt(): void
    {
        $this->order();
        $purchase = $this->purchase($this->materialRequest());
        $this->role('MARKETING');
        $order = SalesOrder::where('order_number', 'ORD-SP1')->firstOrFail();
        $items = [['sku' => 'SP1-ITEM', 'name' => 'Barang Sprint 1', 'unit' => 'PCS', 'qty' => 10, 'price' => 13000]];
        $priced = $this->putJson('/api/orders/ORD-SP1', ['version' => $order->version, 'details' => $order->details,
            'items' => $items,
        ])->assertOk()->json('data');
        $this->assertSame(144300, $priced['grandTotal']);

        $items[0]['qty'] = 11;
        $this->putJson('/api/orders/ORD-SP1', ['version' => $priced['version'], 'details' => $order->details,
            'items' => $items,
        ])->assertConflict();
        $this->assertSame(10, SalesOrder::whereKey($order->id)->firstOrFail()->items[0]['qty']);
        $this->assertSame($priced['version'], $order->fresh()->version);

        $this->role('WAREHOUSE');
        $this->receipt($purchase, 8)->assertOk();
        $this->role('MARKETING');
        $this->putJson('/api/orders/ORD-SP1', ['version' => $priced['version'], 'details' => $order->details,
            'items' => $items,
        ])->assertOk()->assertJsonPath('data.stockReadiness.items.0.shortage', 1)
            ->assertJsonPath('data.grandTotal', 158730);
        $this->assertDatabaseHas('products', ['sku' => 'SP1-ITEM', 'stock' => 10]);
        $this->assertDatabaseCount('purchase_receipts', 1);
    }
}
