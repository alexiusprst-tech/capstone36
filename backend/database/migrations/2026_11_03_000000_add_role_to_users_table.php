<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('MARKETING')->index();
        });

        foreach ([
            'direktur@alpharo.co.id' => 'DIREKTUR',
            'admin@alpharo.co.id' => 'ADMINISTRASI',
            'warehouse@alpharo.co.id' => 'WAREHOUSE',
            'finance@alpharo.co.id' => 'FINANCE',
            'client@alpharo.co.id' => 'CLIENT',
        ] as $email => $role) {
            DB::table('users')->where('email', $email)->update(['role' => $role]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
