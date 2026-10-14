<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products_with_pagination(): void
    {
        $response = $this->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'total_stock',
                ],
            ]);
    }

    public function test_can_filter_products_by_search_or_category(): void
    {
        $response = $this->getJson('/api/products?search=Abate');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_get_categories_and_units(): void
    {
        $catRes = $this->getJson('/api/products/categories');
        $catRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        $unitRes = $this->getJson('/api/products/units');
        $unitRes->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_can_create_update_and_delete_product(): void
    {
        $testSku = 'TEST-SKU-'.time();
        $createRes = $this->postJson('/api/products', [
            'sku' => $testSku,
            'name' => 'Barang Uji Coba',
            'category' => 'Uji Coba Kategori',
            'unit' => 'PCS',
            'stock' => 15,
            'sell_price' => 50000,
            'status' => 'Tersedia',
        ]);

        $createRes->assertStatus(201);
        $productId = $createRes->json('data.id');

        $updateRes = $this->putJson("/api/products/{$productId}", [
            'sku' => $testSku,
            'name' => 'Barang Uji Coba Diubah',
            'category' => 'Uji Coba Kategori',
            'unit' => 'PCS',
            'stock' => 20,
            'sell_price' => 55000,
            'status' => 'Tersedia',
        ]);
        $updateRes->assertStatus(200);

        $deleteRes = $this->deleteJson("/api/products/{$productId}");
        $deleteRes->assertStatus(200);
    }

    public function test_can_import_products(): void
    {
        $payload = [
            'duplicate_mode' => 'update',
            'items' => [
                [
                    'sku' => 'IMP-001',
                    'name' => 'Barang Impor 1',
                    'category' => 'Kategori Impor',
                    'unit' => 'KG',
                    'stock' => 5,
                    'sell_price' => 25000,
                    'status' => 'Tersedia',
                ],
            ],
        ];

        $res = $this->postJson('/api/products/import', $payload);
        $res->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data']);

        // Clean up
        Product::where('sku', 'IMP-001')->delete();
    }
}
