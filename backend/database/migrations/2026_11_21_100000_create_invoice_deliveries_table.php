<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_number');
            $table->string('recipient');
            $table->char('token_hash', 64)->unique();
            $table->string('status', 16)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['sales_order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_deliveries');
    }
};
