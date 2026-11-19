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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('contact_person');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city')->nullable();
            $table->json('profile');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        // Browser IDs in legacy JSON are preserved without guessing a server identity.
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::dropIfExists('customers');
    }
};
