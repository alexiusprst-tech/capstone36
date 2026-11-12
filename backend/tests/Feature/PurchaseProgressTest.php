<?php

namespace Tests\Feature;

use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseProgressTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(string $code = 'PO-ONE'): PurchaseTransaction
    {
        return PurchaseTransaction::create([
            'purchase_code' => $code, 'transaction_date' => '2026-11-06', 'project_name' => 'Proyek uji',
            'default_supplier' => 'Supplier Uji', 'subtotal' => 100, 'total_price' => 100,
            'payment_method' => 'Tunai', 'status' => 'DIPESAN',
            'supplier_progress' => ['supplier_status' => 'Menunggu Konfirmasi'], 'progress_version' => 1,
            'items' => [['barang' => 'Barang', 'sku' => 'SKU-TEST', 'qty' => 1, 'harga_beli' => 100, 'subtotal' => 100]],
        ]);
    }

    private function supplier(string $name = 'Supplier Uji'): void
    {
        $user = User::factory()->create(['role' => 'SUPPLIER']);
        Supplier::create(['code' => 'SUP-'.$user->id, 'name' => $name, 'email' => $user->email, 'is_active' => true]);
        Sanctum::actingAs($user);
    }

    private function progress(string $action, int $version, array $progress, string $code = 'PO-ONE')
    {
        return $this->postJson('/api/purchases/'.$code.'/supplier-progress', compact('action', 'version', 'progress'));
    }

    public function test_shipping_survives_another_po_and_replays_do_not_repeat_history(): void
    {
        $purchase = $this->purchase();
        $this->supplier();
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertOk();
        $delivery = ['surat_jalan' => 'SJ-001', 'courier' => 'Kurir Uji', 'tracking_number' => 'RESI-001', 'dispatch_date' => '2026-11-06'];
        $this->progress('dispatch', 2, ['supplier_status' => 'Dikirim ke Gudang CV', 'delivery_info' => $delivery])
            ->assertOk()->assertJsonPath('data.delivery_info.tracking_number', 'RESI-001');
        $this->progress('dispatch', 2, ['supplier_status' => 'Dikirim ke Gudang CV', 'delivery_info' => $delivery])->assertConflict();
        $this->progress('confirm', 3, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertConflict();
        $this->postJson('/api/purchases', [
            'purchase_code' => 'PO-TWO', 'transaction_date' => '2026-11-06', 'project_name' => 'Proyek kedua',
            'default_supplier' => 'Supplier Uji', 'subtotal' => 100, 'total_price' => 100, 'payment_method' => 'Tunai',
            'items' => [['barang' => 'Barang kedua', 'qty' => 1, 'harga_beli' => 100, 'subtotal' => 100]],
        ])->assertCreated();
        $list = $this->getJson('/api/purchases')->assertOk();
        $data = collect($list->json('data'))->firstWhere('purchase_code', 'PO-ONE');
        $this->assertSame('Dikirim ke Gudang CV', $data['supplier_status']);
        $this->assertSame('RESI-001', $data['delivery_info']['tracking_number']);
        $this->assertCount(2, $purchase->fresh()->progress_history);
        $this->assertSame('Menunggu Konfirmasi', collect($list->json('data'))->firstWhere('purchase_code', 'PO-TWO')['supplier_status']);
    }

    public function test_supplier_cannot_modify_another_suppliers_po_or_confirm_arrival(): void
    {
        $this->purchase();
        $this->supplier('Supplier Lain');
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertForbidden();
        $this->progress('arrival', 1, ['supplier_status' => 'Dikirim ke Gudang CV'])->assertForbidden();
    }

    public function test_finance_and_anonymous_cannot_change_supplier_progress(): void
    {
        $this->purchase();
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => 'FINANCE']));
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertForbidden();
    }

    public function test_legacy_confirmation_can_be_recovered_once_without_creating_another_purchase(): void
    {
        $purchase = $this->purchase();
        $purchase->update(['supplier_progress' => null, 'progress_version' => 0]);
        $this->supplier();
        $this->progress('recover', 0, ['supplier_status' => 'Dikonfirmasi Supplier', 'warehouse_arrived' => true])->assertOk()
            ->assertJsonPath('data.warehouse_arrived', false);
        $this->progress('recover', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertConflict();
        $this->assertDatabaseCount('purchase_transactions', 1);
        $this->assertCount(1, $purchase->fresh()->progress_history);
    }

    public function test_received_purchase_cannot_return_to_confirmation(): void
    {
        $purchase = $this->purchase();
        $purchase->update(['status' => 'DITERIMA_GUDANG']);
        $this->supplier();
        $this->getJson('/api/purchases')->assertOk()->assertJsonPath('data.0.supplier_status', 'Diterima Gudang (Lengkap & Sesuai)');
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertConflict();
    }

    public function test_warehouse_request_cannot_be_used_to_issue_a_duplicate_po(): void
    {
        $request = WarehouseRequest::create(['request_code' => 'REQ-ONE', 'request_date' => '2026-11-06',
            'requested_by' => 'Gudang', 'purchase_code' => 'PO-ONE', 'status' => 'DIPROSES_PEMBELIAN']);
        $this->postJson('/api/purchases', ['purchase_code' => 'PO-DUP', 'transaction_date' => '2026-11-06',
            'project_name' => 'Proyek', 'warehouse_request_id' => $request->id, 'warehouse_request_code' => 'REQ-ONE',
            'subtotal' => 100, 'total_price' => 100, 'payment_method' => 'Tunai',
            'items' => [['barang' => 'Barang', 'qty' => 1, 'harga_beli' => 100, 'subtotal' => 100]],
        ])->assertConflict();
        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->assertSame('PO-ONE', $request->fresh()->purchase_code);
    }

    public function test_tracking_arrival_and_replacement_preserve_the_shipping_record(): void
    {
        $this->purchase();
        $this->supplier();
        $supplier = User::where('role', 'SUPPLIER')->firstOrFail();
        $delivery = ['surat_jalan' => 'SJ-ONE', 'courier' => 'Kurir', 'tracking_number' => 'RESI-ONE', 'dispatch_date' => '2026-11-06'];
        $this->progress('confirm', 1, ['supplier_status' => 'Dikonfirmasi Supplier'])->assertOk();
        $this->progress('dispatch', 2, ['supplier_status' => 'Dikirim ke Gudang CV', 'delivery_info' => $delivery])->assertOk();
        $this->progress('tracking', 3, ['supplier_status' => 'Dikirim ke Gudang CV', 'delivery_info' => [...$delivery, 'shipping_status' => 'Tiba di Gudang']])->assertOk();
        $warehouse = User::factory()->create(['role' => 'WAREHOUSE']);
        Sanctum::actingAs($warehouse);
        $this->progress('arrival', 4, ['supplier_status' => 'Dikirim ke Gudang CV'])->assertOk()->assertJsonPath('data.warehouse_arrived', true);
        $this->progress('arrival', 5, ['supplier_status' => 'Dikirim ke Gudang CV'])->assertConflict();
        $this->progress('discrepancy', 5, ['supplier_status' => 'Ketidaksesuaian Fisik (Menunggu Retur)', 'discrepancy_reason' => 'Barang rusak'])->assertOk();
        Sanctum::actingAs($supplier);
        $this->progress('replacement', 6, ['supplier_status' => 'Barang Pengganti Dikirim', 'delivery_info' => [...$delivery, 'tracking_number' => 'RESI-REPLACEMENT']])
            ->assertOk()->assertJsonPath('data.warehouse_arrived', false)->assertJsonPath('data.discrepancy_reason', 'Barang rusak');
        Sanctum::actingAs($warehouse);
        $this->postJson('/api/purchases/PO-ONE/receive')->assertOk();
        $this->getJson('/api/purchases')->assertOk()->assertJsonPath('data.0.supplier_status', 'Diterima Gudang (Lengkap & Sesuai)')
            ->assertJsonPath('data.0.delivery_info.tracking_number', 'RESI-REPLACEMENT');
    }
}
