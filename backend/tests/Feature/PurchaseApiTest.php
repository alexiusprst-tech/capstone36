<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ProcurementFixture;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use ProcurementFixture;
    use RefreshDatabase;

    public function test_can_list_and_create_suppliers(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'FINANCE']));
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
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $fixture = $this->linkedRequest([['sku' => 'A00001770', 'name' => 'Barang uji', 'qty' => 100, 'unit' => 'UNIT']]);
        $orderId = $fixture->sales_order_id;
        $fixture->delete();
        $response = $this->postJson('/api/warehouse/requests', [
            'sales_order_id' => $orderId,
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
        Sanctum::actingAs(User::factory()->create(['role' => 'FINANCE']));
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

        $req = $this->linkedRequest([['sku' => 'A00001770', 'name' => $prod->name, 'qty' => 100, 'unit' => 'UNIT', 'est_price' => 100000]], 'PT Synnex Metrodata Indonesia');

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

        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $receiptPayload = ['version' => 1, 'idempotency_key' => 'ae4b75aa-1145-4e1e-9ea6-21c7d3d61da6', 'items' => [['line_index' => 0, 'accepted_qty' => 100, 'rejected_qty' => 0]], 'notes' => 'Barang diterima dan diperiksa oleh Warehouse.'];
        $receipt = $this->postJson("/api/purchases/{$purchaseId}/receive", $receiptPayload);

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
        $this->postJson("/api/purchases/{$purchaseId}/receive", $receiptPayload)
            ->assertOk()
            ->assertJsonPath('data.purchase.status', 'DITERIMA_GUDANG');

        $this->assertEquals(100, $prod->fresh()->stock);
    }
}
