<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'marketing@alpharo.co.id' => 'MARKETING',
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
    }
};
