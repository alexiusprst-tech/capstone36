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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category')->index(); // BAST, PO, SPK, FAKTUR, PENAWARAN, SPJ, DLL
            $table->text('description')->nullable();
            $table->string('file_path'); // Path master template di storage
            $table->string('blank_file_path')->nullable(); // Path blank template di storage
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->default('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $table->string('type')->default('AUTO_FILL'); // AUTO_FILL | BLANK_DOWNLOAD
            $table->json('available_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->index();
            $table->string('order_number')->index();
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->string('doc_type')->index(); // BAST, PO, SURAT_JALAN, FAKTUR, SPK, PENAWARAN, DLL
            $table->string('doc_number')->nullable();
            $table->string('title');
            $table->string('file_path'); // Path dokumen hasil di storage
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->default('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $table->string('status')->default('DRAFT'); // DRAFT, FINAL, SIGNED, VERIFIED
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_documents');
        Schema::dropIfExists('document_templates');
    }
};
