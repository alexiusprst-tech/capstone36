<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Berita Acara Serah Terima (BAST) - Sekolah',
                'code' => 'BAST_SEKOLAH',
                'category' => 'BAST',
                'description' => 'Format resmi serah terima barang/jasa untuk instansi sekolah dan dinas pendidikan.',
                'file_name' => 'BAST-Sekolah-TEMPLATE.docx',
                'file_path' => 'templates/master/BAST-Sekolah-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/BAST-Sekolah-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_surat', 'hari', 'tanggal', 'bulan', 'tahun', 'nama_sekolah', 'alamat_sekolah',
                    'nama_kepala_sekolah', 'nip_kepala_sekolah', 'nama_perusahaan', 'nama_direktur',
                    'nomor_order', 'nilai_kontrak', 'terbilang',
                ],
            ],
            [
                'name' => 'Berita Acara Serah Terima (BAST) - Rekanan Perusahaan',
                'code' => 'BAST_COMPANY',
                'category' => 'BAST',
                'description' => 'Format serah terima pekerjaan/pengadaan material untuk client perusahaan swasta.',
                'file_name' => 'BAST-Company-Alpharo-TEMPLATE.docx',
                'file_path' => 'templates/master/BAST-Company-Alpharo-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/BAST-Company-Alpharo-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_surat', 'tanggal', 'nama_client', 'alamat_client', 'pic_client',
                    'nama_project', 'nomor_order', 'nilai_pekerjaan',
                ],
            ],
            [
                'name' => 'Surat Pesanan / Purchase Order (PO)',
                'code' => 'SURAT_PESANAN',
                'category' => 'PO',
                'description' => 'Surat pesanan resmi pengadaan barang/jasa dari client rekanan.',
                'file_name' => 'Surat-Pesanan-TEMPLATE.docx',
                'file_path' => 'templates/master/Surat-Pesanan-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Surat-Pesanan-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_po', 'tanggal_po', 'nama_klien', 'alamat_klien', 'nomor_order',
                    'daftar_barang', 'total_nominal', 'syarat_pembayaran',
                ],
            ],
            [
                'name' => 'Surat Jalan Pengiriman Barang',
                'code' => 'SURAT_JALAN',
                'category' => 'SURAT_JALAN',
                'description' => 'Dokumen pengantar pengiriman material dari gudang menuju lokasi penerima.',
                'file_name' => 'Surat-Jalan-TEMPLATE.docx',
                'file_path' => 'templates/master/Surat-Jalan-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Surat-Jalan-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_surat_jalan', 'tanggal_kirim', 'nama_penerima', 'alamat_tujuan',
                    'plat_kendaraan', 'nama_supir', 'rincian_barang',
                ],
            ],
            [
                'name' => 'Faktur Tagihan & Pajak',
                'code' => 'FAKTUR',
                'category' => 'FAKTUR',
                'description' => 'Faktur resmi penagihan pembayaran termin/lunas beserta kalkulasi PPN/PPh.',
                'file_name' => 'Faktur-TEMPLATE.docx',
                'file_path' => 'templates/master/Faktur-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Faktur-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_faktur', 'tanggal_faktur', 'jatuh_tempo', 'nama_klien',
                    'npwp_klien', 'subtotal', 'ppn', 'grand_total', 'terbilang',
                ],
            ],
            [
                'name' => 'Surat Perintah Kerja (SPK)',
                'code' => 'SPK',
                'category' => 'SPK',
                'description' => 'Dokumen instruksi dan perjanjian pelaksanaan pekerjaan pengadaan.',
                'file_name' => 'SPK-TEMPLATE.docx',
                'file_path' => 'templates/master/SPK-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/SPK-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_spk', 'tanggal_spk', 'pihak_pertama', 'pihak_kedua',
                    'lingkup_pekerjaan', 'waktu_pelaksanaan', 'nilai_spk',
                ],
            ],
            [
                'name' => 'Surat Penawaran Harga (SPH)',
                'code' => 'PENAWARAN',
                'category' => 'PENAWARAN',
                'description' => 'Surat penawaran harga resmi CV Alpharo Artha Jaya kepada calon client.',
                'file_name' => 'Penawaran-Alpharo-001-AAJ-SPH-X-2026-TEMPLATE.docx',
                'file_path' => 'templates/master/Penawaran-Alpharo-001-AAJ-SPH-X-2026-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Penawaran-Alpharo-001-AAJ-SPH-X-2026-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_sph', 'tanggal_sph', 'tujuan_instansi', 'perihal',
                    'tabel_harga', 'masa_berlaku', 'total_penawaran',
                ],
            ],
            [
                'name' => 'Harga Perkiraan Sendiri (HPS)',
                'code' => 'HPS',
                'category' => 'SPJ',
                'description' => 'Dokumen kalkulasi HPS untuk kelengkapan administrasi SPJ tender sekolah.',
                'file_name' => 'HPS-HPS20261001133304-TEMPLATE.docx',
                'file_path' => 'templates/master/HPS-HPS20261001133304-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/HPS-HPS20261001133304-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_hps', 'tanggal_hps', 'nama_kegiatan', 'satuan_kerja', 'total_hps',
                ],
            ],
            [
                'name' => 'Berita Acara Hasil Pemeriksaan Barang',
                'code' => 'HASIL_PEMERIKSAAN',
                'category' => 'SPJ',
                'description' => 'Berita acara pemeriksaan fisik dan spesifikasi barang di lokasi.',
                'file_name' => 'Hasil-Pemeriksaan-TEMPLATE.docx',
                'file_path' => 'templates/master/Hasil-Pemeriksaan-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Hasil-Pemeriksaan-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_surat', 'tanggal_periksa', 'tim_pemeriksa', 'kondisi_barang', 'catatan_hasil',
                ],
            ],
            [
                'name' => 'Berita Acara Klarifikasi & Negosiasi Harga',
                'code' => 'KLARIFIKASI_NEGO',
                'category' => 'SPJ',
                'description' => 'Dokumen kesepakatan negosiasi harga pengadaan bersama PPK/panitia.',
                'file_name' => 'Klarifikasi-Nego-KN20261001134628-TEMPLATE.docx',
                'file_path' => 'templates/master/Klarifikasi-Nego-KN20261001134628-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Klarifikasi-Nego-KN20261001134628-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_surat', 'tanggal_nego', 'harga_awal', 'harga_kesepakatan', 'hasil_klarifikasi',
                ],
            ],
            [
                'name' => 'Kuitansi Pembayaran Resmi',
                'code' => 'KUITANSI',
                'category' => 'FINANCE',
                'description' => 'Kuitansi tanda terima uang pembayaran resmi berstempel dan bermaterai.',
                'file_name' => 'Kuitansi-TEMPLATE.docx',
                'file_path' => 'templates/master/Kuitansi-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Kuitansi-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_kuitansi', 'terima_dari', 'jumlah_uang', 'untuk_pembayaran', 'terbilang',
                ],
            ],
            [
                'name' => 'Surat Permintaan Penawaran Harga (SPPH)',
                'code' => 'SPPH',
                'category' => 'SPJ',
                'description' => 'Surat permohonan penawaran harga pengadaan dari pihak sekolah/klien.',
                'file_name' => 'SPPH-PR00001-TEMPLATE.docx',
                'file_path' => 'templates/master/SPPH-PR00001-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/SPPH-PR00001-TEMPLATE.docx',
                'type' => 'AUTO_FILL',
                'available_variables' => [
                    'nomor_spph', 'tanggal_spph', 'nama_kegiatan', 'batas_waktu',
                ],
            ],
            [
                'name' => 'Lampiran Dokumentasi & Foto Serah Terima',
                'code' => 'LAMPIRAN_DOKUMENTASI',
                'category' => 'SPJ',
                'description' => 'Format lembar lampiran dokumentasi foto barang dan serah terima fisik.',
                'file_name' => 'Lampiran-Dokumentasi-TEMPLATE.docx',
                'file_path' => 'templates/master/Lampiran-Dokumentasi-TEMPLATE.docx',
                'blank_file_path' => 'templates/blank/Lampiran-Dokumentasi-TEMPLATE.docx',
                'type' => 'BLANK_DOWNLOAD',
                'available_variables' => [
                    'nomor_order', 'nama_project', 'tanggal_kegiatan',
                ],
            ],
        ];

        foreach ($templates as $item) {
            $fullPath = storage_path('app/'.$item['file_path']);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

            DocumentTemplate::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'file_size' => $fileSize,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'is_active' => true,
                ])
            );
        }
    }
}
