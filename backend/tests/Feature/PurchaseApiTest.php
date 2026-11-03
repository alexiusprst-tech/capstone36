<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\WarehouseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_and_create_suppliers(): void
    {
        $response = $this->postJson('/api/suppliers', [
            'name' => 'PT Mitra Komputer Jaya',
            'phone' => '021-1234567',
            'email' => 'sales@mitrakomputer.com',
            'address' => 'Jakarta Barat',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'PT Mitra Komputer Jaya');

        $list = $this->getJson('/api/suppliers');
        $list->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_create_and_list_warehouse_request(): void
    {
        $response = $this->postJson('/api/warehouse/requests', [
            'project_id' => 'PR00001',
            'project_name' => 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
            'purpose' => 'Kebutuhan pengadaan 100 komputer lab sekolah',
            'requested_by' => 'Agus Santoso',
            'items' => [
                [
                    'sku' => 'A00001770',
                    'name' => 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
                    'qty' => 100,
                    'unit' => 'UNIT',
                    'est_price' => 100000,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'MENUNGGU_FINANCE')
            ->assertJsonPath('data.total_est_cost', '10000000.00');

        $list = $this->getJson('/api/warehouse/requests');
        $list->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_purchase_receipt_updates_request_and_stock_exactly_once(): void
    {
        $prod = Product::create([
            'sku' => 'A00001770',
            'name' => 'Pemeliharaan Komputer Note Book/ Laptop',
            'category' => 'Pemeliharaan',
            'unit' => 'UNIT',
            'stock' => 0,
            'sell_price' => 150000,
            'hpp' => 0,
            'last_buy_price' => 0,
            'status' => 'Habis',
        ]);

        $req = WarehouseRequest::create([
            'request_code' => 'REQ-2026-001',
            'request_date' => '2026-10-14',
            'project_id' => 'PR00001',
            'project_name' => 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
            'purpose' => 'Kebutuhan 100 komputer',
            'requested_by' => 'Agus Santoso',
            'status' => 'MENUNGGU_FINANCE',
            'total_est_cost' => 10000000,
            'items' => [
                [
                    'sku' => 'A00001770',
                    'name' => 'Pemeliharaan Komputer Note Book/ Laptop',
                    'qty' => 100,
                    'unit' => 'UNIT',
                    'est_price' => 100000,
                    'subtotal' => 10000000,
                ],
            ],
        ]);

        $purchaseData = [
            'purchase_code' => 'PB20261014114148',
            'transaction_date' => '2026-10-14',
            'invoice_number' => 'FAK-INV-99812',
            'project_id' => 'PR00001',
            'project_name' => 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'warehouse_request_id' => $req->id,
            'warehouse_request_code' => $req->request_code,
            'subtotal' => 10000000,
            'discount' => 0,
            'total_price' => 10000000,
            'payment_method' => 'Tunai',
            'created_by' => 'Dewi Lestari (Finance)',
            'items' => [
                [
                    'barang' => 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
                    'sku' => 'A00001770',
                    'supplier' => 'PT Synnex Metrodata Indonesia',
                    'qty' => 100,
                    'harga_beli' => 100000,
                    'diskon_percent' => 0,
                    'diskon_nominal' => 0,
                    'subtotal' => 10000000,
                ],
            ],
        ];

        $response = $this->postJson('/api/purchases', $purchaseData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.purchase_code', 'PB20261014114148');

        $purchaseId = $response->json('data.id');

        // Creating a PO starts procurement but does not increase physical stock.
        $this->assertEquals('DIPROSES_PEMBELIAN', $req->fresh()->status);
        $this->assertEquals('PB20261014114148', $req->fresh()->purchase_code);
        $this->assertEquals(0, $prod->fresh()->stock);

        $this->assertEquals(100000, (float) $prod->fresh()->last_buy_price);
        $this->assertEquals(100000, (float) $prod->fresh()->hpp);

        $receipt = $this->postJson("/api/purchases/{$purchaseId}/receive", [
            'notes' => 'Barang diterima dan diperiksa oleh Warehouse.',
        ]);

        $receipt->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.purchase.status', 'DITERIMA_GUDANG')
            ->assertJsonPath('data.items_received.0.sku', 'A00001770')
            ->assertJsonPath('data.items_received.0.qty_received', 100)
            ->assertJsonPath('data.items_received.0.current_stock', 100);

        $this->assertEquals('SELESAI', $req->fresh()->status);
        $this->assertEquals(100, $prod->fresh()->stock);
        $this->assertEquals('Tersedia', $prod->fresh()->status);

        $inventory = $this->getJson('/api/products?search=A00001770');
        $inventory->assertOk()
            ->assertJsonPath('data.0.stock', 100);

        // Replaying the receipt must not add the same shipment to stock twice.
        $this->postJson("/api/purchases/{$purchaseId}/receive")
            ->assertOk()
            ->assertJsonPath('data.status', 'DITERIMA_GUDANG');

        $this->assertEquals(100, $prod->fresh()->stock);
    }

    public function test_finance_can_record_supplier_follow_up_for_a_purchase(): void
    {
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090000',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Pengadaan komputer sekolah',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Tunai',
            'status' => 'DIPESAN',
            'items' => [],
        ]);

        $response = $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-follow-ups", [
            'action' => 'PENERIMAAN_ULANG',
            'notes' => 'Supplier diminta mengirim ulang 2 unit yang kurang.',
            'expected_date' => '2026-11-05',
            'recorded_by' => 'Dewi Lestari (Finance)',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.purchase.supplier_status', 'Menunggu Penerimaan Ulang')
            ->assertJsonPath('data.purchase.supplier_follow_ups.0.action', 'PENERIMAAN_ULANG')
            ->assertJsonPath('data.purchase.supplier_follow_ups.0.expected_date', '2026-11-05');

        $this->assertSame(
            'Supplier diminta mengirim ulang 2 unit yang kurang.',
            $purchase->fresh()->supplier_follow_ups[0]['notes']
        );
    }

    public function test_warehouse_discrepancy_is_available_to_finance_purchase_list(): void
    {
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090100',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Pengadaan komputer sekolah',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Tunai',
            'status' => 'DIPESAN',
            'items' => [],
        ]);

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-discrepancies", [
            'reason' => 'Jumlah yang diterima kurang 2 unit.',
        ])->assertCreated()
            ->assertJsonPath('data.supplier_status', 'Ketidaksesuaian Fisik (Menunggu Retur)')
            ->assertJsonPath('data.discrepancy_reason', 'Jumlah yang diterima kurang 2 unit.');

        $this->getJson('/api/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_status', 'Ketidaksesuaian Fisik (Menunggu Retur)')
            ->assertJsonPath('data.0.discrepancy_reason', 'Jumlah yang diterima kurang 2 unit.');
    }

    public function test_warehouse_order_discrepancy_is_saved_for_finance_with_notes_and_items(): void
    {
        $this->postJson('/api/warehouse/discrepancies', [
            'order_number' => 'ORD-2026-001',
            'client_name' => 'SMA 9',
            'reason' => 'Barang yang diterima kurang 2 unit.',
            'items' => [
                ['sku' => 'MAT-002', 'name' => 'Besi Beton Ulir D16', 'qty' => 2, 'unit' => 'Batang'],
            ],
            'reported_by' => 'Agus Santoso',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'OPEN');

        $this->getJson('/api/warehouse/discrepancies')
            ->assertOk()
            ->assertJsonPath('data.0.order_number', 'ORD-2026-001')
            ->assertJsonPath('data.0.reason', 'Barang yang diterima kurang 2 unit.')
            ->assertJsonPath('data.0.items.0.sku', 'MAT-002')
            ->assertJsonPath('data.0.reported_by', 'Agus Santoso');
    }

    public function test_supplier_replacement_dispatch_is_persisted_for_warehouse(): void
    {
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090200',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Renovasi ruang kelas',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Transfer Bank',
            'status' => 'DIPESAN',
            'items' => [['barang' => 'Laptop', 'qty' => 1, 'harga_beli' => 1000000, 'subtotal' => 1000000]],
            'supplier_status' => 'Menunggu Penerimaan Ulang',
        ]);

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-replacement-dispatch", [
            'action' => 'Pengiriman Kekurangan Item / Qty Tambahan',
            'replacement_surat_jalan' => 'SJ-RETUR-123456',
            'notes' => 'Dua unit susulan dikirim hari ini.',
            'resolved_date' => '2026-11-03',
        ])->assertCreated()
            ->assertJsonPath('data.supplier_status', 'Barang Pengganti Dikirim')
            ->assertJsonPath('data.discrepancy_resolution.replacement_surat_jalan', 'SJ-RETUR-123456')
            ->assertJsonPath('data.discrepancy_resolution.notes', 'Dua unit susulan dikirim hari ini.');

        $this->getJson('/api/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_status', 'Barang Pengganti Dikirim')
            ->assertJsonPath('data.0.discrepancy_resolution.notes', 'Dua unit susulan dikirim hari ini.');
    }

    public function test_supplier_dispatch_turns_reorder_follow_up_into_warehouse_replacement_shipment(): void
    {
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090300',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Renovasi ruang kelas',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Transfer Bank',
            'status' => 'DITERIMA_GUDANG',
            'items' => [['barang' => 'Laptop', 'qty' => 1, 'harga_beli' => 1000000, 'subtotal' => 1000000]],
            'supplier_status' => 'Menunggu Pemesanan Ulang',
            'supplier_follow_ups' => [['action' => 'PEMESANAN_ULANG']],
        ]);

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-dispatch", [
            'surat_jalan' => 'SJ-ULANG-123456',
            'courier' => 'Kurir Supplier',
            'tracking_number' => 'TRK-123456',
            'dispatch_date' => '2026-11-03',
            'est_arrival' => '2026-11-04',
            'notes' => 'Barang susulan sudah dikirim.',
        ])->assertCreated()
            ->assertJsonPath('data.supplier_status', 'Barang Pengganti Dikirim')
            ->assertJsonPath('data.delivery_info.surat_jalan', 'SJ-ULANG-123456')
            ->assertJsonPath('data.discrepancy_resolution.notes', 'Barang susulan sudah dikirim.');

        $this->getJson('/api/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_status', 'Barang Pengganti Dikirim')
            ->assertJsonPath('data.0.delivery_info.tracking_number', 'TRK-123456');
    }

    public function test_warehouse_receives_replacement_goods_once_and_updates_supplier_status(): void
    {
        $product = Product::create([
            'sku' => 'LAP-001',
            'name' => 'Laptop',
            'category' => 'Elektronik',
            'unit' => 'Unit',
            'stock' => 3,
            'sell_price' => 15000000,
            'hpp' => 10000000,
            'last_buy_price' => 10000000,
            'status' => 'Tersedia',
        ]);
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090400',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Renovasi ruang kelas',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Transfer Bank',
            'status' => 'DITERIMA_GUDANG',
            'items' => [['sku' => 'LAP-001', 'barang' => 'Laptop', 'qty' => 10, 'harga_beli' => 1000000, 'subtotal' => 10000000]],
            'supplier_status' => 'Barang Pengganti Dikirim',
            'discrepancy_resolution' => ['replacement_surat_jalan' => 'SJ-RETUR-654321'],
        ]);

        $payload = [
            'notes' => 'Dua unit susulan diterima dan diperiksa.',
            'items_received' => [
                ['sku' => 'LAP-001', 'name' => 'Laptop', 'qty' => 2, 'unit' => 'Unit'],
            ],
        ];

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-replacement-receive", $payload)
            ->assertOk()
            ->assertJsonPath('data.purchase.supplier_status', 'Barang Pengganti Diterima Gudang')
            ->assertJsonPath('data.purchase.discrepancy_resolution.warehouse_received_notes', 'Dua unit susulan diterima dan diperiksa.')
            ->assertJsonPath('data.items_received.0.current_stock', 5);

        $this->assertSame(5.0, (float) $product->fresh()->stock);

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-replacement-receive", $payload)
            ->assertStatus(409);
        $this->assertSame(5.0, (float) $product->fresh()->stock);

        $this->getJson('/api/purchases')
            ->assertOk()
            ->assertJsonPath('data.0.supplier_status', 'Barang Pengganti Diterima Gudang');
    }

    public function test_warehouse_can_receive_replacement_items_and_supplier_status_updates_once(): void
    {
        $product = Product::create([
            'sku' => 'LAP-001',
            'name' => 'Laptop',
            'category' => 'Elektronik',
            'unit' => 'Unit',
            'stock' => 3,
            'sell_price' => 15000000,
            'hpp' => 10000000,
            'last_buy_price' => 10000000,
            'status' => 'Tersedia',
        ]);
        $purchase = PurchaseTransaction::create([
            'purchase_code' => 'PB20261103090400',
            'transaction_date' => '2026-11-03',
            'project_name' => 'Renovasi ruang kelas',
            'default_supplier' => 'PT Synnex Metrodata Indonesia',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
            'payment_method' => 'Transfer Bank',
            'status' => 'DITERIMA_GUDANG',
            'items' => [['sku' => 'LAP-001', 'barang' => 'Laptop', 'qty' => 10, 'harga_beli' => 1000000, 'subtotal' => 10000000]],
            'supplier_status' => 'Barang Pengganti Dikirim',
            'discrepancy_resolution' => ['replacement_surat_jalan' => 'SJ-RETUR-654321'],
        ]);

        $payload = [
            'notes' => 'Dua unit susulan diterima dan diperiksa.',
            'items_received' => [
                ['sku' => 'LAP-001', 'name' => 'Laptop', 'qty' => 2, 'unit' => 'Unit'],
            ],
        ];

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-replacement-receive", $payload)
            ->assertOk()
            ->assertJsonPath('data.purchase.supplier_status', 'Barang Pengganti Diterima Gudang')
            ->assertJsonPath('data.purchase.discrepancy_resolution.warehouse_received_notes', 'Dua unit susulan diterima dan diperiksa.')
            ->assertJsonPath('data.items_received.0.current_stock', 5);

        $this->assertSame(5.0, (float) $product->fresh()->stock);

        $this->postJson("/api/purchases/{$purchase->purchase_code}/supplier-replacement-receive", $payload)
            ->assertStatus(409);

        $this->assertSame(5.0, (float) $product->fresh()->stock);
    }
}
