<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/** Synthetic procurement cases; never run against the operational database. */
class Caps23ApprovalCasesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    private function role(string $role): User
    {
        $user = User::factory()->create(['role' => $role, 'name' => 'Petugas QA '.$role]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function draft(int $qty = 1, int $stock = 5, float $price = 1_000_000): array
    {
        $this->travelTo(Carbon::parse('2026-11-20T09:00:00+07:00'));
        $marketing = $this->role('MARKETING');
        $customer = Customer::factory()->create(['name' => 'SMK Pengadaan QA']);
        Product::create(['sku' => 'CAPS23-LAPTOP', 'name' => 'Laptop Laboratorium QA', 'unit' => 'PCS', 'category' => 'QA', 'stock' => $stock]);
        $saved = $this->putJson('/api/orders/ORD-CAPS23-QA', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'Pengadaan Laboratorium QA', 'date' => '2026-11-20'],
            'items' => [['sku' => 'CAPS23-LAPTOP', 'name' => 'Laptop Laboratorium QA', 'unit' => 'PCS', 'qty' => $qty, 'price' => $price]],
        ])->assertOk()->json('data');

        return [$saved, $marketing];
    }

    public function test_qc_rejection_blocks_submission_until_replacements_are_received_then_director_releases_invoice(): void
    {
        [$draft, $marketing] = $this->draft(qty: 5, stock: 2);
        $this->assertSame(3, $draft['stockReadiness']['items'][0]['shortage']);
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])->assertUnprocessable();

        $this->role('WAREHOUSE');
        $mr = $this->postJson('/api/warehouse/requests', ['order_number' => 'ORD-CAPS23-QA', 'purpose' => 'Melengkapi tiga laptop laboratorium',
            'items' => [['sku' => 'CAPS23-LAPTOP', 'qty' => 3, 'est_price' => 800000]],
        ])->assertCreated()->json('data');
        Supplier::create(['code' => 'SUP-CAPS23', 'name' => 'Supplier Laboratorium QA', 'is_active' => true]);
        $this->role('FINANCE');
        $po = $this->postJson('/api/purchases', ['purchase_code' => 'PO-CAPS23-QA', 'transaction_date' => '2026-11-20',
            'warehouse_request_code' => $mr['request_code'], 'default_supplier' => 'Supplier Laboratorium QA', 'payment_method' => 'Transfer',
            'items' => [['sku' => 'CAPS23-LAPTOP', 'unit' => 'PCS', 'qty' => 3, 'harga_beli' => 800000]],
        ])->assertCreated()->json('data');
        $this->role('WAREHOUSE');
        $partial = $this->postJson('/api/purchases/PO-CAPS23-QA/receive', ['version' => $po['progress_version'],
            'idempotency_key' => 'e7c0585b-f1fa-4f54-a972-8cd2cd596401',
            'items' => [['line_index' => 0, 'accepted_qty' => 1, 'rejected_qty' => 1, 'reason' => 'Layar rusak saat QC']], 'notes' => 'Satu unit layar rusak saat QC; satu unit belum dikirim.',
        ])->assertOk()->json('data.purchase');
        Sanctum::actingAs($marketing);
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.stockReadiness.items.0.shortage', 2);
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])->assertUnprocessable();
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-CAPS23-QA', 'status' => 'Draft', 'submitted_at' => null]);

        $this->role('FINANCE');
        $followup = $this->postJson('/api/purchases/PO-CAPS23-QA/supplier-progress', ['action' => 'followup', 'version' => $partial['progress_version'],
            'progress' => ['followup_reference' => 'QC-CAPS23-01', 'followup_notes' => 'Supplier mengganti unit rusak dan melengkapi unit yang kurang.'],
        ])->assertOk()->json('data');
        $this->role('WAREHOUSE');
        $this->postJson('/api/purchases/PO-CAPS23-QA/receive', ['version' => $followup['progress_version'],
            'idempotency_key' => '319cc78a-578b-429f-b87c-cbfc80aa66ce',
            'items' => [['line_index' => 0, 'accepted_qty' => 2, 'rejected_qty' => 0]],
        ])->assertOk();
        Sanctum::actingAs($marketing);
        $ready = $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.stockReadiness.status', 'Siap Diajukan')->json('data.0');
        $submitted = $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $ready['version']])
            ->assertOk()->assertJsonPath('data.status', 'Menunggu Verifikasi')->assertJsonPath('data.grandTotal', 5550000)->json('data');
        $this->assertSame($marketing->id, $submitted['submittedById']);
        $this->role('FINANCE');
        $invoice = ['invoiceDate' => '2026-11-20', 'dueDate' => '2026-11-27'];
        $this->postJson('/api/orders/ORD-CAPS23-QA/invoice', $invoice)->assertConflict();
        $director = $this->role('DIREKTUR');
        $this->postJson('/api/orders/ORD-CAPS23-QA/approve', ['notes' => 'QC lengkap; sesuai pesanan lima laptop.'])
            ->assertOk()->assertJsonPath('data.status', 'Siap Invoice')->assertJsonPath('data.approvedById', $director->id);
        $this->role('FINANCE');
        $this->postJson('/api/orders/ORD-CAPS23-QA/invoice', $invoice)->assertOk()->assertJsonPath('data.invoice.totalAmount', 5550000);
        $this->assertDatabaseHas('products', ['sku' => 'CAPS23-LAPTOP', 'stock' => 5]);
        $this->assertDatabaseCount('purchase_receipts', 2);
        $this->assertSame(3, (int) DB::table('inventory_movements')->where('type', 'RECEIPT')->sum('delta'));
        $this->assertSame(5, (int) DB::table('sales_order_allocations')->sum('qty'));
    }

    #[TestWith(['MARKETING'])]
    #[TestWith(['WAREHOUSE'])]
    #[TestWith(['ADMINISTRASI'])]
    #[TestWith(['CLIENT'])]
    #[TestWith(['SUPPLIER'])]
    public function test_non_director_cannot_approve_or_append_an_approval_history(string $role): void
    {
        [$draft] = $this->draft(price: 5_000_000);
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])->assertOk();
        $before = SalesOrder::where('order_number', 'ORD-CAPS23-QA')->firstOrFail();
        $this->role($role);
        $this->postJson('/api/orders/ORD-CAPS23-QA/approve', ['notes' => 'Tidak berwenang'])->assertForbidden();
        $this->assertSame($before->history, $before->fresh()->history);
        $this->assertNull($before->fresh()->approved_at);
        $this->assertSame('Menunggu Verifikasi', $before->fresh()->status);
    }

    public function test_a_second_stock_review_invalidates_the_first_confirmation_without_changing_physical_stock(): void
    {
        [$draft] = $this->draft();
        $reviewed = $this->postJson('/api/orders/ORD-CAPS23-QA/stock-check')->assertOk()->json('data');
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])->assertConflict()
            ->assertJsonPath('message', 'Rincian pesanan berubah. Periksa ulang sebelum mengajukan.');
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-CAPS23-QA', 'status' => 'Draft', 'submitted_at' => null]);
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $reviewed['version']])->assertOk();
        $this->assertDatabaseHas('products', ['sku' => 'CAPS23-LAPTOP', 'stock' => 5]);
        $this->assertSame(1, (int) DB::table('sales_order_allocations')->sum('qty'));
    }

    public function test_submit_and_approve_require_authentication_before_accessing_an_order(): void
    {
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => 1])->assertUnauthorized();
        $this->postJson('/api/orders/ORD-CAPS23-QA/approve', ['notes' => 'Tanpa sesi'])->assertUnauthorized();
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_submitter_name_is_the_recorded_actor_snapshot_and_survives_an_account_name_change(): void
    {
        [$draft, $marketing] = $this->draft();
        $submitted = $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])
            ->assertOk()->assertJsonPath('data.submittedBy', 'Petugas QA MARKETING')->json('data');
        User::whereKey($marketing->id)->update(['name' => 'Nama Akun Setelah Pengajuan']);
        $this->role('DIREKTUR');
        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.submittedBy', 'Petugas QA MARKETING')
            ->assertJsonPath('data.0.submittedById', $marketing->id)->assertJsonPath('data.0.submittedAt', $submitted['submittedAt']);
    }

    public function test_director_cannot_approve_an_unsubmitted_high_value_draft(): void
    {
        [$draft] = $this->draft(price: 5_000_000);
        $this->role('DIREKTUR');
        $this->postJson('/api/orders/ORD-CAPS23-QA/approve')->assertConflict();
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-CAPS23-QA', 'status' => 'Draft', 'version' => $draft['version'], 'approved_at' => null]);
    }

    public function test_director_cannot_add_an_unnecessary_approval_to_an_order_at_exactly_five_million(): void
    {
        [$draft] = $this->draft(price: 4_504_504.50);
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $draft['version']])
            ->assertOk()->assertJsonPath('data.grandTotal', 5000000)->assertJsonPath('data.status', 'Siap Invoice');
        $this->role('DIREKTUR');
        $this->postJson('/api/orders/ORD-CAPS23-QA/approve')->assertConflict();
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-CAPS23-QA', 'status' => 'Siap Invoice', 'approved_at' => null]);
    }

    public function test_stock_shortage_on_any_line_prevents_submission_of_the_entire_order(): void
    {
        [$draft] = $this->draft();
        Product::create(['sku' => 'CAPS23-MONITOR', 'name' => 'Monitor QA', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 0]);
        $order = SalesOrder::where('order_number', 'ORD-CAPS23-QA')->firstOrFail();
        $saved = $this->putJson('/api/orders/ORD-CAPS23-QA', ['version' => $draft['version'], 'details' => $order->details,
            'items' => [...$order->items, ['sku' => 'CAPS23-MONITOR', 'name' => 'Monitor QA', 'unit' => 'PCS', 'qty' => 1, 'price' => 100000]],
        ])->assertOk()->json('data');
        $this->postJson('/api/orders/ORD-CAPS23-QA/submit', ['version' => $saved['version']])->assertUnprocessable()
            ->assertJsonValidationErrors('items')->assertJsonPath('errors.items.0', 'Stok kurang: Monitor QA (1 PCS).');
        $this->assertSame('Draft', $order->fresh()->status);
        $this->assertNull($order->fresh()->submitted_by);
    }

    #[TestWith([800000, 800000])]
    #[TestWith([0, null])]
    public function test_review_exposes_catalog_hpp_without_substituting_a_percentage_of_selling_price(int $hpp, ?int $expected): void
    {
        $this->draft();
        Product::where('sku', 'CAPS23-LAPTOP')->update(['hpp' => $hpp]);
        $response = $this->getJson('/api/orders')->assertOk()->assertJsonStructure(['data' => [['stockReadiness' => ['items' => [['estimatedUnitCost']]]]]]);
        $this->assertSame($expected, $response->json('data.0.stockReadiness.items.0.estimatedUnitCost'));
    }
}
