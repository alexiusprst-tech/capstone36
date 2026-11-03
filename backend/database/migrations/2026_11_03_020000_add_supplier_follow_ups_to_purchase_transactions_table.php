<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_transactions', 'supplier_status')) {
                $table->string('supplier_status')->nullable();
            }
            if (! Schema::hasColumn('purchase_transactions', 'discrepancy_reason')) {
                $table->text('discrepancy_reason')->nullable();
            }
            if (! Schema::hasColumn('purchase_transactions', 'discrepancy_reported_at')) {
                $table->date('discrepancy_reported_at')->nullable();
            }
            if (! Schema::hasColumn('purchase_transactions', 'supplier_follow_ups')) {
                $table->json('supplier_follow_ups')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_status',
                'discrepancy_reason',
                'discrepancy_reported_at',
                'supplier_follow_ups',
            ]);
        });
    }
};
