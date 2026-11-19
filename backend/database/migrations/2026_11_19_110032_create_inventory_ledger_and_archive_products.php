<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('inventory_locks', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
        });
        DB::table('inventory_locks')->insert(['id' => 1]);
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->bigInteger('delta');
            $table->unsignedBigInteger('before_stock');
            $table->unsignedBigInteger('after_stock');
            $table->string('sku_snapshot', 50);
            $table->string('name_snapshot');
            $table->text('reason');
            $table->timestamps();
            $table->index(['product_id', 'id']);
            $table->index(['type', 'id']);
        });
        DB::table('products')->orderBy('id')->chunkById(400, function ($products) {
            $now = now();
            DB::table('inventory_movements')->insert($products->map(fn ($product) => [
                'product_id' => $product->id, 'actor_id' => null, 'type' => 'OPENING',
                'reference_type' => 'migration', 'reference_id' => null,
                'delta' => (int) $product->stock, 'before_stock' => 0, 'after_stock' => (int) $product->stock,
                'sku_snapshot' => $product->sku, 'name_snapshot' => $product->name,
                'reason' => 'Saldo awal saat migrasi ledger; stok fisik tidak diubah.',
                'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_locks');
        Schema::table('products', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
