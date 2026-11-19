<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Support\ProcurementFixture;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use ProcurementFixture;
    use RefreshDatabase;

    #[TestWith(['GET', '/api/products'])]
    #[TestWith(['POST', '/api/products'])]
    #[TestWith(['GET', '/api/suppliers'])]
    #[TestWith(['POST', '/api/purchases'])]
    #[TestWith(['GET', '/api/warehouse/requests'])]
    #[TestWith(['GET', '/api/spj/projects'])]
    #[TestWith(['PUT', '/api/spj/projects/UNKNOWN'])]
    #[TestWith(['GET', '/api/documents'])]
    #[TestWith(['GET', '/api/document-templates'])]
    public function test_operational_api_requires_authentication(string $method, string $url): void
    {
        $this->json($method, $url)->assertUnauthorized();
    }

    public function test_marketing_cannot_mutate_warehouse_or_finance_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $this->postJson('/api/products', [])->assertForbidden();
        $this->postJson('/api/suppliers', [])->assertForbidden();
        $this->postJson('/api/purchases', [])->assertForbidden();
        $this->putJson('/api/spj/projects/UNKNOWN', [])->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }

    #[TestWith(['password', 'password123'])]
    #[TestWith(['password123', 'password'])]
    public function test_alternate_demo_password_does_not_authenticate(string $saved, string $wrong): void
    {
        $user = User::factory()->create(['password' => $saved]);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => $wrong])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_session_login_does_not_accept_an_alternate_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'password123'])->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_unbound_client_cannot_download_another_customers_invoice(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'CLIENT']));
        $order = SalesOrder::create(['history' => [], 'created_by' => auth()->id(), 'order_number' => 'ORD-PRIVATE', 'details' => ['clientId' => 22], 'items' => [], 'subtotal' => 1, 'tax' => 0, 'grand_total' => 1]);
        $order->invoice = ['invoiceNumber' => 'INV-PRIVATE'];
        $order->save();
        $this->getJson('/api/orders/ORD-PRIVATE/invoice/download')->assertForbidden();
    }

    private function product(): Product
    {
        return Product::create(['sku' => 'QA-SKU', 'name' => 'Barang QA', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 10, 'min_stock' => 2]);
    }

    private function allocate(Product $product): void
    {
        $order = SalesOrder::create(['history' => [], 'created_by' => auth()->id(), 'order_number' => 'ORD-ALLOC', 'details' => [], 'items' => [], 'subtotal' => 0, 'tax' => 0, 'grand_total' => 0]);
        DB::table('sales_order_allocations')->insert(['sales_order_id' => $order->id, 'product_id' => $product->id, 'qty' => 8, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_stock_cannot_be_reduced_below_active_allocations(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $product = $this->product();
        $this->allocate($product);
        $this->putJson('/api/products/'.$product->id, ['sku' => $product->sku, 'name' => $product->name, 'category' => 'QA', 'unit' => 'PCS', 'stock' => 7])->assertConflict();
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_allocated_product_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $product = $this->product();
        $this->allocate($product);
        $this->deleteJson('/api/products/'.$product->id)->assertConflict();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_import_cannot_create_negative_stock(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'WAREHOUSE']));
        $this->postJson('/api/products/import', ['items' => [['sku' => 'BAD', 'name' => 'Barang', 'category' => 'QA', 'unit' => 'PCS', 'stock' => -1]]])->assertUnprocessable()->assertJsonValidationErrors('items.0.stock');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_purchase_totals_are_calculated_on_server(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'FINANCE', 'name' => 'Finance QA']));
        $mr = $this->linkedRequest([['sku' => 'QA-SKU', 'name' => 'Barang QA', 'qty' => 2]], 'Supplier QA');
        $this->postJson('/api/purchases', ['warehouse_request_id' => $mr->id, 'default_supplier' => 'Supplier QA', 'purchase_code' => 'PO-QA', 'transaction_date' => '2026-11-19', 'project_name' => 'QA', 'subtotal' => 1, 'total_price' => 1, 'discount' => 0, 'payment_method' => 'Transfer', 'created_by' => 'Pemalsu', 'items' => [['barang' => 'Barang QA', 'sku' => 'QA-SKU', 'qty' => 2, 'harga_beli' => 100000, 'subtotal' => 1]]])->assertCreated()
            ->assertJsonPath('data.subtotal', '200000.00')->assertJsonPath('data.total_price', '200000.00')->assertJsonPath('data.created_by', 'Finance QA');
    }

    public function test_supplier_cannot_list_other_suppliers_purchase_transactions(): void
    {
        Supplier::create(['code' => 'SUP-QA', 'name' => 'Supplier QA', 'email' => 'supplier-qa@example.test']);
        Sanctum::actingAs(User::factory()->create(['role' => 'SUPPLIER', 'email' => 'supplier-qa@example.test']));
        PurchaseTransaction::create(['purchase_code' => 'PO-OTHER', 'transaction_date' => '2026-11-19', 'project_name' => 'Rahasia', 'default_supplier' => 'Supplier Lain', 'subtotal' => 10, 'total_price' => 10, 'payment_method' => 'Transfer', 'items' => []]);
        $this->getJson('/api/purchases')->assertOk()->assertJsonCount(0, 'data');
    }
}
