<?php

namespace Tests\Feature;

use App\Models\Product;
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

    public function test_can_create_purchase_transaction_and_update_request_and_stock(): void
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

        // Check request status updated
        $this->assertEquals('DIPROSES_PEMBELIAN', $req->fresh()->status);

        // Check product stock increased and last buy price updated
        $prodFresh = $prod->fresh();
        $this->assertEquals(100, $prodFresh->stock);
        $this->assertEquals(100000, (float) $prodFresh->last_buy_price);
    }
}
