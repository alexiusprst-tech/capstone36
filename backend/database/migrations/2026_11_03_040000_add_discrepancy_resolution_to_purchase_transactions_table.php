<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_transactions', 'discrepancy_resolution')) {
            Schema::table('purchase_transactions', function (Blueprint $table) {
                $table->json('discrepancy_resolution')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_transactions', 'discrepancy_resolution')) {
            Schema::table('purchase_transactions', function (Blueprint $table) {
                $table->dropColumn('discrepancy_resolution');
            });
        }
    }
};
