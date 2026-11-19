<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\InventoryTransaction;
use App\Services\SalesOrderInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function warehouse(): User
    {
        $user = User::factory()->create(['role' => 'WAREHOUSE']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['sku' => 'LEDGER-SKU', 'name' => 'Barang Ledger', 'category' => 'QA', 'unit' => 'PCS', 'stock' => 10], $changes);
    }

    private function order(User $user, string $number, int $qty = 5): SalesOrder
    {
        return SalesOrder::create(['order_number' => $number, 'created_by' => $user->id,
            'details' => [], 'items' => [['sku' => 'LEDGER-SKU', 'name' => 'Barang Ledger', 'unit' => 'PCS', 'qty' => $qty, 'price' => 100]],
            'history' => [], 'subtotal' => $qty * 100, 'tax' => 0, 'grand_total' => $qty * 100]);
    }

    public function test_creation_adjustment_and_metadata_edit_have_an_auditable_stock_chain(): void
    {
        $user = $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 14, 'adjustment_reason' => 'Hasil opname']))->assertOk();
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 14, 'name' => 'Nama baru']))->assertOk();
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $id, 'actor_id' => $user->id, 'type' => 'OPENING', 'before_stock' => 0, 'after_stock' => 10, 'delta' => 10]);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $id, 'actor_id' => $user->id, 'type' => 'ADJUSTMENT', 'before_stock' => 10, 'after_stock' => 14, 'delta' => 4, 'reason' => 'Hasil opname', 'name_snapshot' => 'Barang Ledger']);
        $this->getJson('/api/inventory/movements?search=LEDGER-SKU&type=ADJUSTMENT')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.actor.name', $user->name)->assertJsonPath('data.0.delta', 4);
    }

    public function test_invalid_stock_and_sku_changes_never_mutate_product_or_ledger(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        foreach ([null, -1, 1000000001] as $stock) {
            $this->putJson('/api/products/'.$id, $this->payload(['stock' => $stock]))->assertUnprocessable()->assertJsonValidationErrors('stock');
        }
        $this->putJson('/api/products/'.$id, $this->payload(['sku' => 'REPLACED']))->assertConflict();
        $this->assertDatabaseHas('products', ['id' => $id, 'sku' => 'LEDGER-SKU', 'stock' => 10]);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_import_records_final_duplicate_value_once_and_preserves_zero_opening(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        $this->postJson('/api/products/import', ['duplicate_mode' => 'update', 'items' => [
            $this->payload(['stock' => 12]), $this->payload(['stock' => 15]),
            $this->payload(['sku' => 'ZERO', 'stock' => 0]),
        ]])->assertOk();
        $this->assertDatabaseCount('inventory_movements', 3);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $id, 'type' => 'ADJUSTMENT', 'before_stock' => 10, 'after_stock' => 15, 'delta' => 5, 'reference_type' => 'import']);
        $this->assertDatabaseHas('inventory_movements', ['sku_snapshot' => 'ZERO', 'type' => 'OPENING', 'delta' => 0]);
        $this->postJson('/api/products/import', ['duplicate_mode' => 'update', 'items' => [$this->payload(['stock' => 15])]])->assertOk();
        $this->assertDatabaseCount('inventory_movements', 3);
    }

    public function test_archive_preserves_stock_history_sku_and_restore(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        $this->deleteJson('/api/products/'.$id)->assertOk();
        $this->assertSoftDeleted('products', ['id' => $id]);
        $this->getJson('/api/products?search=LEDGER-SKU')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/inventory/movements?search=LEDGER-SKU')->assertOk()->assertJsonPath('meta.total', 1);
        $this->assertSame($id, InventoryMovement::firstOrFail()->product->id);
        $this->postJson('/api/products', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('sku');
        $this->postJson('/api/products/import', ['items' => [$this->payload()]])->assertConflict();
        $this->postJson('/api/products/'.$id.'/restore')->assertOk();
        $this->assertNull(Product::findOrFail($id)->deleted_at);
        $this->assertSame(10, Product::findOrFail($id)->stock);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_allocations_rebuild_after_stock_change_and_keep_physical_stock_separate(): void
    {
        $user = $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload(['stock' => 0]))->assertCreated()->json('data.id');
        $order = $this->order($user, 'DRAFT-A');
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 8]))->assertOk();
        $this->assertDatabaseHas('sales_order_allocations', ['sales_order_id' => $order->id, 'product_id' => $id, 'qty' => 5]);
        $this->getJson('/api/products?search=LEDGER-SKU')->assertOk()->assertJsonPath('data.0.stock', 8)->assertJsonPath('data.0.allocated_stock', 5)->assertJsonPath('data.0.available_stock', 3);
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 4]))->assertConflict();
        $this->deleteJson('/api/products/'.$id)->assertConflict();
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 8, 'unit' => 'BOX']))->assertConflict();
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_preferred_draft_receives_stock_first_without_taking_submitted_allocations(): void
    {
        $user = $this->warehouse();
        $product = Product::create($this->payload(['stock' => 10]));
        $submitted = $this->order($user, 'SUBMITTED', 4);
        $submitted->status = 'Siap Invoice';
        $submitted->save();
        $earlier = $this->order($user, 'EARLIER');
        $preferred = $this->order($user, 'PREFERRED');
        InventoryTransaction::run(function () use ($submitted, $preferred) {
            app(SalesOrderInventory::class)->allocate($submitted);
            app(SalesOrderInventory::class)->reallocateDrafts($preferred->id);
        });
        foreach ([[$submitted, 4], [$preferred, 5], [$earlier, 1]] as [$order, $qty]) {
            $this->assertDatabaseHas('sales_order_allocations', ['sales_order_id' => $order->id, 'product_id' => $product->id, 'qty' => $qty]);
        }
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_pending_purchase_blocks_archive_and_unit_reinterpretation(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        PurchaseTransaction::create(['purchase_code' => 'PO-OPEN', 'transaction_date' => '2026-11-19', 'subtotal' => 100, 'total_price' => 100, 'payment_method' => 'Transfer', 'items' => [['sku' => 'LEDGER-SKU', 'barang' => 'Barang Ledger', 'unit' => 'PCS', 'qty' => 1, 'harga_beli' => 100, 'subtotal' => 100]]]);
        $this->deleteJson('/api/products/'.$id)->assertConflict();
        $this->putJson('/api/products/'.$id, $this->payload(['unit' => 'BOX']))->assertConflict();
        $this->postJson('/api/products/import', ['items' => [$this->payload(['unit' => 'BOX'])]])->assertConflict();
        $this->assertDatabaseHas('products', ['id' => $id, 'unit' => 'PCS', 'deleted_at' => null]);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_import_resolves_saved_sku_case_before_allocation_and_archive_guards(): void
    {
        $user = $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload())->assertCreated()->json('data.id');
        $order = $this->order($user, 'CASE-ORDER', 8);
        InventoryTransaction::run(fn () => app(SalesOrderInventory::class)->allocate($order));
        $this->postJson('/api/products/import', ['items' => [$this->payload(['sku' => 'ledger-sku', 'stock' => 2])]])->assertConflict();
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $id, 'sku' => 'LEDGER-SKU', 'stock' => 10]);
        $this->postJson('/api/products/import', ['items' => [$this->payload(['sku' => 'ledger-sku', 'stock' => 12])]])->assertOk();
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $id, 'before_stock' => 10, 'after_stock' => 12, 'delta' => 2]);
    }

    public function test_ledger_requires_authentication_and_internal_role_and_rejects_writes(): void
    {
        $this->getJson('/api/inventory/movements')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => 'SUPPLIER']));
        $this->getJson('/api/inventory/movements')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role' => 'FINANCE']));
        $this->getJson('/api/inventory/movements')->assertOk();
        $this->postJson('/api/products', $this->payload())->assertForbidden();
        $this->getJson('/api/inventory/movements?type=FAKE')->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->getJson('/api/inventory/movements?per_page=1000')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_stock_status_uses_server_stock_and_configured_minimum(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload(['stock' => 11, 'min_stock' => 20, 'status' => 'Tersedia']))->assertCreated()->assertJsonPath('data.status', 'Menipis')->json('data.id');
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 11, 'min_stock' => 5, 'status' => 'Habis']))->assertOk()->assertJsonPath('data.status', 'Tersedia');
        $this->postJson('/api/products/import', ['items' => [$this->payload(['stock' => 5, 'min_stock' => 5, 'status' => 'Tersedia'])]])->assertOk();
        $this->assertDatabaseHas('products', ['id' => $id, 'status' => 'Menipis']);
        $this->putJson('/api/products/'.$id, $this->payload(['stock' => 0, 'min_stock' => 0, 'status' => 'Tersedia']))->assertOk()->assertJsonPath('data.status', 'Habis');
    }

    public function test_migration_records_opening_balance_without_applying_stock_twice(): void
    {
        $product = Product::create($this->payload(['stock' => 37]));
        $migration = require database_path('migrations/2026_11_19_110032_create_inventory_ledger_and_archive_products.php');
        $migration->down();
        $migration->up();
        $this->assertSame(37, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'actor_id' => null, 'reference_type' => 'migration', 'type' => 'OPENING', 'delta' => 37, 'before_stock' => 0, 'after_stock' => 37]);
        $this->assertSame(1, DB::table('inventory_locks')->count());
    }

    public function test_price_boundaries_reject_overflow_and_rounding_but_allow_clearing_prices(): void
    {
        $this->warehouse();
        $id = $this->postJson('/api/products', $this->payload(['sell_price' => null, 'hpp' => null, 'last_buy_price' => null]))->assertCreated()->assertJsonPath('data.sell_price', 0)->json('data.id');
        foreach (['last_buy_price', 'hpp', 'sell_price'] as $price) {
            foreach ([1000000000001, '1e99', '1.001'] as $value) {
                $this->putJson('/api/products/'.$id, $this->payload([$price => $value]))->assertUnprocessable()->assertJsonValidationErrors($price);
            }
        }
        $this->putJson('/api/products/'.$id, $this->payload(['sell_price' => 999.99]))->assertOk()->assertJsonPath('data.sell_price', 999.99);
        $this->putJson('/api/products/'.$id, $this->payload(['sell_price' => null]))->assertOk()->assertJsonPath('data.sell_price', 0);
        $this->postJson('/api/products/import', ['items' => [$this->payload(['sell_price' => '1.001'])]])->assertUnprocessable()->assertJsonValidationErrors('items.0.sell_price');
        $this->assertDatabaseCount('inventory_movements', 1);
    }
}
