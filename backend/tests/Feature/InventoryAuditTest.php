<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\SpjDocument;
use App\Models\SpjProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_stock_reserved_by_an_active_order(): void
    {
        $user = User::factory()->create(['role' => 'WAREHOUSE']);
        Sanctum::actingAs($user);
        $product = Product::create(['sku' => 'RESERVED', 'name' => 'Barang', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 10]);
        $order = SalesOrder::create(['order_number' => 'RESERVE', 'created_by' => $user->id, 'details' => [], 'items' => [], 'history' => [], 'subtotal' => 0, 'tax' => 0, 'grand_total' => 0]);
        DB::table('sales_order_allocations')->insert(['sales_order_id' => $order->id, 'product_id' => $product->id, 'qty' => 8, 'created_at' => now(), 'updated_at' => now()]);

        $this->postJson('/api/products/import', ['items' => [['sku' => 'RESERVED', 'name' => 'Barang', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 2]], 'duplicate_mode' => 'update'])->assertConflict();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseHas('sales_order_allocations', ['product_id' => $product->id, 'qty' => 8]);
    }

    public function test_receipt_of_an_explicit_new_sku_does_not_change_a_similarly_named_product(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $product = Product::create(['sku' => 'SKU-OLD', 'name' => 'Semen', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 10]);
        PurchaseTransaction::create(['purchase_code' => 'PO-EXACT', 'transaction_date' => '2026-11-19', 'project_name' => 'QA', 'subtotal' => 100, 'total_price' => 100, 'payment_method' => 'Transfer', 'items' => [['sku' => 'SKU-NEW', 'barang' => 'Semen', 'qty' => 1, 'harga_beli' => 100, 'subtotal' => 100]]]);

        $payload = ['version' => 0, 'idempotency_key' => 'd66a56fe-d731-402e-93a5-046b9e779ac9', 'items' => [['line_index' => 0, 'accepted_qty' => 1, 'rejected_qty' => 0]]];
        $this->postJson('/api/purchases/PO-EXACT/receive', $payload)->assertUnprocessable();
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-NEW']);
        Product::create(['sku' => 'SKU-NEW', 'name' => 'Semen', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 0]);
        $this->postJson('/api/purchases/PO-EXACT/receive', $payload)->assertOk();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-NEW', 'stock' => 1]);
    }

    public function test_partial_payment_cannot_generate_a_paid_receipt(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        SpjProject::create(['project_id' => 'QA-PARTIAL', 'project_name' => 'Proyek', 'customer_name' => 'Customer QA', 'items' => [['item_name' => 'Barang', 'quantity' => 1, 'price' => 100]], 'project_value' => 100, 'invoice_value' => 100, 'invoice_paid' => 1, 'finance_status' => 'LUNAS']);
        SpjDocument::create(['project_id' => 'QA-PARTIAL', 'doc_key' => 'kuitansi', 'doc_order' => 1, 'doc_name' => 'Kuitansi', 'status' => 'DRAFT']);

        $this->postJson('/api/spj/projects/QA-PARTIAL/documents/kuitansi/generate', ['form_data' => []])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertDatabaseHas('spj_documents', ['project_id' => 'QA-PARTIAL', 'doc_key' => 'kuitansi', 'status' => 'DRAFT']);
    }

    public function test_spj_value_follows_the_latest_saved_order_and_cannot_forge_payment(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        Product::create(['sku' => 'SYNC', 'name' => 'Barang', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 10]);
        $customer = Customer::factory()->create(['name' => 'Customer QA']);
        $payload = ['version' => 0, 'details' => ['clientId' => $customer->id, 'clientName' => 'Customer QA', 'projectName' => 'Proyek', 'projectId' => 'QA-SYNC'], 'items' => [['sku' => 'SYNC', 'name' => 'Barang', 'unit' => 'PCS', 'qty' => 1, 'price' => 100000]]];
        $saved = $this->putJson('/api/orders/QA-SYNC-ORDER', $payload)->assertOk();
        $payload['version'] = $saved->json('data.version');
        $payload['items'][0]['price'] = 200000;

        $this->putJson('/api/orders/QA-SYNC-ORDER', $payload)->assertOk()->assertJsonPath('data.grandTotal', 222000);

        $this->assertDatabaseHas('spj_projects', ['project_id' => 'QA-SYNC', 'project_value' => 222000]);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        $this->putJson('/api/spj/projects/QA-SYNC', ['invoice_paid' => 222000, 'finance_status' => 'LUNAS'])->assertForbidden();
        $this->assertDatabaseMissing('spj_projects', ['project_id' => 'QA-SYNC', 'finance_status' => 'LUNAS']);
    }
}
