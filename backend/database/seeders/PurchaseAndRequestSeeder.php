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
                'address' => 'Gedung APL Tower Lt. 42, Jl. Letjen S. Parman Kav. 28, Tanjung Duren Selatan, Jakarta Barat',
                'contact_person' => 'Hendra Gunawan (Sales Enterprise)',
                'phone' => '021-29345800',
                'email' => 'sales@synnexmetrodata.com',
                'npwp' => '01.345.678.9-031.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '5001239841',
                'bank_account_name' => 'PT Synnex Metrodata Indonesia',
                'is_active' => true,
                'notes' => 'Supplier utama hardware komputer, server, dan laptop bergaransi resmi.',
            ],
            [
                'code' => 'SUP00002',
                'name' => 'CV Sumber Makmur Komputer',
                'address' => 'Jl. Pajajaran No. 45, Baranangsiang, Kota Bogor, Jawa Barat',
                'contact_person' => 'Budi Setiawan',
                'phone' => '0251-8321900',
                'email' => 'marketing@sumbermakmur.co.id',
                'npwp' => '02.456.789.0-042.000',
                'bank_name' => 'Bank Mandiri',
                'bank_account_number' => '1330018892019',
                'bank_account_name' => 'CV Sumber Makmur Komputer',
                'is_active' => true,
                'notes' => 'Vendor peripheral IT, printer, proyektor, dan aksesoris multimedia.',
            ],
            [
                'code' => 'SUP00003',
                'name' => 'PT Indo Komputer Perkasa',
                'address' => 'Komplek Harco Mangga Dua Blok E No. 12, Sawah Besar, Jakarta Pusat',
                'contact_person' => 'Siti Nurhaliza',
                'phone' => '021-62301122',
                'email' => 'info@indokomputer.com',
                'npwp' => '01.889.012.3-018.000',
                'bank_name' => 'BNI',
                'bank_account_number' => '0829103982',
                'bank_account_name' => 'PT Indo Komputer Perkasa',
                'is_active' => true,
                'notes' => 'Distributor jaringan komputer, kabel UTP, switch mikrotik, dan router cisco.',
            ],
            [
                'code' => 'SUP00004',
                'name' => 'CV Mandiri Perkasa Prima',
                'address' => 'Kawasan Industri Sentul, Jl. Babakan Madang No. 8, Bogor, Jawa Barat',
                'contact_person' => 'Ahmad Fauzi',
                'phone' => '0251-8456789',
                'email' => 'order@mandiriperkasa.id',
                'npwp' => '03.112.233.4-055.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '5230918231',
                'bank_account_name' => 'CV Mandiri Perkasa Prima',
                'is_active' => true,
                'notes' => 'Penyedia material konstruksi, baja ringan, semen, dan pipa pvc.',
            ],
            [
                'code' => 'SUP00005',
                'name' => 'PT Cipta Karya Niaga',
                'address' => 'Jl. Raya Daan Mogot Km. 11, Cengkareng, Jakarta Barat',
                'contact_person' => 'Dewi Sartika',
                'phone' => '021-58902233',
                'email' => 'sales@ciptakaryaniaga.com',
                'npwp' => '01.992.384.1-085.000',
                'bank_name' => 'BRI',
                'bank_account_number' => '038901000293301',
                'bank_account_name' => 'PT Cipta Karya Niaga',
                'is_active' => true,
                'notes' => 'Grosir perlengkapan elektrikal kabel NYM, panel MCB, dan lampu LED gedung.',
            ],
            [
                'code' => 'SUP00006',
                'name' => 'PT Global Sarana Solusindo',
                'address' => 'Jl. TB Simatupang No. 18, Cilandak, Jakarta Selatan',
                'contact_person' => 'Rian Hidayat',
                'phone' => '021-75912345',
                'email' => 'procurement@globalsarana.co.id',
                'npwp' => '02.102.938.4-012.000',
                'bank_name' => 'Bank Mandiri',
                'bank_account_number' => '1270098234112',
                'bank_account_name' => 'PT Global Sarana Solusindo',
                'is_active' => true,
                'notes' => 'Penyedia software lisensi, Microsoft Windows, Office Pro, dan Antivirus.',
            ],
            [
                'code' => 'SUP00007',
                'name' => 'CV Graha Pratama Furniture',
                'address' => 'Jl. Dr. Saharjo No. 112, Tebet, Jakarta Selatan',
                'contact_person' => 'Eko Prasetyo',
                'phone' => '021-83709876',
                'email' => 'sales@grahafuniture.com',
                'npwp' => '03.445.667.8-061.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '6820192834',
                'bank_account_name' => 'CV Graha Pratama Furniture',
                'is_active' => true,
                'notes' => 'Meja dan kursi laboratorium komputer sekolah, partisi ruang kantor.',
            ],
            [
                'code' => 'SUP00008',
                'name' => 'PT Mitra Sejahtera Teknik',
                'address' => 'Kawasan Industri Pulogadung Blok III A, Cakung, Jakarta Timur',
                'contact_person' => 'Ir. Bambang Tri',
                'phone' => '021-4601199',
                'email' => 'contact@mitrasejahtera.co.id',
                'npwp' => '01.556.778.9-009.000',
                'bank_name' => 'BSI',
                'bank_account_number' => '7112093847',
                'bank_account_name' => 'PT Mitra Sejahtera Teknik',
                'is_active' => true,
                'notes' => 'Alat mekanikal dan elektrikal heavy-duty, genset, dan stabilizer daya.',
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
