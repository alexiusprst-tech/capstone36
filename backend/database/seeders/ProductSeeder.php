<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/products_seed.json');
        if (! File::exists($jsonPath)) {
            $this->command->error("File {$jsonPath} not found.");

            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (! $data) {
            $this->command->error('Failed to parse products_seed.json.');

            return;
        }

        DB::transaction(function () use ($data) {
            // Seed Categories
            foreach ($data['categories'] as $catName) {
                ProductCategory::firstOrCreate(['name' => $catName]);
            }

            // Seed Units
            foreach ($data['units'] as $unitName) {
                ProductUnit::firstOrCreate(['name' => $unitName]);
            }

            // Seed Products in chunks of 500
            $now = now();
            $chunks = array_chunk($data['products'], 500);

            foreach ($chunks as $chunk) {
                $rows = [];
                foreach ($chunk as $p) {
                    $rows[] = [
                        'sku' => $p['sku'],
                        'name' => $p['name'],
                        'category' => $p['category'],
                        'unit' => $p['unit'],
                        'description' => $p['description'],
                        'stock' => $p['stock'],
                        'min_stock' => $p['min_stock'],
                        'last_buy_price' => $p['last_buy_price'],
                        'hpp' => $p['hpp'],
                        'sell_price' => $p['sell_price'],
                        'status' => $p['status'] ?? 'Tersedia',
                        'created_by' => 'SYSTEM',
                        'updated_by' => 'SYSTEM',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                Product::upsert($rows, ['sku'], [
                    'name', 'category', 'unit', 'description', 'stock', 'min_stock',
                    'last_buy_price', 'hpp', 'sell_price', 'status', 'updated_by', 'updated_at',
                ]);
            }
        });

        $this->command->info('Seeded '.count($data['products']).' products successfully.');
    }
}
