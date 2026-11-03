<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo accounts for CV Alpharo Artha Jaya.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'marketing@alpharo.co.id',
                'name' => 'Ahmad Fauzi (Marketing)',
                'password' => Hash::make('password123'),
                'role' => 'MARKETING',
            ],
            [
                'email' => 'direktur@alpharo.co.id',
                'name' => 'Bambang Sutedjo (Direktur)',
                'password' => Hash::make('password123'),
                'role' => 'DIREKTUR',
            ],
            [
                'email' => 'admin@alpharo.co.id',
                'name' => 'Siti Aminah (Administrasi)',
                'password' => Hash::make('password123'),
                'role' => 'ADMINISTRASI',
            ],
            [
                'email' => 'warehouse@alpharo.co.id',
                'name' => 'Joko Susilo (Warehouse)',
                'password' => Hash::make('password123'),
                'role' => 'WAREHOUSE',
            ],
            [
                'email' => 'finance@alpharo.co.id',
                'name' => 'Dewi Lestari (Finance)',
                'password' => Hash::make('password123'),
                'role' => 'FINANCE',
            ],
            [
                'email' => 'client@alpharo.co.id',
                'name' => 'Kepala SMA Negeri 9 Jakarta',
                'password' => Hash::make('password123'),
                'role' => 'CLIENT',
            ],
            [
                'email' => 'user1@example.com',
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'MARKETING',
            ],
            [
                'email' => 'user2@example.com',
                'name' => 'Siti Rahma',
                'password' => Hash::make('password123'),
                'role' => 'MARKETING',
            ],
        ];

        foreach ($accounts as $acc) {
            User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => $acc['password'],
                    'role' => $acc['role'],
                ]
            );
        }

        $this->call([
            RegionSeeder::class,
        ]);
    }
}
