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
        Schema::create('spj_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique(); // PR00001
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->string('customer_name');
            $table->string('customer_code')->nullable();
            $table->string('marketing_name')->nullable();
            $table->string('company_name')->default('CV ALPHARO ARTHA JAYA');
            $table->string('activity_name')->nullable();
            $table->string('activity_detail')->nullable();
            $table->string('account_code')->nullable();
            $table->string('fiscal_year')->default('2026');
            $table->string('funding_source')->default('BOP');
            $table->string('category')->default('Barang/Jasa');
            $table->decimal('budget_ceiling', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('project_value', 15, 2)->default(0);
            $table->decimal('invoice_value', 15, 2)->default(0);
            $table->decimal('invoice_paid', 15, 2)->default(0);
            $table->string('pic_name')->nullable();
            $table->string('pic_title')->nullable();
            $table->string('pic_nip')->nullable();
            $table->string('city_signature')->default('Bogor');
            $table->string('status')->default('DISETUJUI'); // DRAFT, MENUNGGU_PERSETUJUAN, DISETUJUI, DIPROSES, SELESAI
            $table->json('director_approval')->nullable();
            $table->string('warehouse_status')->default('TERKIRIM');
            $table->json('warehouse_data')->nullable();
            $table->string('finance_status')->default('LUNAS');
            $table->json('finance_data')->nullable();
            $table->json('items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('spj_documents', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->index();
            $table->foreign('project_id')->references('project_id')->on('spj_projects')->cascadeOnDelete();
            $table->unsignedTinyInteger('doc_order'); // 1-15
            $table->string('doc_key')->index(); // cover_project, hps, spph, dll
            $table->string('doc_name');
            $table->boolean('is_external')->default(false);
            $table->string('status')->default('BELUM_DIBUAT'); // BELUM_DIBUAT, DATA_BELUM_LENGKAP, DRAFT, MENUNGGU_PERSETUJUAN, PERLU_REVISI, FINAL, DOKUMEN_EKSTERNAL
            $table->boolean('is_data_complete')->default(true);
            $table->json('missing_requirements')->nullable();
            $table->string('doc_number')->nullable();
            $table->string('doc_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->default('docx');
            $table->unsignedInteger('version')->default(1);
            $table->json('form_data')->nullable(); // Nilai spesifik form input (header, textarea, rincian nego, dll)
            $table->timestamps();

            $table->unique(['project_id', 'doc_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spj_documents');
        Schema::dropIfExists('spj_projects');
    }
};
