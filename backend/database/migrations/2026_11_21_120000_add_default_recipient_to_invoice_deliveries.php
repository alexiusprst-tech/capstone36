<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_deliveries', function (Blueprint $table) {
            $table->boolean('default_recipient')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_deliveries', function (Blueprint $table) {
            $table->dropColumn('default_recipient');
        });
    }
};
