<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupplierUserSeeder extends Seeder
{
    /**
     * Seed user accounts for all registered suppliers with password "password".
     */
    public function run(): void
    {
        $supplierAccounts = [
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

        foreach ($supplierAccounts as $acc) {
            User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => $acc['password'],
                ]
            );
        }

        $this->command->info('Supplier user accounts successfully seeded with password: password');
    }
}

