<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class Nov20ProcurementStateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function role(string $role, ?string $name = null): User
    {
        $user = User::factory()->create(['role' => $role, ...($name ? ['name' => $name] : [])]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function purchase(): array
    {
        $this->travelTo(Carbon::parse('2026-11-20T12:00:00+07:00'));
        $this->role('MARKETING');
        $customer = Customer::factory()->create();
        Product::create(['sku' => 'NOV20-QC', 'name' => 'Barang QC November', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 2]);
        $this->putJson('/api/orders/ORD-NOV20', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'Proyek November'],
            'items' => [['sku' => 'NOV20-QC', 'name' => 'Barang QC November', 'unit' => 'PCS', 'qty' => 10, 'price' => 1000]],
        ])->assertOk();
        $this->role('WAREHOUSE');
        $mr = $this->postJson('/api/warehouse/requests', ['order_number' => 'ORD-NOV20', 'purpose' => 'Pengadaan QC',
            'items' => [['sku' => 'NOV20-QC', 'qty' => 8, 'est_price' => 100]],
        ])->assertCreated()->json('data');
        $this->role('FINANCE');
        Supplier::create(['code' => 'SUP-NOV20', 'name' => 'Supplier November', 'is_active' => true]);

        return $this->postJson('/api/purchases', ['purchase_code' => 'PO-NOV20', 'transaction_date' => '2026-11-20',
            'warehouse_request_code' => $mr['request_code'], 'default_supplier' => 'Supplier November', 'payment_method' => 'Transfer',
            'items' => [['sku' => 'NOV20-QC', 'unit' => 'PCS', 'qty' => 8, 'harga_beli' => 100]],
        ])->assertCreated()->json('data');
    }

    private function shipped(): array
    {
        $this->purchase();
        $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'confirm', 'version' => 1,
            'progress' => ['supplier_notes' => 'Supplier memastikan barang tersedia'],
        ])->assertOk();

        return $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'dispatch', 'version' => 2,
            'progress' => ['delivery_info' => ['surat_jalan' => 'SJ-NOV20', 'courier' => 'Kurir November',
                'tracking_number' => 'RESI-NOV20', 'dispatch_date' => '2026-11-20', 'shipping_status' => 'Dalam Perjalanan']],
        ])->assertOk()->json('data');
    }

    private function listed(): array
    {
        return collect($this->getJson('/api/purchases')->assertOk()->json('data'))->firstWhere('purchase_code', 'PO-NOV20');
    }

    public function test_uninspected_po_exposes_exact_qc_counts_without_inventing_returns_or_a_receiver(): void
    {
        $purchase = $this->purchase();

        $this->assertSame(['ordered_qty' => 8, 'accepted_qty' => 0, 'rejected_qty' => 0, 'remaining_qty' => 8,
            'needs_followup' => false, 'return_status' => 'TIDAK_ADA'], $purchase['inspection']);
        $this->assertSame('BELUM_DIPERIKSA', $purchase['receipt_summary'][0]['qc_status']);
        $this->assertSame([], $purchase['return_items']);
        $this->assertSame([], $purchase['receipts']);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 2]);
    }

    public function test_qc_returns_contain_only_rejected_goods_and_preserve_real_receivers_through_replacement(): void
    {
        $purchase = $this->purchase();
        $receiver = $this->role('WAREHOUSE', 'Petugas QC November');
        $first = $this->postJson('/api/purchases/PO-NOV20/receive', ['version' => $purchase['progress_version'],
            'idempotency_key' => '89e17355-e432-4a7e-87f0-f98b7dc27a66', 'received_date' => '2026-11-20',
            'items' => [['line_index' => 0, 'accepted_qty' => 3, 'rejected_qty' => 2, 'reason' => 'Dua unit pecah saat pemeriksaan awal']],
        ])->assertOk()->json('data.purchase');

        $this->assertSame(['ordered_qty' => 8, 'accepted_qty' => 3, 'rejected_qty' => 2, 'remaining_qty' => 5,
            'needs_followup' => true, 'return_status' => 'PERLU_TINDAK_LANJUT'], $first['inspection']);
        $this->assertSame('DITOLAK_RETUR', $first['receipt_summary'][0]['qc_status']);
        $this->assertSame('Petugas QC November', $first['receipts'][0]['receiver_name']);
        $this->assertSame(['id' => $receiver->id, 'name' => 'Petugas QC November'], $first['receipts'][0]['receiver']);
        $this->assertCount(1, $first['return_items']);
        $this->assertSame(2, $first['return_items'][0]['rejected_qty']);
        $this->assertSame('Petugas QC November', $first['return_items'][0]['received_by_name']);
        $this->assertSame('Dua unit pecah saat pemeriksaan awal', $first['return_items'][0]['reason']);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 5]);

        $this->role('FINANCE');
        $this->assertSame($first['return_items'], $this->listed()['return_items']);
        $followup = $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'followup', 'version' => $first['progress_version'],
            'progress' => ['followup_reference' => 'RETUR-QC-NOV20', 'followup_notes' => 'Supplier melengkapi tiga unit dan mengganti dua unit yang ditolak saat QC.'],
        ])->assertOk()->json('data');
        $this->assertSame('MENUNGGU_PENGGANTI', $followup['inspection']['return_status']);
        $this->assertSame('MENUNGGU_PENGGANTI', $followup['receipt_summary'][0]['qc_status']);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 5]);
        $this->assertSame(3, (int) DB::table('inventory_movements')->where('type', 'RECEIPT')->sum('delta'));

        $secondReceiver = $this->role('WAREHOUSE', 'Petugas Pengganti November');
        $complete = $this->postJson('/api/purchases/PO-NOV20/receive', ['version' => $followup['progress_version'],
            'idempotency_key' => 'ea61e5e8-26f7-4ec3-a596-e7f5d76dc8f2', 'items' => [['line_index' => 0, 'accepted_qty' => 5, 'rejected_qty' => 0]],
        ])->assertOk()->json('data.purchase');
        $this->assertSame('PENGGANTI_DITERIMA', $complete['inspection']['return_status']);
        $this->assertSame('PENGGANTI_DITERIMA', $complete['receipt_summary'][0]['qc_status']);
        $this->assertSame(8, $complete['inspection']['accepted_qty']);
        $this->assertSame(2, $complete['inspection']['rejected_qty']);
        $this->assertSame($first['return_items'], $complete['return_items']);
        $this->assertSame($receiver->id, $complete['receipts'][0]['received_by_id']);
        $this->assertSame($secondReceiver->id, $complete['receipts'][1]['received_by_id']);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 10]);
        $this->assertSame(8, (int) DB::table('inventory_movements')->where('type', 'RECEIPT')->sum('delta'));
        $this->assertDatabaseCount('purchase_receipts', 2);
    }

    public function test_status_only_tracking_patch_preserves_resi_courier_and_dispatch_document_across_roles(): void
    {
        $purchase = $this->shipped();
        $tracked = $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'tracking', 'version' => $purchase['progress_version'],
            'progress' => ['delivery_info' => ['shipping_status' => 'Tiba di Gudang', 'current_location' => 'Gerbang Gudang Alpharo']],
        ])->assertOk()->json('data');
        $this->assertSame('RESI-NOV20', $tracked['delivery_info']['tracking_number']);
        $this->assertSame('Kurir November', $tracked['delivery_info']['courier']);
        $this->assertSame('SJ-NOV20', $tracked['delivery_info']['surat_jalan']);
        $this->role('WAREHOUSE');
        $listed = $this->listed();
        $this->assertSame($tracked['delivery_info'], $listed['delivery_info']);
        $this->assertSame('BELUM_DIPERIKSA', $listed['receipt_summary'][0]['qc_status']);
        $this->assertSame([], $listed['return_items']);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 2]);
    }

    #[TestWith(['shipping_status'])]
    #[TestWith(['tracking_number'])]
    #[TestWith(['current_location'])]
    public function test_malformed_tracking_fields_are_422_without_partial_progress_or_stock_changes(string $field): void
    {
        $purchase = $this->shipped();
        $before = PurchaseTransaction::where('purchase_code', 'PO-NOV20')->firstOrFail();

        $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'tracking', 'version' => $purchase['progress_version'],
            'progress' => ['delivery_info' => [$field => ['unexpected' => 'nested object']]],
        ])->assertUnprocessable()->assertJsonValidationErrors('progress.delivery_info.'.$field);

        $this->assertSame($before->supplier_progress, $before->fresh()->supplier_progress);
        $this->assertSame($before->progress_history, $before->fresh()->progress_history);
        $this->assertSame($before->progress_version, $before->fresh()->progress_version);
        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 2]);
    }

    public function test_tracking_after_partial_acceptance_preserves_the_inspection_and_cannot_be_forged_by_warehouse(): void
    {
        $purchase = $this->shipped();
        $this->role('WAREHOUSE');
        $partial = $this->postJson('/api/purchases/PO-NOV20/receive', ['version' => $purchase['progress_version'],
            'idempotency_key' => '7ddf1414-3ef2-42c6-bfad-767722d81e21', 'items' => [['line_index' => 0, 'accepted_qty' => 1, 'rejected_qty' => 0]],
        ])->assertOk()->json('data.purchase');
        $payload = ['action' => 'tracking', 'version' => $partial['progress_version'],
            'progress' => ['delivery_info' => ['shipping_status' => 'Dalam Perjalanan', 'current_location' => 'Sisa tujuh unit di kendaraan kedua']]];
        $this->postJson('/api/purchases/PO-NOV20/supplier-progress', $payload)->assertForbidden();
        $this->role('FINANCE');
        $tracked = $this->postJson('/api/purchases/PO-NOV20/supplier-progress', $payload)->assertOk()->json('data');

        $this->assertSame('DITERIMA_SEBAGIAN', $tracked['status']);
        $this->assertSame($partial['inspection'], $tracked['inspection']);
        $this->assertSame('DITERIMA_SEBAGIAN', $tracked['receipt_summary'][0]['qc_status']);
        $this->assertSame([], $tracked['return_items']);
        $this->assertSame('RESI-NOV20', $tracked['delivery_info']['tracking_number']);
        $this->assertDatabaseCount('purchase_receipts', 1);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 3]);
    }

    #[TestWith([null])]
    #[TestWith([['tracking_number' => null, 'courier' => null, 'surat_jalan' => null]])]
    public function test_null_tracking_patches_do_not_erase_saved_shipping_identity(?array $delivery): void
    {
        $purchase = $this->shipped();

        $this->postJson('/api/purchases/PO-NOV20/supplier-progress', ['action' => 'tracking', 'version' => $purchase['progress_version'],
            'progress' => ['delivery_info' => $delivery],
        ])->assertOk()->assertJsonPath('data.delivery_info.tracking_number', 'RESI-NOV20')
            ->assertJsonPath('data.delivery_info.courier', 'Kurir November')->assertJsonPath('data.delivery_info.surat_jalan', 'SJ-NOV20');

        $this->role('WAREHOUSE');
        $this->assertSame('RESI-NOV20', $this->listed()['delivery_info']['tracking_number']);
        $this->assertDatabaseCount('purchase_receipts', 0);
        $this->assertDatabaseHas('products', ['sku' => 'NOV20-QC', 'stock' => 2]);
    }
}
