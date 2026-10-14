<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->date('request_date');
            $table->string('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->text('purpose')->nullable();
            $table->string('requested_by');
            $table->string('status')->default('MENUNGGU_FINANCE'); // MENUNGGU_FINANCE, DIPROSES_PEMBELIAN, SELESAI, DITOLAK
            $table->decimal('total_est_cost', 15, 2)->default(0);
            $table->json('items')->nullable();
            $table->string('purchase_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code')->unique(); // PB20261014114148
            $table->date('transaction_date');
            $table->string('invoice_number')->nullable(); // No Faktur
            $table->string('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->string('default_supplier')->nullable();
            $table->unsignedBigInteger('warehouse_request_id')->nullable();
            $table->string('warehouse_request_code')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0); // Jumlah Harga
            $table->decimal('discount', 15, 2)->default(0); // Discount faktur
            $table->decimal('total_price', 15, 2)->default(0); // Total Harga
            $table->string('payment_method')->default('Tunai'); // Tunai, Transfer Bank, Tempo 30 Hari, Tempo 60 Hari
            $table->string('status')->default('LUNAS'); // LUNAS, MENUNGGU_PEMBAYARAN, DIBATALKAN
            $table->json('items')->nullable();
            $table->string('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_transactions');
        Schema::dropIfExists('warehouse_requests');
        Schema::dropIfExists('suppliers');
    }
};
