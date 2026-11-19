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
        Schema::table('warehouse_requests', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('order_items_hash', 64)->nullable();
        });
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('pending_followup')->default(false);
        });
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_transaction_id')->constrained()->restrictOnDelete();
            $table->string('idempotency_key', 80);
            $table->char('payload_hash', 64);
            $table->date('received_date');
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('legacy')->default(false);
            $table->timestamps();
            $table->unique(['purchase_transaction_id', 'idempotency_key'], 'purchase_receipt_retry_unique');
        });
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('line_index');
            $table->string('sku', 50)->nullable();
            $table->string('name');
            $table->string('unit', 50);
            $table->unsignedInteger('accepted_qty');
            $table->unsignedInteger('rejected_qty');
            $table->text('reason')->nullable();
            $table->unique(['purchase_receipt_id', 'line_index'], 'purchase_receipt_line_unique');
        });
        // Mark historical full receipts without adding physical stock a second time.
        DB::table('purchase_transactions')->where('status', 'DITERIMA_GUDANG')->orderBy('id')->chunkById(200, function ($purchases) {
            foreach ($purchases as $purchase) {
                $id = DB::table('purchase_receipts')->insertGetId([
                    'purchase_transaction_id' => $purchase->id, 'idempotency_key' => 'legacy-'.$purchase->id,
                    'payload_hash' => hash('sha256', 'legacy-'.$purchase->id), 'received_date' => substr($purchase->updated_at ?? $purchase->transaction_date, 0, 10),
                    'notes' => 'Penerimaan historis sebelum pencatatan per barang; pelaku tidak tersedia. Stok tidak diubah.',
                    'legacy' => true, 'created_at' => $purchase->updated_at, 'updated_at' => $purchase->updated_at,
                ]);
                foreach (json_decode($purchase->items ?? '[]', true) ?: [] as $index => $item) {
                    DB::table('purchase_receipt_lines')->insert([
                        'purchase_receipt_id' => $id, 'product_id' => DB::table('products')->where('sku', $item['sku'] ?? '')->value('id'),
                        'line_index' => $index, 'sku' => $item['sku'] ?? null, 'name' => $item['barang'] ?? $item['name'] ?? 'Barang historis',
                        'unit' => $item['unit'] ?? 'UNIT', 'accepted_qty' => max(0, (int) ($item['qty'] ?? 0)), 'rejected_qty' => 0,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn('pending_followup');
        });
        Schema::table('warehouse_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_order_id');
            $table->dropConstrainedForeignId('requested_by_id');
            $table->dropColumn('order_items_hash');
        });
    }
};
