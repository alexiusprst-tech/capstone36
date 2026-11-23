<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WarehousePurchaseDraftTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function role(string $role): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => $role]));
    }

    private function payload(): array
    {
        $this->travelTo(Carbon::parse('2026-11-20T12:00:00+07:00'));
        $this->role('MARKETING');
        $customer = Customer::factory()->create();
        Product::create(['sku' => 'DRAFT-PURCHASE', 'name' => 'Barang Pembelian', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 2]);
        $this->putJson('/api/orders/ORD-PURCHASE-DRAFT', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'Project Pembelian'],
            'items' => [['sku' => 'DRAFT-PURCHASE', 'name' => 'Barang Pembelian', 'unit' => 'PCS', 'qty' => 5, 'price' => 150]],
        ])->assertOk();
        $supplier = Supplier::create(['code' => 'SUP-DRAFT', 'name' => 'Supplier Pengajuan', 'is_active' => true]);
        $this->role('WAREHOUSE');

        return ['order_number' => 'ORD-PURCHASE-DRAFT', 'purpose' => 'Kebutuhan pembelajaran', 'notes' => 'Dari form Gudang',
            'procurement_draft' => ['transaction_date' => '2026-11-20', 'supplier_id' => $supplier->id,
                'invoice_number' => 'FAKTUR-DRAFT-1', 'payment_method' => 'Tunai', 'discount' => 0.10],
            'items' => [['sku' => 'DRAFT-PURCHASE', 'qty' => 3, 'est_price' => 100.10, 'est_discount' => 0.05]],
        ];
    }

    public function test_warehouse_persists_all_purchase_form_values_with_exact_total_without_issuing_po_or_stock(): void
    {
        $payload = $this->payload();
        $saved = $this->postJson('/api/warehouse/requests', $payload)->assertCreated()
            ->assertJsonPath('data.procurement_draft.default_supplier', 'Supplier Pengajuan')
            ->assertJsonPath('data.procurement_draft.invoice_number', 'FAKTUR-DRAFT-1')
            ->assertJsonPath('data.procurement_draft.transaction_date', '2026-11-20')
            ->assertJsonPath('data.procurement_draft.payment_method', 'Tunai')
            ->assertJsonPath('data.procurement_draft.discount', 0.1)
            ->assertJsonPath('data.procurement_draft.subtotal', 300.15)
            ->assertJsonPath('data.procurement_draft.total_price', 300.05)
            ->assertJsonPath('data.total_est_cost', '300.05')->json('data');
        $this->assertStringStartsWith('PB20261120', $saved['procurement_draft']['purchase_code']);
        $this->assertNull($saved['purchase_code']);
        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->assertDatabaseHas('products', ['sku' => 'DRAFT-PURCHASE', 'stock' => 2]);
        $this->getJson('/api/warehouse/requests')->assertOk()->assertJsonPath('data.0.procurement_draft', $saved['procurement_draft']);
    }

    #[TestWith(['procurement_draft.supplier_id', 999999])]
    #[TestWith(['procurement_draft.transaction_date', 'not-a-date'])]
    #[TestWith(['procurement_draft.payment_method', 'Lunas'])]
    #[TestWith(['procurement_draft.discount', 400])]
    #[TestWith(['items.0.est_discount', 101])]
    #[TestWith(['procurement_draft', []])]
    #[TestWith(['procurement_draft.purchase_code', 'FAKE-CODE'])]
    public function test_invalid_draft_is_rejected_without_creating_a_request(string $field, mixed $value): void
    {
        $payload = $this->payload();
        data_set($payload, $field, $value);
        $this->postJson('/api/warehouse/requests', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_requests', 0);
        $this->assertDatabaseCount('purchase_transactions', 0);
        $this->assertDatabaseHas('products', ['sku' => 'DRAFT-PURCHASE', 'stock' => 2]);
    }

    public function test_inactive_supplier_cannot_be_selected(): void
    {
        $payload = $this->payload();
        Supplier::whereKey($payload['procurement_draft']['supplier_id'])->update(['is_active' => false]);
        $this->postJson('/api/warehouse/requests', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('warehouse_requests', 0);
    }

    public function test_finance_can_issue_the_persisted_draft_code_and_values_as_the_actual_po(): void
    {
        $saved = $this->postJson('/api/warehouse/requests', $this->payload())->assertCreated()->json('data');
        $this->role('FINANCE');
        $draft = $saved['procurement_draft'];
        $this->postJson('/api/purchases', [...$draft, 'warehouse_request_code' => $saved['request_code'],
            'items' => [['sku' => 'DRAFT-PURCHASE', 'unit' => 'PCS', 'qty' => 3, 'harga_beli' => 100.10, 'diskon_nominal' => 0.05]],
        ])->assertCreated()->assertJsonPath('data.purchase_code', $draft['purchase_code'])
            ->assertJsonPath('data.status', 'DIPESAN')->assertJsonPath('data.total_price', '300.05');
        $this->assertDatabaseHas('warehouse_requests', ['id' => $saved['id'], 'purchase_code' => $draft['purchase_code']]);
        $this->assertDatabaseHas('products', ['sku' => 'DRAFT-PURCHASE', 'stock' => 2]);
    }
}
