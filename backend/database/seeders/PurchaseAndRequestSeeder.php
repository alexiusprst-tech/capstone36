<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\WarehouseRequest;
use Illuminate\Database\Seeder;

class PurchaseAndRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP00001',
                'name' => 'PT Synnex Metrodata Indonesia',
                'phone' => '021-29345800',
                'email' => 'sales@synnexmetrodata.com',
                'address' => 'Gedung APL Tower Lt. 42, Jl. Letjen S. Parman Kav. 28, Jakarta Barat',
            ],
            [
                'code' => 'SUP00002',
                'name' => 'CV Sumber Makmur Komputer',
                'phone' => '0251-8321900',
                'email' => 'marketing@sumbermakmur.co.id',
                'address' => 'Jl. Pajajaran No. 45, Baranangsiang, Kota Bogor',
            ],
            [
                'code' => 'SUP00003',
                'name' => 'PT Indo Komputer Perkasa',
                'phone' => '021-62301122',
                'email' => 'info@indokomputer.com',
                'address' => 'Komplek Harco Mangga Dua Blok E No. 12, Jakarta Pusat',
            ],
            [
                'code' => 'SUP00004',
                'name' => 'CV Mandiri Perkasa Prima',
                'phone' => '0251-8456789',
                'email' => 'order@mandiriperkasa.id',
                'address' => 'Kawasan Industri Sentul, Jl. Babakan Madang, Bogor',
            ],
            [
                'code' => 'SUP00005',
                'name' => 'PT Cipta Karya Niaga',
                'phone' => '021-58902233',
                'email' => 'sales@ciptakaryaniaga.com',
                'address' => 'Jl. Raya Daan Mogot Km. 11, Cengkareng, Jakarta Barat',
            ],
        ];

        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['code' => $s['code']], $s);
        }

        // Default warehouse request matching user workflow
        WarehouseRequest::updateOrCreate(
            ['request_code' => 'REQ-2026-001'],
            [
                'request_date' => '2026-10-14',
                'project_id' => 'PR00001',
                'project_name' => 'pengadaan komputer sekolah (SMA NEGRI 2 BOGOR)',
                'purpose' => 'Kebutuhan pengadaan komputer & notebook lab TIK proyek SMA Negeri 2 Bogor',
                'requested_by' => 'Agus Santoso (Staff Gudang)',
                'status' => 'MENUNGGU_FINANCE',
                'total_est_cost' => 10000000,
                'items' => [
                    [
                        'sku' => 'A00001770',
                        'name' => 'A00001770 - Pemeliharaan Komputer Note Book/ Laptop',
                        'qty' => 100,
                        'unit' => 'UNIT',
                        'est_price' => 100000,
                        'subtotal' => 10000000,
                    ],
                ],
                'notes' => 'Telah diverifikasi sesuai spesifikasi berkas SPJ proyek SMA Negeri 2 Bogor.',
            ]
        );
    }
}
