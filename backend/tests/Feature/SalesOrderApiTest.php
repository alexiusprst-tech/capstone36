<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DocumentTemplate;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SpjProject;
use App\Models\User;
use App\Services\SpjService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SalesOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsRole(string $role): User
    {
        if (! Customer::whereKey(1)->exists()) {
            Customer::factory()->create(['id' => 1]);
        }
        $user = User::factory()->create(['role' => $role]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function draft(float $subtotal = 4_000_000, int $stock = 10): SalesOrder
    {
        $user = $this->loginAsRole('MARKETING');
        Product::create(['sku' => 'T22-ITEM', 'name' => 'Barang aktual', 'category' => 'Alat', 'unit' => 'PCS', 'stock' => $stock]);
        // Save through HTTP so later transitions use the same persisted draft as the UI.
        $this->putJson('/api/orders/ORD-T22', [
            'version' => 0,
            'details' => ['clientName' => 'Customer Uji', 'projectName' => 'Proyek T22', 'clientId' => 1, 'projectId' => 'PRJ-T22', 'description' => 'Metadata tersimpan', 'status' => 'Siap Invoice', 'approvedById' => $user->id],
            'items' => [['sku' => 'T22-ITEM', 'name' => 'Barang aktual', 'unit' => 'PCS', 'qty' => 1, 'price' => $subtotal]],
            'grandTotal' => 1, 'status' => 'Siap Invoice',
        ])->assertOk()->assertJsonPath('data.status', 'Draft')->assertJsonPath('data.approvedById', null);

        return SalesOrder::where('order_number', 'ORD-T22')->firstOrFail();
    }

    public function test_missing_authentication_returns_401(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->putJson('/api/orders/ORD-T22', [])->assertUnauthorized();
    }

    #[TestWith(['FINANCE'])]
    #[TestWith(['WAREHOUSE'])]
    #[TestWith(['DIREKTUR'])]
    #[TestWith(['ADMINISTRASI'])]
    #[TestWith(['CLIENT'])]
    #[TestWith(['SUPPLIER'])]
    public function test_other_roles_cannot_save_or_submit_orders(string $role): void
    {
        $order = $this->draft();
        $this->loginAsRole($role);
        $this->putJson('/api/orders/ORD-T22', [])->assertForbidden();
        $this->submitOrder('ORD-T22')->assertForbidden();
        $this->assertSame('Draft', $order->fresh()->status);
    }

    #[TestWith([4_504_504, 4_999_999, 'Siap Invoice'])]
    #[TestWith([4_504_504.5, 5_000_000, 'Siap Invoice'])]
    #[TestWith([4_504_505, 5_000_001, 'Menunggu Verifikasi'])]
    #[TestWith([4_000_000, 4_440_000, 'Siap Invoice'])]
    public function test_server_routes_using_total_including_tax(float $subtotal, int $total, string $status): void
    {
        $order = $this->draft($subtotal);
        $this->submitOrder('ORD-T22', ['grandTotal' => 1, 'status' => 'Siap Invoice'])
            ->assertOk()->assertJsonPath('data.status', $status)->assertJsonPath('data.grandTotal', $total);
        $fresh = $order->fresh();
        $this->assertSame($status, $fresh->status);
        $this->assertSame($order->created_by, $fresh->submitted_by);
        $this->assertNotNull($fresh->submitted_at);
        $this->assertSame('MARKETING', $fresh->history[1]['role']);
        $this->assertDatabaseCount('sales_orders', 1);
    }

    public function test_unfulfilled_stock_blocks_submission_without_partial_transition(): void
    {
        $order = $this->draft(stock: 0);
        $this->postJson('/api/orders/ORD-T22/stock-check')->assertOk()
            ->assertJsonPath('data.stockReadiness.items.0.name', 'Barang aktual')
            ->assertJsonPath('data.stockReadiness.items.0.unit', 'PCS');
        $this->submitOrder('ORD-T22')->assertUnprocessable()->assertJsonValidationErrors('items')
            ->assertJsonPath('errors.items.0', 'Stok kurang: Barang aktual (1 PCS).');
        $this->assertSame('Draft', $order->fresh()->status);
        $this->assertNull($order->fresh()->submitted_at);
        $this->assertCount(2, $order->fresh()->history);
    }

    public function test_unknown_and_repeated_skus_cannot_bypass_stock_check(): void
    {
        $order = $this->draft(stock: 1);
        $item = $order->items[0];
        $order->update(['items' => [$item, $item]]);
        $this->submitOrder('ORD-T22')->assertUnprocessable();
        $order->update(['items' => [[...$item, 'sku' => 'UNKNOWN', 'name' => 'Barang kebutuhan khusus']]]);
        $this->submitOrder('ORD-T22')->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Barang belum terdaftar: Barang kebutuhan khusus. Pilih ulang di Edit Order.');
        $this->assertSame('Draft', $order->fresh()->status);
    }

    public function test_stock_in_a_similar_catalog_item_does_not_satisfy_legacy_order_skus(): void
    {
        $this->loginAsRole('MARKETING');
        Product::create(['sku' => 'A00000714', 'name' => 'Granit 60 x 60', 'category' => 'Bangunan', 'unit' => 'M', 'stock' => 100, 'sell_price' => 0]);
        Product::create(['sku' => 'A00001678', 'name' => 'Pasir Pasang', 'category' => 'Bangunan', 'unit' => 'M3', 'stock' => 100, 'sell_price' => 0]);
        $details = ['clientId' => 1, 'clientName' => 'Customer Uji', 'projectName' => 'Pengadaan Material'];
        $legacy = [
            ['sku' => 'MAT-007', 'name' => 'Granit Tile 60x60 Polished Glazed', 'unit' => 'Dus', 'qty' => 1, 'price' => 215000],
            ['sku' => 'MAT-004', 'name' => 'Pasir Pasang Ekstra Bangunan', 'unit' => 'M3', 'qty' => 1, 'price' => 320000],
        ];
        $this->putJson('/api/orders/ORD-CATALOG', ['version' => 0, 'details' => $details, 'items' => $legacy])
            ->assertOk()->assertJsonPath('data.stockReadiness.items.0.catalogFound', false);

        $this->submitOrder('ORD-CATALOG')->assertUnprocessable()->assertJsonPath('errors.items.0',
            'Barang belum terdaftar: Granit Tile 60x60 Polished Glazed dan 1 lainnya. Pilih ulang di Edit Order.');

        $this->assertDatabaseCount('sales_order_allocations', 0);
        $this->assertSame('Draft', SalesOrder::where('order_number', 'ORD-CATALOG')->first()->status);
        $this->assertDatabaseHas('products', ['sku' => 'A00000714', 'stock' => 100]);
    }

    public function test_marketing_can_submit_real_stock_after_explicitly_selecting_catalog_items_and_order_prices(): void
    {
        $this->loginAsRole('MARKETING');
        Product::create(['sku' => 'A00000714', 'name' => 'Granit 60 x 60', 'category' => 'Bangunan', 'unit' => 'M', 'stock' => 100, 'sell_price' => 0]);
        $details = ['clientId' => 1, 'clientName' => 'Customer Uji', 'projectName' => 'Pengadaan Material'];
        $legacy = [['sku' => 'MAT-007', 'name' => 'Granit Tile Lama', 'unit' => 'Dus', 'qty' => 1, 'price' => 215000]];
        $saved = $this->putJson('/api/orders/ORD-CATALOG', ['version' => 0, 'details' => $details, 'items' => $legacy])->assertOk()->json('data');
        $selected = [['sku' => 'A00000714', 'name' => 'Granit 60 x 60', 'unit' => 'M', 'qty' => 2, 'price' => 100000]];

        $this->putJson('/api/orders/ORD-CATALOG', ['version' => $saved['version'], 'details' => $details, 'items' => $selected])
            ->assertOk()->assertJsonPath('data.stockReadiness.status', 'Siap Diajukan')
            ->assertJsonPath('data.stockReadiness.items.0.catalogFound', true)
            ->assertJsonPath('data.stockReadiness.items.0.allocated', 2);
        $this->submitOrder('ORD-CATALOG')->assertOk()->assertJsonPath('data.status', 'Siap Invoice')
            ->assertJsonPath('data.items.0.unit', 'M')->assertJsonPath('data.grandTotal', 222000);

        $this->assertDatabaseHas('products', ['sku' => 'A00000714', 'stock' => 100, 'sell_price' => 0]);
        $this->assertSame(2, (int) DB::table('sales_order_allocations')->sum('qty'));
    }

    public function test_submitted_details_cannot_be_edited_deleted_or_submitted_twice(): void
    {
        $order = $this->draft(5_000_000);
        $this->submitOrder('ORD-T22')->assertOk();
        $payload = ['version' => $order->fresh()->version, 'details' => $order->details, 'items' => $order->items];
        $payload['items'][0]['price'] = 100;
        $this->putJson('/api/orders/ORD-T22', $payload)->assertConflict();
        $this->deleteJson('/api/orders/ORD-T22')->assertConflict();
        $this->submitOrder('ORD-T22')->assertConflict();
        $this->assertSame(5_550_000, $order->fresh()->grand_total);
        $this->assertCount(2, $order->fresh()->history);
    }

    public function test_stale_update_is_refused_and_another_marketing_can_submit(): void
    {
        $order = $this->draft();
        $payload = ['version' => 0, 'details' => $order->details, 'items' => $order->items];
        $this->putJson('/api/orders/ORD-T22', $payload)->assertConflict();
        $this->loginAsRole('MARKETING');
        $this->submitOrder('ORD-T22')->assertOk();
        $this->assertCount(2, $order->fresh()->history);
    }

    public function test_director_approval_persists_actor_time_and_releases_finance(): void
    {
        $order = $this->draft(5_000_000);
        $this->submitOrder('ORD-T22')->assertOk();
        $director = $this->loginAsRole('DIREKTUR');
        $this->freezeTime();
        $this->postJson('/api/orders/ORD-T22/approve', ['notes' => 'Sesuai data final.'])
            ->assertOk()->assertJsonPath('data.status', 'Siap Invoice')->assertJsonPath('data.approvedById', $director->id);
        $this->assertSame($director->id, $order->fresh()->approved_by);
        $this->assertSame(now()->toDateTimeString(), $order->fresh()->approved_at->toDateTimeString());
        $this->assertSame('Sesuai data final.', $order->fresh()->approval_notes);
        $this->postJson('/api/orders/ORD-T22/approve')->assertConflict();
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.status', 'Siap Invoice');
    }

    public function test_finance_cannot_approve_or_invoice_a_pending_order(): void
    {
        $order = $this->draft(5_000_000);
        $this->submitOrder('ORD-T22')->assertOk();
        $this->loginAsRole('FINANCE');
        $this->postJson('/api/orders/ORD-T22/approve')->assertForbidden();
        $this->postJson('/api/orders/ORD-T22/invoice', $this->invoicePayload())->assertConflict();
        $this->assertNull($order->fresh()->invoice);
        $this->assertSame('Menunggu Verifikasi', $order->fresh()->status);
    }

    public function test_invoice_uses_approved_total_and_cannot_be_duplicated(): void
    {
        $order = $this->draft();
        $this->submitOrder('ORD-T22')->assertOk();
        $this->loginAsRole('FINANCE');
        $this->postJson('/api/orders/ORD-T22/invoice', [...$this->invoicePayload(), 'totalAmount' => 1])
            ->assertOk()->assertJsonPath('data.invoice.totalAmount', 4_440_000)
            ->assertJsonPath('data.invoice.terminCount', 1)->assertJsonPath('data.status', 'Menunggu Pembayaran');
        $this->postJson('/api/orders/ORD-T22/invoice', $this->invoicePayload())->assertConflict();
        $this->assertSame(4_440_000, $order->fresh()->invoice['totalAmount']);
        $this->assertCount(3, $order->fresh()->history);
    }

    public function test_invalid_item_and_invoice_dates_return_422(): void
    {
        $order = $this->draft();
        $this->putJson('/api/orders/ORD-T22', ['version' => $order->version, 'details' => $order->details,
            'items' => [[...$order->items[0], 'qty' => 0, 'price' => -1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.qty', 'items.0.price']);
        $this->submitOrder('ORD-T22')->assertOk();
        $this->loginAsRole('FINANCE');
        $this->postJson('/api/orders/ORD-T22/invoice', [...$this->invoicePayload(), 'dueDate' => '2026-01-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('dueDate');
        $this->assertNull($order->fresh()->invoice);
    }

    #[TestWith(['CLIENT'])]
    #[TestWith(['SUPPLIER'])]
    public function test_portals_cannot_read_internal_orders(string $role): void
    {
        $this->loginAsRole($role);
        $this->getJson('/api/orders')->assertForbidden();
    }

    private function submitOrder(string $number, array $data = []): TestResponse
    {
        $order = SalesOrder::where('order_number', $number)->firstOrFail();

        return $this->postJson('/api/orders/'.$number.'/submit', [...$data, 'version' => $order->version]);
    }

    public function test_project_metadata_survives_refresh_and_submission_rejects_an_unreviewed_version(): void
    {
        $order = $this->draft();
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.clientId', 1)
            ->assertJsonPath('data.0.projectId', 'PRJ-T22')->assertJsonPath('data.0.description', 'Metadata tersimpan');
        $this->postJson('/api/orders/ORD-T22/submit', ['version' => 1])->assertConflict();
        $this->assertSame('Draft', $order->fresh()->status);
        $this->assertNull($order->fresh()->submitted_at);
    }

    private function invoicePayload(): array
    {
        return ['invoiceDate' => '2026-11-06', 'dueDate' => '2026-11-20', 'paymentType' => 'Penuh', 'terminCount' => 4];
    }

    public function test_competing_orders_cannot_submit_against_the_same_stock(): void
    {
        $first = $this->draft(stock: 1);
        $this->putJson('/api/orders/ORD-T22-OTHER', ['version' => 0, 'details' => $first->details, 'items' => $first->items])
            ->assertOk()->assertJsonPath('data.stockReadiness.status', 'Menunggu Stok')
            ->assertJsonPath('data.stockReadiness.items.0.shortage', 1);
        $this->submitOrder('ORD-T22')->assertOk();
        $this->submitOrder('ORD-T22-OTHER')->assertUnprocessable();
        $this->assertSame(1, (int) DB::table('sales_order_allocations')->sum('qty'));
        $this->assertSame(1, Product::where('sku', 'T22-ITEM')->first()->stock);
        $this->getJson('/api/products?search=T22-ITEM')->assertOk()
            ->assertJsonPath('data.0.available_stock', 0)->assertJsonPath('data.0.allocated_stock', 1);
    }

    public function test_changing_or_deleting_a_draft_releases_its_allocation(): void
    {
        $first = $this->draft(stock: 2);
        $item = [...$first->items[0], 'qty' => 2];
        $this->putJson('/api/orders/ORD-T22', ['version' => $first->version, 'details' => $first->details, 'items' => [$item]])->assertOk();
        $this->putJson('/api/orders/ORD-T22-OTHER', ['version' => 0, 'details' => $first->details, 'items' => $first->items])->assertOk();
        $this->postJson('/api/orders/ORD-T22-OTHER/stock-check')->assertOk()->assertJsonPath('data.stockReadiness.status', 'Menunggu Stok');
        $this->deleteJson('/api/orders/ORD-T22')->assertOk();
        $this->postJson('/api/orders/ORD-T22-OTHER/stock-check')->assertOk()->assertJsonPath('data.stockReadiness.status', 'Siap Diajukan');
        $this->submitOrder('ORD-T22-OTHER')->assertOk();
        $this->assertSame(1, (int) DB::table('sales_order_allocations')->sum('qty'));
    }

    public function test_director_cannot_approve_if_physical_stock_was_corrected_down(): void
    {
        $order = $this->draft(5_000_000, stock: 1);
        $this->submitOrder('ORD-T22')->assertOk();
        Product::where('sku', 'T22-ITEM')->update(['stock' => 0]);
        $this->loginAsRole('DIREKTUR');
        $this->postJson('/api/orders/ORD-T22/approve')->assertUnprocessable();
        $this->assertSame('Menunggu Verifikasi', $order->fresh()->status);
        $this->assertNull($order->fresh()->approved_by);
    }

    public function test_document_generation_cannot_bypass_pending_approval(): void
    {
        $order = $this->draft(5_000_000);
        $order->update(['details' => [...$order->details, 'projectId' => 'PRJ-T22']]);
        $this->submitOrder('ORD-T22')->assertOk();
        $this->loginAsRole('FINANCE');
        DocumentTemplate::create(['name' => 'Faktur', 'code' => 'FAKTUR-T22', 'category' => 'FAKTUR', 'type' => 'docx',
            'file_path' => 'unused.docx', 'file_name' => 'unused.docx']);
        $this->postJson('/api/documents/generate', ['template_code' => 'FAKTUR-T22', 'order_number' => 'ORD-T22', 'variables' => ['nilai' => 1]])
            ->assertConflict();
        $project = SpjProject::where('project_id', 'PRJ-T22')->firstOrFail();
        $result = app(SpjService::class)->validateDocumentData($project, 'faktur');
        $this->assertFalse($result['is_complete']);
        $this->assertContains('Pesanan belum mendapat persetujuan valid untuk invoice', $result['missing']);
        $this->assertNull($order->fresh()->invoice);
    }
}
