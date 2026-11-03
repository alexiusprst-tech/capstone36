<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_discrepancy_reports', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->string('client_name');
            $table->text('reason');
            $table->json('items')->nullable();
            $table->string('reported_by')->nullable();
            $table->string('status')->default('OPEN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_discrepancy_reports');
    }
};
