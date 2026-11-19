<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(): array
    {
        return ['name' => 'Sekolah QA', 'orgFullName' => 'NAMA RESMI SEKOLAH QA',
            'contactPerson' => 'PIC QA', 'phone' => '081234567890',
            'address' => 'Jalan Pengujian 10', 'city' => 'JAKARTA TIMUR', 'email' => 'qa@example.test'];
    }

    public function test_customer_endpoints_require_a_valid_session(): void
    {
        $this->getJson('/api/customers')->assertUnauthorized();
        $this->postJson('/api/customers', $this->payload())->assertUnauthorized();
        $this->putJson('/api/customers/1', $this->payload())->assertUnauthorized();
        $this->deleteJson('/api/customers/1')->assertUnauthorized();
        $this->assertDatabaseCount('customers', 0);
    }

    #[TestWith(['FINANCE', true])]
    #[TestWith(['WAREHOUSE', true])]
    #[TestWith(['DIREKTUR', true])]
    #[TestWith(['ADMINISTRASI', true])]
    #[TestWith(['CLIENT', false])]
    #[TestWith(['SUPPLIER', false])]
    public function test_only_marketing_can_mutate_customer_data(string $role, bool $canRead): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => $role]));

        $response = $this->getJson('/api/customers');
        $canRead ? $response->assertOk() : $response->assertForbidden();
        $this->postJson('/api/customers', $this->payload())->assertForbidden();
        $this->putJson('/api/customers/'.$customer->id, [...$this->payload(), 'version' => 1])->assertForbidden();
        $this->deleteJson('/api/customers/'.$customer->id)->assertForbidden();
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('Customer Uji', $customer->fresh()->name);
    }

    #[TestWith(['name', ''])]
    #[TestWith(['contactPerson', ''])]
    #[TestWith(['address', ''])]
    #[TestWith(['phone', 'not-a-phone'])]
    #[TestWith(['phone', '1.....2'])]
    #[TestWith(['email', 'not-an-email'])]
    #[TestWith(['website', 'javascript:alert(1)'])]
    #[TestWith(['phone', ['invalid']])]
    #[TestWith(['logoDataUrl', ['invalid']])]
    public function test_invalid_identity_contact_and_address_are_rejected_without_a_write(string $field, mixed $value): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));

        $this->postJson('/api/customers', [...$this->payload(), $field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_customer_creation_editing_and_deletion_are_persisted_and_stale_edits_return_409(): void
    {
        $marketing = User::factory()->create(['role' => 'MARKETING']);
        Sanctum::actingAs($marketing);
        $created = $this->postJson('/api/customers', [...$this->payload(), 'created_by' => 999, 'code' => 'FORGED', 'version' => 99])
            ->assertCreated()->assertJsonPath('data.version', 1)->assertJsonPath('data.serverManaged', true)->json('data');
        $this->assertDatabaseHas('customers', ['id' => $created['id'], 'created_by' => $marketing->id, 'name' => 'Sekolah QA']);
        $this->assertNotSame('FORGED', $created['code']);
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $this->getJson('/api/customers/'.$created['id'])->assertOk()->assertJsonPath('data.orgFullName', 'NAMA RESMI SEKOLAH QA');

        $this->putJson('/api/customers/'.$created['id'], [...$this->payload(), 'phone' => '081111111111', 'version' => 1])
            ->assertOk()->assertJsonPath('data.version', 2)->assertJsonPath('data.phone', '081111111111');
        $this->putJson('/api/customers/'.$created['id'], [...$this->payload(), 'version' => 1])->assertConflict();
        $this->assertDatabaseHas('customers', ['id' => $created['id'], 'phone' => '081111111111', 'version' => 2]);

        $this->deleteJson('/api/customers/'.$created['id'])->assertOk();
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_order_uses_a_server_customer_and_preserves_its_reference_and_historical_snapshot(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $customer = Customer::factory()->create(['name' => 'Customer Asli']);
        Product::create(['sku' => 'CUSTOMER-QA', 'name' => 'Barang QA', 'unit' => 'PCS', 'category' => 'QA', 'stock' => 5]);
        $data = ['version' => 0, 'details' => ['clientId' => $customer->id, 'clientName' => 'Nama palsu', 'projectName' => 'Proyek QA',
            'customerProfile' => ['name' => 'Profil palsu', 'code' => 'FORGED']],
            'items' => [['sku' => 'CUSTOMER-QA', 'name' => 'Barang QA', 'unit' => 'PCS', 'qty' => 1, 'price' => 0]]];

        $this->putJson('/api/orders/ORD-CUSTOMER-QA', $data)->assertOk()
            ->assertJsonPath('data.clientName', 'Customer Asli')->assertJsonPath('data.clientId', $customer->id)
            ->assertJsonPath('data.customerProfile.code', $customer->code)->assertJsonPath('data.grandTotal', 0);
        $this->assertDatabaseHas('sales_orders', ['order_number' => 'ORD-CUSTOMER-QA', 'customer_id' => $customer->id]);
        $this->deleteJson('/api/customers/'.$customer->id)->assertConflict();
        $this->assertModelExists($customer);

        $this->putJson('/api/customers/'.$customer->id, [...$this->payload(), 'name' => 'Nama Baru', 'version' => 1])->assertOk();
        $this->assertSame('Customer Asli', SalesOrder::where('order_number', 'ORD-CUSTOMER-QA')->first()->details['clientName']);
    }

    public function test_a_browser_only_customer_id_cannot_create_a_persisted_order(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));

        $this->putJson('/api/orders/ORD-LOCAL-ID', ['version' => 0, 'details' => ['clientId' => 999, 'clientName' => 'Lokal', 'projectName' => 'QA'],
            'items' => [['sku' => 'QA', 'name' => 'QA', 'unit' => 'PCS', 'qty' => 1, 'price' => 1000]]])
            ->assertUnprocessable()->assertJsonValidationErrors('details.clientId');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_invalid_search_type_returns_422_instead_of_a_server_error(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));

        $this->getJson('/api/customers?search[]=invalid')->assertUnprocessable()->assertJsonValidationErrors('search');
    }

    public function test_search_treats_zero_as_a_real_filter(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        Customer::factory()->create(['code' => 'CS-ALPHA', 'name' => 'Customer Biasa']);
        $matched = Customer::factory()->create(['code' => 'CS-BETA', 'name' => 'Customer 0']);

        $this->getJson('/api/customers?search=0')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matched->id);
    }

    public function test_non_string_order_logo_returns_422_instead_of_a_server_error(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $customer = Customer::factory()->create();

        $this->putJson('/api/orders/ORD-INVALID-LOGO', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'QA', 'customerProfile' => ['logoDataUrl' => ['invalid']]],
            'items' => [['sku' => 'QA', 'name' => 'Barang QA', 'unit' => 'PCS', 'qty' => 1, 'price' => 1000]]])
            ->assertUnprocessable()->assertJsonValidationErrors('details.customerProfile.logoDataUrl');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    #[TestWith(['qty', 0])]
    #[TestWith(['price', -1])]
    public function test_invalid_order_quantity_or_price_is_rejected_before_persistence(string $field, int $value): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $customer = Customer::factory()->create();

        $this->putJson('/api/orders/ORD-INVALID-VALUE', ['version' => 0,
            'details' => ['clientId' => $customer->id, 'clientName' => $customer->name, 'projectName' => 'QA'],
            'items' => [['sku' => 'QA', 'name' => 'Barang QA', 'unit' => 'PCS', 'qty' => 1, 'price' => 1000, $field => $value]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.'.$field);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_legacy_customer_ids_are_not_automatically_attached_to_a_different_server_identity(): void
    {
        $marketing = User::factory()->create(['role' => 'MARKETING']);
        Sanctum::actingAs($marketing);
        $customer = Customer::factory()->create(['name' => 'Nama Lama']);
        SalesOrder::create(['order_number' => 'ORD-LEGACY-CUSTOMER', 'created_by' => $marketing->id, 'details' => ['clientId' => $customer->id, 'clientName' => 'Nama Lama'],
            'items' => [], 'history' => [], 'subtotal' => 0, 'tax' => 0, 'grand_total' => 0]);

        $this->getJson('/api/orders')->assertOk()->assertJsonPath('data.0.clientId', null)->assertJsonPath('data.0.clientName', 'Nama Lama');
        $this->deleteJson('/api/customers/'.$customer->id)->assertConflict();
        $this->assertModelExists($customer);
    }
}
