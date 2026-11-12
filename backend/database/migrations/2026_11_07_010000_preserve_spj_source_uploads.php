<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spj_documents', function (Blueprint $table) {
            $table->string('source_file_path')->nullable();
        });
        DB::table('spj_documents')->where('file_path', 'like', '%/external/%')
            ->update(['source_file_path' => DB::raw('file_path')]);
    }

    public function down(): void
    {
        Schema::table('spj_documents', fn (Blueprint $table) => $table->dropColumn('source_file_path'));
    }
};
