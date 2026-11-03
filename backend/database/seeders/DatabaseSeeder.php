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
                'email' => 'client@alpharo.co.id',
                'name' => 'Kepala SMA Negeri 9 Jakarta (Client)',
                'password' => Hash::make('password123'),
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
            // AKUN SUPPLIER RESMI DENGAN PASSWORD "password"
            [
                'email' => 'supplier@alpharo.co.id',
                'name' => 'PT Synnex Metrodata Indonesia (Akun Demo Supplier)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'sales@synnexmetrodata.com',
                'name' => 'Hendra Gunawan (PT Synnex Metrodata Indonesia)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'marketing@sumbermakmur.co.id',
                'name' => 'Budi Setiawan (CV Sumber Makmur Komputer)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'info@indokomputer.com',
                'name' => 'Siti Nurhaliza (PT Indo Komputer Perkasa)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'order@mandiriperkasa.id',
                'name' => 'Ahmad Fauzi (CV Mandiri Perkasa Prima)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'sales@ciptakaryaniaga.com',
                'name' => 'Dewi Sartika (PT Cipta Karya Niaga)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'procurement@globalsarana.co.id',
                'name' => 'Rian Hidayat (PT Global Sarana Solusindo)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'sales@grahafuniture.com',
                'name' => 'Eko Prasetyo (CV Graha Pratama Furniture)',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'contact@mitrasejahtera.co.id',
                'name' => 'Ir. Bambang Tri (PT Mitra Sejahtera Teknik)',
                'password' => Hash::make('password'),
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
            PurchaseAndRequestSeeder::class,
        ]);
    }
}
