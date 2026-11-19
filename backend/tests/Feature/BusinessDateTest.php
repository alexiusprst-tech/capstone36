<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_accept_the_current_wib_date_across_the_utc_midnight_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-11-19T00:30:00+07:00'));
        $this->assertSame('2026-11-19', now(config('app.business_timezone'))->toDateString());
        $this->assertSame('2026-11-18', now()->toDateString());
        $this->assertSame('2026-11-18', now('UTC')->toDateString());
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        Product::create(['sku' => 'DATE-QA', 'name' => 'Barang tanggal', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 0]);
        PurchaseTransaction::create(['purchase_code' => 'PO-DATE', 'transaction_date' => '2026-11-18', 'project_name' => 'Uji tanggal',
            'payment_method' => 'Transfer', 'status' => 'DIPESAN', 'progress_version' => 1, 'subtotal' => 100, 'total_price' => 100,
            'items' => [['sku' => 'DATE-QA', 'barang' => 'Barang tanggal', 'unit' => 'PCS', 'qty' => 2]]]);
        $payload = ['version' => 1, 'received_date' => '2026-11-20', 'idempotency_key' => '6e0e23a1-104b-4691-b9c6-aa1fbb5a59af',
            'items' => [['line_index' => 0, 'accepted_qty' => 1, 'rejected_qty' => 0]]];
        $this->postJson('/api/purchases/PO-DATE/receive', $payload)->assertUnprocessable()->assertJsonValidationErrors('received_date');
        $payload['received_date'] = '2026-11-19';
        $this->postJson('/api/purchases/PO-DATE/receive', $payload)->assertOk();
        unset($payload['received_date']);
        $payload['version'] = 2;
        $payload['idempotency_key'] = 'e05d628e-5fdc-4ab2-9994-4f3fa2ee5825';
        $this->postJson('/api/purchases/PO-DATE/receive', $payload)->assertOk()->assertJsonPath('data.receipt.received_date', '2026-11-19');
        $this->assertDatabaseHas('purchase_receipts', ['received_date' => '2026-11-19']);
        $this->assertDatabaseHas('products', ['sku' => 'DATE-QA', 'stock' => 2]);
    }

    public function test_material_request_uses_the_wib_year_without_changing_utc_timestamps(): void
    {
        $this->travelTo(Carbon::parse('2027-01-01T00:30:00+07:00'));
        $user = User::factory()->create(['role' => 'WAREHOUSE']);
        Sanctum::actingAs($user);
        Product::create(['sku' => 'NEW-YEAR', 'name' => 'Barang tahun baru', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 0]);
        SalesOrder::create(['order_number' => 'ORD-NEW-YEAR', 'created_by' => $user->id, 'details' => ['projectName' => 'Tahun baru WIB'],
            'items' => [['sku' => 'NEW-YEAR', 'name' => 'Barang tahun baru', 'qty' => 1, 'unit' => 'PCS']], 'history' => [], 'subtotal' => 0, 'tax' => 0, 'grand_total' => 0]);
        $response = $this->postJson('/api/warehouse/requests', ['order_number' => 'ORD-NEW-YEAR', 'purpose' => 'Kekurangan tanggal bisnis WIB',
            'items' => [['sku' => 'NEW-YEAR', 'qty' => 1, 'est_price' => 0]]])->assertCreated()->assertJsonPath('data.request_date', '2027-01-01');
        $this->assertStringStartsWith('REQ-2027-', $response->json('data.request_code'));
        $this->assertSame('2026-12-31', now()->toDateString());
        $this->assertDatabaseHas('warehouse_requests', ['created_at' => '2026-12-31 17:30:00']);
    }
}
