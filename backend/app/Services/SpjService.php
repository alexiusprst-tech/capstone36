<?php

namespace App\Services;

use App\Models\SpjDocument;
use App\Models\SpjProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class SpjService
{
    /**
     * Peta file master template untuk 15 jenis dokumen SPJ
     */
    protected array $templateMap = [
        'cover_project' => 'templates/master/SPPH-PR00001-TEMPLATE.docx', // fallback docx base
        'perencanaan' => 'templates/master/HPS-HPS20261001133304-TEMPLATE.docx',
        'hps' => 'templates/master/HPS-HPS20261001133304-TEMPLATE.docx',
        'spph' => 'templates/master/SPPH-PR00001-TEMPLATE.docx',
        'penawaran' => 'templates/master/Penawaran-Alpharo-001-AAJ-SPH-X-2026-TEMPLATE.docx',
        'klarifikasi_nego' => 'templates/master/Klarifikasi-Nego-KN20261001134628-TEMPLATE.docx',
        'permintaan_penawaran' => 'templates/master/Surat-Pesanan-TEMPLATE.docx',
        'spk' => 'templates/master/SPK-TEMPLATE.docx',
        'hasil_pemeriksaan' => 'templates/master/Hasil-Pemeriksaan-TEMPLATE.docx',
        'bast_sekolah' => 'templates/master/BAST-Sekolah-TEMPLATE.docx',
        'bast_company' => 'templates/master/BAST-Company-Alpharo-TEMPLATE.docx',
        'surat_jalan' => 'templates/master/Surat-Jalan-TEMPLATE.docx',
        'faktur' => 'templates/master/Faktur-TEMPLATE.docx',
        'kuitansi' => 'templates/master/Kuitansi-TEMPLATE.docx',
        'bukti_foto' => 'templates/master/Lampiran-Dokumentasi-TEMPLATE.docx',
    ];

    /**
     * Periksa kelengkapan data lintas modul (Marketing, Direktur, Warehouse, Finance)
     * untuk dokumen tertentu pada suatu project.
     *
     * @return array{is_complete: bool, missing: array<string>}
     */
    public function validateDocumentData(SpjProject $project, string $docKey): array
    {
        $missing = [];

        // 1. Validasi umum data Marketing & Proyek
        if (empty($project->project_name)) {
            $missing[] = 'Marketing: Nama project belum diisi';
        }
        if (empty($project->customer_name)) {
            $missing[] = 'Marketing: Customer / Client belum dipilih';
        }
        if (empty($project->items) || count($project->items) === 0) {
            $missing[] = 'Marketing: Rincian barang / jasa transaksi belum diinput';
        }

        // 2. Validasi per jenis dokumen
        switch ($docKey) {
            case 'hps':
                if (empty($project->budget_ceiling) || $project->budget_ceiling <= 0) {
                    $missing[] = 'Marketing/Administrasi: Pagu anggaran HPS belum ditentukan';
                }
                break;

            case 'penawaran':
                if (empty($project->director_approval) || ($project->director_approval['status'] ?? '') !== 'Disetujui') {
                    $missing[] = 'Direktur: Penawaran harga memerlukan telaah/persetujuan Direktur';
                }
                break;

            case 'klarifikasi_nego':
                if (empty($project->items[0]['price_nego'])) {
                    $missing[] = 'Marketing: Harga hasil kesepakatan negosiasi belum dicatat';
                }
                break;

            case 'spk':
                if (empty($project->pic_name)) {
                    $missing[] = 'Administrasi: Nama penanggung jawab / PPK sekolah belum diisi';
                }
                break;

            case 'hasil_pemeriksaan':
                if (($project->warehouse_status ?? '') !== 'TERKIRIM') {
                    $missing[] = 'Warehouse: Barang belum diverifikasi atau diserahkan di lokasi';
                }
                break;

            case 'bast_sekolah':
                if (($project->warehouse_status ?? '') !== 'TERKIRIM') {
                    $missing[] = 'Warehouse: Barang belum tuntas dikirim untuk dibuatkan BAST Sekolah';
                }
                if (empty($project->pic_name)) {
                    $missing[] = 'Administrasi: Nama Kepala Sekolah / Penanggung Jawab wajib diisi';
                }
                break;

            case 'bast_company':
                if (($project->warehouse_status ?? '') !== 'TERKIRIM') {
                    $missing[] = 'Warehouse: Pemenuhan fisik pekerjaan belum selesai';
                }
                break;

            case 'surat_jalan':
                $wh = $project->warehouse_data ?? [];
                if (empty($wh['driver_name']) && empty($wh['vehicle_plate'])) {
                    $missing[] = 'Warehouse: Nama supir atau nomor plat kendaraan pengiriman belum diinput';
                }
                break;

            case 'faktur':
                if (empty($project->project_value) || $project->project_value <= 0) {
                    $missing[] = 'Finance: Nilai tagihan dan kalkulasi pajak belum tersedia';
                }
                break;

            case 'kuitansi':
                if (($project->finance_status ?? '') !== 'LUNAS' && empty($project->invoice_paid)) {
                    $missing[] = 'Finance: Pembayaran belum terkonfirmasi/diterima untuk penerbitan Kuitansi';
                }
                break;

            case 'bukti_foto':
                if (($project->warehouse_status ?? '') !== 'TERKIRIM') {
                    $missing[] = 'Warehouse: Belum ada dokumentasi serah terima barang dari petugas lapangan';
                }
                break;
        }

        return [
            'is_complete' => count($missing) === 0,
            'missing' => $missing,
        ];
    }

    /**
     * Generate dokumen Word (.docx) untuk suatu dokumen SPJ berdasarkan data project.
     */
    public function generateDocument(SpjProject $project, string $docKey, array $formData = []): SpjDocument
    {
        $doc = SpjDocument::where('project_id', $project->project_id)
            ->where('doc_key', $docKey)
            ->firstOrFail();

        // Siapkan folder penyimpanan per project
        $relativeDir = 'generated_docs/spj/'.Str::slug($project->project_id);
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! File::exists($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        // Tentukan template sumber
        $templateRelPath = $this->templateMap[$docKey] ?? 'templates/master/HPS-HPS20261001133304-TEMPLATE.docx';
        $templateAbsPath = storage_path('app/'.$templateRelPath);

        if (! file_exists($templateAbsPath)) {
            // Gunakan salah satu template docx yang ada di templates/master sebagai basis
            $fallback = storage_path('app/templates/master/BAST-Sekolah-TEMPLATE.docx');
            if (file_exists($fallback)) {
                $templateAbsPath = $fallback;
            } else {
                throw new \RuntimeException("Template master tidak ditemukan untuk dokumen {$docKey}");
            }
        }

        $timestamp = date('Ymd_His');
        $fileName = "{$docKey}_{$project->project_id}_{$timestamp}.docx";
        $targetAbsPath = "{$absoluteDir}/{$fileName}";
        $targetRelPath = "{$relativeDir}/{$fileName}";

        File::copy($templateAbsPath, $targetAbsPath);

        // Gabungkan saved doc form_data dengan input formData
        $effectiveFormData = array_merge($doc->form_data ?? [], $formData);

        // Gabungkan data project, data formulir, dan variabel placeholder
        $variables = $this->buildPlaceholders($project, $doc, $effectiveFormData);

        // Render isi dokumen via XML replacement
        $this->renderDocxXml($targetAbsPath, $docKey, $project, $variables, $effectiveFormData);

        // Perbarui record SpjDocument
        $doc->update([
            'status' => 'FINAL',
            'is_data_complete' => true,
            'missing_requirements' => null,
            'doc_number' => $effectiveFormData['doc_number'] ?? $effectiveFormData['nomor'] ?? $doc->doc_number ?: strtoupper("{$docKey}-{$project->project_id}/".date('Y')),
            'doc_date' => $effectiveFormData['doc_date'] ?? $effectiveFormData['tanggal'] ?? $doc->doc_date ?: date('d/m/Y'),
            'file_path' => $targetRelPath,
            'file_name' => $fileName,
            'version' => $doc->version + 1,
            'form_data' => $effectiveFormData,
        ]);

        return $doc->fresh();
    }

    /**
     * Bulk generate semua dokumen terpilih yang memenuhi prasyarat.
     *
     * @param  array<string>  $docKeys
     * @return array{success_count: int, failed_count: int, results: array<mixed>}
     */
    public function bulkGenerate(SpjProject $project, array $docKeys): array
    {
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($docKeys as $docKey) {
            $validation = $this->validateDocumentData($project, $docKey);

            if (! $validation['is_complete']) {
                $failedCount++;
                $results[] = [
                    'doc_key' => $docKey,
                    'status' => 'failed',
                    'reason' => implode(', ', $validation['missing']),
                ];

                continue;
            }

            try {
                $doc = $this->generateDocument($project, $docKey);
                $successCount++;
                $results[] = [
                    'doc_key' => $docKey,
                    'status' => 'success',
                    'doc_name' => $doc->doc_name,
                    'file_name' => $doc->file_name,
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $results[] = [
                    'doc_key' => $docKey,
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Unggah dokumen eksternal (scan basah / PDF dari client).
     */
    public function uploadExternalDocument(
        SpjProject $project,
        string $docKey,
        UploadedFile $file,
        array $metadata = []
    ): SpjDocument {
        $doc = SpjDocument::where('project_id', $project->project_id)
            ->where('doc_key', $docKey)
            ->firstOrFail();

        $relativeDir = 'generated_docs/spj/'.Str::slug($project->project_id).'/external';
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! File::exists($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension();
        $safeName = "{$docKey}_ext_{$project->project_id}_".time().".{$extension}";
        $file->move($absoluteDir, $safeName);

        $doc->update([
            'is_external' => true,
            'status' => 'DOKUMEN_EKSTERNAL',
            'is_data_complete' => true,
            'missing_requirements' => null,
            'doc_number' => $metadata['doc_number'] ?? $doc->doc_number,
            'doc_date' => $metadata['doc_date'] ?? date('d/m/Y'),
            'file_path' => "{$relativeDir}/{$safeName}",
            'file_name' => $safeName,
            'file_type' => $extension,
            'version' => $doc->version + 1,
            'form_data' => array_merge($doc->form_data ?? [], $metadata),
        ]);

        return $doc->fresh();
    }

    /**
     * Konversi nominal angka ke kalimat terbilang bahasa Indonesia.
     */
    public function terbilang(float|int $nilai): string
    {
        $nilai = abs((int) round($nilai));
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($nilai < 12) {
            $hasil = ' '.$huruf[$nilai];
        } elseif ($nilai < 20) {
            $hasil = $this->terbilang($nilai - 10).' Belas';
        } elseif ($nilai < 100) {
            $hasil = $this->terbilang((int) floor($nilai / 10)).' Puluh '.$this->terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            $hasil = ' Seratus '.$this->terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            $hasil = $this->terbilang((int) floor($nilai / 100)).' Ratus '.$this->terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            $hasil = ' Seribu '.$this->terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $hasil = $this->terbilang((int) floor($nilai / 1000)).' Ribu '.$this->terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $hasil = $this->terbilang((int) floor($nilai / 1000000)).' Juta '.$this->terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $hasil = $this->terbilang((int) floor($nilai / 1000000000)).' Miliar '.$this->terbilang($nilai % 1000000000);
        } else {
            $hasil = $this->terbilang((int) floor($nilai / 1000000000000)).' Triliun '.$this->terbilang($nilai % 1000000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $hasil)).' Rupiah';
    }

    /**
     * Rakit placeholder variabel terpadu dari entitas project, dokumen, dan form overrides.
     */
    protected function buildPlaceholders(SpjProject $project, SpjDocument $doc, array $formData): array
    {
        $items = ! empty($formData['items']) ? $formData['items'] : ($project->items ?? []);
        $item = $items[0] ?? [];
        $wh = $project->warehouse_data ?? [];
        $fn = $project->finance_data ?? [];

        $itemName = $formData['komponen'] ?? $item['item_name'] ?? $item['component'] ?? $item['code'] ?? $project->project_name ?? 'Pemeliharaan Komputer Note Book/ Laptop';
        $volume = (int) ($formData['volume'] ?? $item['quantity'] ?? $item['volume'] ?? $item['qty_sent'] ?? 1);
        $unit = $formData['satuan'] ?? $item['unit'] ?? $item['satuan'] ?? 'UNIT';
        $price = (float) ($formData['harga'] ?? $item['price'] ?? $item['price_offer'] ?? ($project->project_value ?: 0));
        $priceNego = (float) ($formData['harga_nego'] ?? $item['price_nego'] ?? $item['nego_price'] ?? ($price * 0.9));

        $calcSubtotalOffer = 0.0;
        $calcSubtotalNego = 0.0;
        foreach ($items as $it) {
            $q = (float) ($it['quantity'] ?? $it['volume'] ?? $it['qty_sent'] ?? 1);
            $p = (float) ($it['price'] ?? $it['price_offer'] ?? $it['original_price'] ?? 0);
            $pN = (float) ($it['price_nego'] ?? $it['nego_price'] ?? ($p * 0.9));
            $calcSubtotalOffer += ($q * $p);
            $calcSubtotalNego += ($q * $pN);
        }
        if ($calcSubtotalOffer <= 0) {
            $calcSubtotalOffer = (float) ($volume * $price);
        }
        if ($calcSubtotalNego <= 0) {
            $calcSubtotalNego = (float) ($volume * $priceNego);
        }

        $totalOffer = (float) ($formData['subtotal'] ?? $item['total_offer'] ?? $calcSubtotalOffer);
        $taxOffer = round($totalOffer * 0.11);
        $grandOffer = (float) ($formData['total_harga'] ?? ($totalOffer + $taxOffer));

        $totalNego = (float) ($item['total_nego'] ?? $calcSubtotalNego);
        $taxNego = round($totalNego * 0.11);
        $grandNego = $totalNego + $taxNego;
        $pphNego = round($totalNego * 0.015);
        $nettoNego = (float) ($formData['nilai_setelah_pph'] ?? ($grandNego - $pphNego));

        return [
            'id_project' => $project->project_id,
            'nama_project' => $formData['project_name'] ?? $project->project_name,
            'customer' => $formData['customer_name'] ?? $project->customer_name,
            'customer_code' => $project->customer_code,
            'marketing' => $project->marketing_name,
            'company' => $project->company_name,
            'nama_kegiatan' => $formData['nama_kegiatan'] ?? $formData['activity_name'] ?? $project->activity_name ?: $project->project_name,
            'rincian_kegiatan' => $formData['rincian_kegiatan'] ?? $project->activity_detail ?: $project->project_name,
            'tahun_anggaran' => $formData['fiscal_year'] ?? $formData['tahun_anggaran'] ?? $project->fiscal_year,
            'sumber_dana' => $formData['funding_source'] ?? $formData['sumber_dana'] ?? $project->funding_source,
            'pagu_anggaran' => number_format((float) ($project->budget_ceiling ?: $grandOffer), 0, ',', '.'),
            'tgl_mulai' => $project->start_date,
            'estimasi_tgl_selesai' => $project->end_date,
            'nilai_project' => number_format((float) ($project->project_value ?: $grandOffer), 0, ',', '.'),
            'nilai_invoice' => number_format((float) ($project->invoice_value ?: $grandNego), 0, ',', '.'),
            'invoice_dibayar' => number_format((float) ($project->invoice_paid ?: $nettoNego), 0, ',', '.'),
            'nama_sekolah' => $formData['customer_name'] ?? $project->customer_name,
            'alamat_sekolah' => $formData['alamat_sekolah'] ?? 'Jl. Pengadilan No. 4, Bogor',
            'nama_kepala_sekolah' => $formData['pic_name'] ?? $project->pic_name ?: 'Endang, M.Pd.',
            'nip_kepala_sekolah' => $formData['pic_nip'] ?? $project->pic_nip ?: '00001',
            'jabatan_penanggung_jawab' => $formData['pic_title'] ?? $project->pic_title ?: 'Kepala Sekolah',
            'kota_tanda_tangan' => $formData['city_signature'] ?? $project->city_signature ?: 'Bogor',
            'nama_direktur' => $project->director_approval['name'] ?? 'Franklin Janwar',
            'jabatan_direktur' => $project->director_approval['title'] ?? 'Direktur',
            'nomor_surat' => $formData['nomor'] ?? $formData['doc_number'] ?? $doc->doc_number ?? '001/AAJ/SPH/X/2026',
            'nomor_hps' => $formData['nomor'] ?? $formData['doc_number'] ?? 'HPS20261001133304',
            'nomor_sph' => $formData['nomor'] ?? $formData['doc_number'] ?? '001/AAJ/SPH/X/2026',
            'nomor_nego' => $formData['nomor'] ?? $formData['doc_number'] ?? 'KN20261001134628',
            'tanggal' => $formData['tanggal'] ?? $formData['doc_date'] ?? date('d/m/Y'),
            'komponen' => $itemName,
            'volume' => $volume,
            'satuan' => $unit,
            'harga' => number_format($price, 0, ',', '.'),
            'harga_nego' => number_format($priceNego, 0, ',', '.'),
            'jumlah_harga' => number_format($totalOffer, 0, ',', '.'),
            'ppn' => number_format($taxOffer, 0, ',', '.'),
            'total' => number_format($grandOffer, 0, ',', '.'),
            'nama_supir' => $formData['nama_supir'] ?? $wh['driver_name'] ?? 'Agus Santoso',
            'plat_kendaraan' => $formData['no_kendaraan'] ?? $formData['plat_kendaraan'] ?? $wh['vehicle_plate'] ?? 'B 9812 AAJ',
            'nomor_faktur' => $formData['nomor'] ?? $formData['doc_number'] ?? $fn['invoice_number'] ?? 'INV-2026-001',
        ];
    }

    /**
     * Buka file docx (zip) dan ganti variabel XML dengan data proyek & formulir secara cerdas.
     * Mengganti tanda titik-titik (dots/ellipsis) template dengan nilai riil dan menghapus sisa dots.
     */
    protected function renderDocxXml(string $docxPath, string $docKey, SpjProject $project, array $variables, array $formData = []): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml !== false) {
            $x = fn ($val): string => htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');

            $customerRaw = $formData['customer_name'] ?? $project->customer_name ?: 'SMA NEGERI 2 BOGOR';
            $customer = $x($customerRaw);
            $customerUpper = $x(strtoupper($customerRaw));
            $alamat = $x($formData['alamat_sekolah'] ?? 'Jl. Pengadilan No. 4, Pabaton, Kecamatan Bogor Tengah, Kota Bogor');
            $telepon = $x('(0251) 8321045');
            $surel = $x('info@sman2bogor.sch.id');
            $laman = $x('sman2bogor.sch.id');
            $kodepos = $x('16121');
            $kota = $x($formData['city_signature'] ?? $project->city_signature ?: 'Bogor');

            $kegiatanRaw = $formData['nama_kegiatan'] ?? $formData['activity_name'] ?? $formData['project_name'] ?? $project->activity_name ?? $project->project_name ?? 'Pemeliharaan Komputer Note Book/ Laptop';
            $kegiatan = $x($kegiatanRaw);

            $items = ! empty($formData['items']) ? $formData['items'] : ($project->items ?? []);
            $item = $items[0] ?? [];

            $komponen = $x($formData['komponen'] ?? $item['item_name'] ?? $item['component'] ?? $item['code'] ?? 'Pemeliharaan Komputer Note Book/ Laptop');
            $volume = (int) ($formData['volume'] ?? $item['quantity'] ?? $item['volume'] ?? $item['qty_sent'] ?? 100);
            $satuan = $x($formData['satuan'] ?? $item['unit'] ?? 'UNIT');
            $hargaPenawaran = (float) ($formData['harga'] ?? $item['price'] ?? $item['price_offer'] ?? $item['original_price'] ?? 100000);
            $hargaNego = (float) ($formData['harga_nego'] ?? $item['price_nego'] ?? $item['nego_price'] ?? 90000);

            // Hitung subtotal akumulatif jika items lebih dari 1
            $calcSubtotalPenawaran = 0.0;
            $calcSubtotalNego = 0.0;
            foreach ($items as $it) {
                $qty = (float) ($it['quantity'] ?? $it['volume'] ?? $it['qty_sent'] ?? 1);
                $pr = (float) ($it['price'] ?? $it['price_offer'] ?? $it['original_price'] ?? 0);
                $prN = (float) ($it['price_nego'] ?? $it['nego_price'] ?? ($pr * 0.9));
                $calcSubtotalPenawaran += ($qty * $pr);
                $calcSubtotalNego += ($qty * $prN);
            }
            if ($calcSubtotalPenawaran <= 0) {
                $calcSubtotalPenawaran = (float) ($volume * $hargaPenawaran);
            }
            if ($calcSubtotalNego <= 0) {
                $calcSubtotalNego = (float) ($volume * $hargaNego);
            }

            $subtotalPenawaran = (float) ($formData['subtotal'] ?? $item['total_offer'] ?? $calcSubtotalPenawaran);
            $ppnPenawaran = round($subtotalPenawaran * 0.11);
            $totalPenawaran = (float) ($formData['total_harga'] ?? ($subtotalPenawaran + $ppnPenawaran));

            $subtotalNego = (float) ($item['total_nego'] ?? $calcSubtotalNego);
            $ppnNego = round($subtotalNego * 0.11);
            $totalNego = $subtotalNego + $ppnNego;
            $pphNego = round($subtotalNego * 0.015);
            $nettoNego = (float) ($formData['nilai_setelah_pph'] ?? ($totalNego - $pphNego));

            if (! empty($formData['jumlah'])) {
                $parsedJml = (float) str_replace(['.', ','], '', (string) $formData['jumlah']);
                if ($parsedJml > 0) {
                    $nettoNego = $parsedJml;
                }
            }

            $terbilangPenawaran = $x(! empty($formData['terbilang']) ? $formData['terbilang'] : ucwords($this->terbilang($totalPenawaran)));
            $terbilangNego = $x(! empty($formData['terbilang']) ? $formData['terbilang'] : ucwords($this->terbilang($nettoNego)));

            $wh = $project->warehouse_data ?? [];
            $fn = $project->finance_data ?? [];
            $supirNama = $formData['nama_supir'] ?? $wh['driver_name'] ?? 'Agus Santoso';
            $platNomor = $formData['no_kendaraan'] ?? $formData['plat_kendaraan'] ?? $wh['vehicle_plate'] ?? 'B 9812 AAJ';
            $supirPlat = $x("{$supirNama} ({$platNomor})");

            $kepalaSekolah = $x($formData['pic_name'] ?? $project->pic_name ?: 'Endang, M.Pd.');
            $nipKepalaSekolah = $x($formData['pic_nip'] ?? $project->pic_nip ?: '196805121994031005');
            $direktur = $x('Franklin Janwar');
            $perusahaan = $x('CV ALPHARO ARTHA JAYA');
            $alamatPerusahaan = $x('Ruko Cibubur Indah Blok B No. 6 Jl. Lapangan Tembak Cibubur - Jakarta Timur');
            $sumberDana = $x($formData['funding_source'] ?? $project->funding_source ?: 'BOP');
            $tahunAnggaran = $x($formData['fiscal_year'] ?? $project->fiscal_year ?: date('Y'));
            $rekening = $x($project->account_code ?: '5.1.02.01.01.0024');

            $noFaktur = $x($formData['doc_number'] ?? $formData['nomor'] ?? $formData['nomor_faktur'] ?? $fn['invoice_number'] ?? 'IV20261006143815');
            $noKuitansi = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'PI20261006143924');
            $noPenawaran = $x($formData['doc_number'] ?? $formData['nomor'] ?? '001/AAJ/SPH/X/2026');
            $noHps = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'HPS20261001133304');
            $noSpph = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'PR00001/SPPH/X/2026');
            $noNego = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'KN20261001134628');
            $noSpk = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'SPK/2026/10/001');
            $noSp = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'SP20261003140101');
            $noPemeriksaan = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'BA-HP/2026/10/001');
            $noBastSekolah = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'BAST-SEK/2026/10/001');
            $noBastCompany = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'BAST-AAJ/2026/10/001');
            $noSuratJalan = $x($formData['doc_number'] ?? $formData['nomor'] ?? 'SJ-20261006001');

            $tglIndonesia = $x($formData['doc_date'] ?? $formData['tanggal'] ?? '06 Oktober 2026');
            $tglAwalIndonesia = $x($formData['doc_date'] ?? $formData['tanggal'] ?? '01 Oktober 2026');
            $tglNegoIndonesia = $x($formData['doc_date'] ?? $formData['tanggal'] ?? '03 Oktober 2026');

            switch ($docKey) {
                case 'faktur':
                    $xml = str_replace('<w:t xml:space="preserve"> .... ................ ....</w:t>', "<w:t xml:space=\"preserve\"> {$tglIndonesia}</w:t>", $xml);
                    $xml = str_replace('<w:t>No. ....................................................</w:t>', "<w:t>No. {$noFaktur}</w:t>", $xml);
                    $xml = preg_replace('/<w:t>\.{40,}<\/w:t>/', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\.{40,}\s+\.{40,}<\/w:t>/', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\.{40,}<\/w:t>/', "<w:t>{$kegiatan}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\.{40,}\s+\.{40,}<\/w:t>/', "<w:t>{$komponen}</w:t>", $xml, 1);
                    $xml = str_replace('<w:t>................</w:t>', "<w:t>{$volume}</w:t>", $xml);
                    $xml = str_replace('<w:t>Rp .................000</w:t>', '<w:t>Rp '.number_format($hargaPenawaran, 0, ',', '.').'</w:t>', $xml);
                    $xml = str_replace('<w:t>Rp ................</w:t>', '<w:t>Rp '.number_format($subtotalPenawaran, 0, ',', '.').'</w:t>', $xml);
                    $xml = str_replace('<w:t>Rp ..................</w:t>', '<w:t>Rp '.number_format($ppnPenawaran, 0, ',', '.').'</w:t>', $xml);
                    $xml = preg_replace('/<w:t xml:space="preserve">\s*\.{30,}<\/w:t>/', "<w:t xml:space=\"preserve\"> {$terbilangNego}</w:t>", $xml);
                    break;

                case 'kuitansi':
                    $xml = str_replace('<w:t>Nomor : ....................................................</w:t>', "<w:t>Nomor : {$noKuitansi}</w:t>", $xml);
                    $xml = preg_replace('/<w:t>\.{40,}<\/w:t>/', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>#\s*\.{40,}\s*#<\/w:t>/', "<w:t># {$terbilangNego} #</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\.{40,}\s+\.{40,}<\/w:t>/', "<w:t>Pembayaran {$kegiatan} ({$customerUpper}) sesuai Faktur No. {$noFaktur}</w:t>", $xml, 1);
                    $xml = str_replace('<w:t>………..</w:t>', '<w:t>'.number_format($nettoNego, 0, ',', '.').',-</w:t>', $xml);
                    $xml = str_replace('<w:t xml:space="preserve"> .... ................ ....</w:t>', "<w:t xml:space=\"preserve\"> {$tglIndonesia}</w:t>", $xml);
                    break;

                case 'bukti_foto':
                    $xml = preg_replace('/<w:t>\.{40,}<\/w:t>/', '<w:t>'.strtoupper($kegiatan)." ({$customerUpper})</w:t>", $xml, 1);
                    break;

                case 'hps':
                case 'perencanaan':
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/[…]{20,}/u', $alamat, $xml, 1);
                    $xml = preg_replace('/Telepon\s+[…\.\s]{3,}/u', "Telepon {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…\.\s]{3,}\s*Surel\s*:\s*[…\.\s]{3,}/u', "Laman : {$laman} Surel : {$surel}", $xml);
                    $xml = preg_replace('/<w:t>\s*[…]{5,}\s*<\/w:t>/u', "<w:t>{$kota}</w:t>", $xml, 1);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/<w:t>\s*[…]{5,}\s*<\/w:t>/u', "<w:t>{$kegiatan}</w:t>", $xml, 1);
                    $xml = preg_replace('/Rp\s+[…\.\s]{3,}/u', 'Rp '.number_format($totalPenawaran, 0, ',', '.'), $xml);
                    $xml = preg_replace('/<w:t>\s*[…\.]{3,}\s*<\/w:t>/u', "<w:t>{$rekening}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*[…\.]{3,}\s*<\/w:t>/u', "<w:t>{$sumberDana} Tahun Anggaran {$tahunAnggaran}</w:t>", $xml, 1);
                    $xml = preg_replace('/Terbilang\s*:\s*[…\.\s]{5,}/u', "Terbilang : {$terbilangPenawaran}", $xml);
                    $xml = preg_replace('/\([…\.\s]+\)/u', "({$kepalaSekolah})", $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP. {$nipKepalaSekolah}", $xml);
                    break;

                case 'spph':
                case 'cover_project':
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/Telepon\s+[…\.\s]{3,}/u', "Telepon {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…\.\s]{3,}\s*Surel\s*:\s*[…\.\s]{3,}/u', "Laman : {$laman} Surel : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/:\s*\.{20,}/u', ": {$noSpph}", $xml);
                    $xml = preg_replace('/[…]{4,},\s*[…]{4,}\s*20[…]{2,}/u', "{$kota}, {$tglAwalIndonesia}", $xml);
                    $xml = preg_replace('/Sehubungan dengan adanya kegiatan\s+[…]{5,}\s+dengan Kode Rekening\s+[…\.]{3,}\s+di dalam dana\s+[…]{5,}\s+Tahun Anggaran\s+[…\.]{3,}\.\s*Bersama ini kami\s+\.{5,}\s+mengajukan/u',
                        "Sehubungan dengan adanya kegiatan {$kegiatan} dengan Kode Rekening {$rekening} di dalam dana {$sumberDana} Tahun Anggaran {$tahunAnggaran}. Bersama ini kami {$customerUpper} mengajukan", $xml);
                    $xml = preg_replace('/\([…\.\s]+\)/u', "({$kepalaSekolah})", $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP : {$nipKepalaSekolah}", $xml);
                    break;

                case 'penawaran':
                    $xml = preg_replace('/<w:t>\s*[…]{10,}\s*<\/w:t>/u', "<w:t>{$noPenawaran}</w:t>", $xml, 1);
                    $xml = preg_replace('/Jakarta,\s*[…]{4,}\s*20[…]{2,}/u', "Jakarta, {$tglAwalIndonesia}", $xml);
                    $xml = preg_replace('/<w:t>\s*[…]{10,}\s*<\/w:t>/u', "<w:t>Kepala {$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*[…]{15,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/Menunjuk Surat Permintaan Penawaran Harga dari\s+[…]{4,},\s*bersama ini kami mengajukan penawaran harga untuk\s+[…]{10,}\./u',
                        "Menunjuk Surat Permintaan Penawaran Harga dari {$customerUpper}, bersama ini kami mengajukan penawaran harga untuk {$kegiatan}.", $xml);
                    $xml = preg_replace('/Rp\.\s*[…]{4,}\s*\([…]{10,}\)/u', 'Rp. '.number_format($totalPenawaran, 0, ',', '.')." ({$terbilangPenawaran})", $xml);
                    break;

                case 'klarifikasi_nego':
                    $hasilNego = $x($formData['nego_result'] ?? 'Sepakat');
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/[…]{20,}/u', $alamat, $xml);
                    $xml = preg_replace('/Telepon\s+[…]{3,}\s+Faksimile\s+[…]{3,}/u', "Telepon {$telepon} Faksimile {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…]{5,}\s*Pos-el\s*:\s*[…]{5,}/u', "Laman : {$laman} Pos-el : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/[…]{4,},\s*[…]{4,}\s*20[…]{2,}/u', "{$kota}, {$tglNegoIndonesia}", $xml);
                    $xml = str_replace('<w:t>................................</w:t>', "<w:t>{$noNego}</w:t>", $xml);
                    $xml = preg_replace('/Berdasarkan surat saudara nomor\s*:\s*[…]{5,}\s*tanggal\s*[…]{4,}\s*tentang Penawaran Harga,\s*dengan ini saya mengundang saudara untuk berpartisipasi dalam proses Pengadaan Barang dan Jasa yang di selenggarakan oleh\s*[…]{5,},\s*pada\s*:/u',
                        "Berdasarkan surat saudara nomor : {$noPenawaran} tanggal {$tglAwalIndonesia} tentang Penawaran Harga, dengan ini saya mengundang saudara untuk berpartisipasi dalam proses Pengadaan Barang dan Jasa yang di selenggarakan oleh {$customerUpper}, pada :", $xml);
                    $xml = preg_replace('/Rp\s+[…\.\s]{3,}/u', 'Rp '.number_format($nettoNego, 0, ',', '.'), $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP: {$nipKepalaSekolah}", $xml);
                    $xml = preg_replace('/\([…\.\s]+\)/u', "({$kepalaSekolah})", $xml);

                    // Halaman 2: Dokumen Hasil Negosiasi
                    $xml = preg_replace('/Nama Satuan Pendidikan\s*:\s*[…\.\s]{4,}/u', "Nama Satuan Pendidikan : {$customerUpper}", $xml);
                    $xml = preg_replace('/Alamat Satuan Pendidikan\s*:\s*[…\.\s]{4,}/u', "Alamat Satuan Pendidikan : {$alamat}", $xml);
                    $xml = preg_replace('/Tanggal Negosiasi\s*:\s*[…\.\s]{4,}/u', "Tanggal Negosiasi : {$tglNegoIndonesia}", $xml);
                    $xml = preg_replace('/Hasil Negosiasi\s*:\s*[…\.\s]{4,}/u', "Hasil Negosiasi : {$hasilNego}", $xml);

                    // Baris Tabel Rincian Negosiasi
                    $negoRowsXml = '';
                    foreach ($items as $idx => $it) {
                        $no = $idx + 1;
                        $itName = $x($it['item_name'] ?? $it['component'] ?? $it['code'] ?? 'Barang');
                        $itQty = $x((string) ($it['quantity'] ?? $it['volume'] ?? 1));
                        $itSat = $x((string) ($it['unit'] ?? 'Unit'));
                        $itHp = 'Rp '.number_format($it['original_price'] ?? $it['price'] ?? $it['price_offer'] ?? 0, 0, ',', '.');
                        $itHn = 'Rp '.number_format($it['price_nego'] ?? $it['nego_price'] ?? $it['price'] ?? 0, 0, ',', '.');
                        $itKet = $x($it['keterangan'] ?? 'Sepakat');

                        $negoRowsXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="500" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>'.$no.'</w:t></w:r></w:p></w:tc>';
                        $negoRowsXml .= '<w:tc><w:tcPr><w:tcW w:w="3440" w:type="dxa"/><w:gridSpan w:val="3"/></w:tcPr><w:p><w:r><w:t>'.$itName.'</w:t></w:r></w:p></w:tc>';
                        $negoRowsXml .= '<w:tc><w:tcPr><w:tcW w:w="1000" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>'.$itQty.' '.$itSat.'</w:t></w:r></w:p></w:tc>';
                        $negoRowsXml .= '<w:tc><w:tcPr><w:tcW w:w="1600" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>'.$itHp.'</w:t></w:r></w:p></w:tc>';
                        $negoRowsXml .= '<w:tc><w:tcPr><w:tcW w:w="1700" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>'.$itHn.'</w:t></w:r></w:p></w:tc>';
                        $negoRowsXml .= '<w:tc><w:tcPr><w:tcW w:w="1400" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>'.$itKet.'</w:t></w:r></w:p></w:tc></w:tr>';
                    }

                    if (! empty($negoRowsXml)) {
                        $xml = preg_replace('/<w:tr[^>]*>.*?<w:t>……………………<\/w:t>.*?<\/w:tr>/s', $negoRowsXml, $xml, 1);
                    }
                    $xml = str_replace('<w:t>Rp ………………</w:t>', '<w:t>Rp '.number_format($subtotalNego, 0, ',', '.').'</w:t>', $xml);
                    break;

                case 'permintaan_penawaran': // Surat Pesanan
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/[…]{20,}/u', $alamat, $xml);
                    $xml = preg_replace('/Telepon\s+[…]{3,}\s+Faksimile\s+[…]{3,}/u', "Telepon {$telepon} Faksimile {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…]{3,}\s*[\.]*\s*Pos-el\s*:\s*[…]{3,}/u', "Laman : {$laman} Pos-el : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/Nomor\s*:\s*\.{20,}/u', "Nomor : {$noSp}", $xml);
                    $xml = preg_replace('/Sehubungan dengan kegiatan pengadaan\s+\.{20,},\s*dengan ini kami mengajukan permintaan/u',
                        "Sehubungan dengan kegiatan pengadaan {$kegiatan}, dengan ini kami mengajukan permintaan", $xml);
                    $xml = preg_replace('/Jakarta,\s*[\.…\s]{5,}/u', "{$kota}, {$tglNegoIndonesia}", $xml);
                    $xml = preg_replace('/Terbilang\s*:\s*\.{20,}/u', "Terbilang : {$terbilangNego}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{30,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah}</w:t>", $xml);
                    break;

                case 'spk':
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/[…]{20,}/u', $alamat, $xml);
                    $xml = preg_replace('/Telepon\s+[…]{3,}\s+Faksimile\s+[…]{3,}/u', "Telepon {$telepon} Faksimile {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…]{3,}\s*Pos-el\s*:\s*[…\.]{3,}/u', "Laman : {$laman} Pos-el : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/Nama Satuan Pendidikan\s*:\s*\.{20,}/u', "Nama Satuan Pendidikan : {$customerUpper}", $xml);
                    $xml = preg_replace('/Nomor SPK\s*:\s*\.{15,}/u', "Nomor SPK : {$noSpk}", $xml);
                    $xml = preg_replace('/Tanggal SPK\s*:\s*[\.…\s]{5,}/u', "Tanggal SPK : {$tglIndonesia}", $xml);
                    $xml = preg_replace('/Tanggal Negosiasi\s*:\s*[\.…\s]{5,}/u', "Tanggal Negosiasi : {$tglNegoIndonesia}", $xml);
                    $xml = preg_replace('/Paket Pekerjaan\s*:\s*\.{20,}/u', "Paket Pekerjaan : {$kegiatan}", $xml);
                    $xml = preg_replace('/mulai tanggal\s+[\.…\s]{5,}\s+sampai dengan\s+[\.…\s]{5,},/u', "mulai tanggal {$tglIndonesia} sampai dengan 18 Oktober 2026,", $xml);
                    $xml = preg_replace('/Jumlah Total\s*:\s*Rp\s+\.{10,}/u', 'Jumlah Total : Rp '.number_format($subtotalPenawaran, 0, ',', '.'), $xml);
                    $xml = preg_replace('/PPN\s*:\s*Rp\s+\.{10,}/u', 'PPN : Rp '.number_format($ppnPenawaran, 0, ',', '.'), $xml);
                    $xml = preg_replace('/PPH\s*:\s*Rp\s+\.{10,}/u', 'PPH : Rp '.number_format($pphNego, 0, ',', '.'), $xml);
                    $xml = preg_replace('/Total Pembayaran\s*:\s*Rp\s+\.{10,}/u', 'Total Pembayaran : Rp '.number_format($nettoNego, 0, ',', '.'), $xml);
                    $xml = preg_replace('/Terbilang\s*:\s*\.{20,}/u', "Terbilang : {$terbilangNego}", $xml);
                    $xml = preg_replace('/Jakarta,\s*[\.…\s]{5,}/u', "{$kota}, {$tglIndonesia}", $xml);
                    $xml = preg_replace('/\([…\.\s]+\)/u', "({$kepalaSekolah})", $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP. {$nipKepalaSekolah}", $xml);
                    break;

                case 'hasil_pemeriksaan':
                    $xml = preg_replace('/SEKOLAH MENENGAH\s+[…\.\s]{3,}/u', "SEKOLAH MENENGAH ATAS {$customerUpper}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/Telepon\s+[…]{3,}\s+Faksimile\s+[…]{3,}/u', "Telepon {$telepon} Faksimile {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…]{3,}\s*Pos-el\s*:\s*[…]{3,}/u', "Laman : {$laman} Pos-el : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/No\.\s*\.{15,}/u', "No. {$noPemeriksaan}", $xml);
                    $xml = preg_replace('/Pada hari ini,\s*[\.…\s]{4,}\s*tanggal\s*[\.…\s]{2,}\s*bulan\s*[\.…\s]{4,}\s*tahun\s*[\.…\s]{4,}\s*Sesuai dengan Nomor Surat Pemesanan\s*:\s*\.{20,}/u',
                        "Pada hari ini, Selasa tanggal 06 bulan Oktober tahun 2026 Sesuai dengan Nomor Surat Pemesanan : {$noSp}", $xml);
                    $xml = str_replace('<w:t>.... ................ ....</w:t>', "<w:t>{$tglNegoIndonesia}</w:t>", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$perusahaan}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{4,}\s*<\/w:t>/u', "<w:t>{$kegiatan}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', '<w:t>Drs. H. Mulyadi, M.Pd.</w:t>', $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{10,}\s*<\/w:t>/u', '<w:t>197203151998021002</w:t>', $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', '<w:t>Siti Rahmah, S.Kom.</w:t>', $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', '<w:t>Guru TIK / Staf Sarpras</w:t>', $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$komponen} (Kondisi Baru & Berfungsi Normal)</w:t>", $xml, 1);
                    $xml = str_replace('<w:t>................ UNIT</w:t>', "<w:t>{$volume} {$satuan}</w:t>", $xml);
                    $xml = preg_replace('/Jakarta\s*,\s*[\.…\s]{5,}/u', "{$kota}, {$tglIndonesia}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah}</w:t>", $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP. {$nipKepalaSekolah}", $xml);
                    break;

                case 'bast_sekolah':
                    $xml = preg_replace('/<w:t>\s*[…]{20,}\s*<\/w:t>/u', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/Telepon\s+[…]{3,}\s+Faksimile\s+[…]{3,}/u', "Telepon {$telepon} Faksimile {$telepon}", $xml);
                    $xml = preg_replace('/Laman\s*:\s*[…]{3,}\s*\.{3,}\s*Pos-el\s*:\s*[…]{3,}/u', "Laman : {$laman} Pos-el : {$surel}", $xml);
                    $xml = preg_replace('/Kode\s*Pos\s*:\s*[…\.\s]{2,}/u', "Kode Pos : {$kodepos}", $xml);
                    $xml = preg_replace('/Nomor\s*:\s*\.{20,}/u', "Nomor : {$noBastSekolah}", $xml);
                    $xml = preg_replace('/Pada hari ini,\s*[\.…\s]{4,}\s*tanggal\s*[\.…\s]{2,}\s*bulan\s*[\.…\s]{4,}\s*tahun\s*[\.…\s]{4,}\s*Sesuai dengan rincian berikut:/u',
                        'Pada hari ini, Selasa tanggal 06 bulan Oktober tahun 2026 Sesuai dengan rincian berikut:', $xml);
                    $xml = str_replace('<w:t>.... ................ ....</w:t>', "<w:t>{$tglNegoIndonesia}</w:t>", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$noSp}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{4,}\s*<\/w:t>/u', "<w:t>{$tahunAnggaran}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$direktur} (Direktur {$perusahaan})</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah} (Kepala {$customerUpper})</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*[…]{5,}\s*<\/w:t>/u', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/PIHAK PERTAMA menyerahkan pekerjaan\s+\.{20,}\s+kepada PIHAK KEDUA/u',
                        "PIHAK PERTAMA menyerahkan pekerjaan {$kegiatan} kepada PIHAK KEDUA", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$komponen}</w:t>", $xml, 1);
                    $xml = preg_replace('/Jakarta\s*,\s*[\.…\s]{5,}/u', "{$kota}, {$tglIndonesia}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$direktur}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah}</w:t>", $xml);
                    $xml = preg_replace('/NIP[\.:]\s*[…\.\s]+/u', "NIP. {$nipKepalaSekolah}", $xml);
                    break;

                case 'bast_company':
                    $xml = preg_replace('/Nomor\s*:\s*\.{20,}/u', "Nomor : {$noBastCompany}", $xml);
                    $xml = preg_replace('/Pada hari ini,\s*[\.…\s]{4,}\s*tanggal\s*[\.…\s]{2,}\s*bulan\s*[\.…\s]{4,}\s*tahun\s*[\.…\s]{4,},\s*bertempat di\s*\.{20,},/u',
                        "Pada hari ini, Selasa tanggal 06 bulan Oktober tahun 2026, bertempat di {$customerUpper},", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$direktur}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$alamatPerusahaan}</w:t>", $xml, 1);
                    $xml = preg_replace('/Dalam hal ini bertindak untuk dan atas nama\s+\.{20,}\s+sebagai PIHAK PERTAMA/u',
                        "Dalam hal ini bertindak untuk dan atas nama {$perusahaan} sebagai PIHAK PERTAMA", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{4,}\s*<\/w:t>/u', '<w:t>Kepala Sekolah</w:t>', $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{20,}\s*<\/w:t>/u', "<w:t>{$nipKepalaSekolah}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/yang diserahkan oleh CV ALPHARO ARTHA JAYA kepada\s+\.{20,},/u',
                        "yang diserahkan oleh CV ALPHARO ARTHA JAYA kepada {$customerUpper},", $xml);
                    $xml = preg_replace('/1\.\s*\.{20,}\s*:\s*\.{10,}/u', "1. {$komponen} : {$volume} {$satuan}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$kepalaSekolah}</w:t>", $xml);
                    break;

                case 'surat_jalan':
                    $xml = preg_replace('/Nomor\s*:\s*\.{20,}/u', "Nomor : {$noSuratJalan}", $xml);
                    $xml = preg_replace('/Jakarta,\s*[\.…\s]{5,}/u', "Jakarta, {$tglIndonesia}", $xml);
                    $xml = preg_replace('/Kepada\s+\.{20,}\s+\.{20,}/u', "Kepada {$customerUpper}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{30,}\s+\.{30,}\s*<\/w:t>/u', "<w:t>{$alamat}</w:t>", $xml, 1);
                    $xml = preg_replace('/Surat Pesanan dari\s+\.{20,}\s+dalam rangka/u', "Surat Pesanan dari {$customerUpper} dalam rangka", $xml);
                    $xml = preg_replace('/\.{20,}\s+Tahun Anggaran\s+\.{10,}/u', "{$kegiatan} Tahun Anggaran {$tahunAnggaran}", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$komponen}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$volume}</w:t>", $xml, 1);
                    $xml = str_replace('<w:t>................</w:t>', "<w:t>{$satuan}</w:t>", $xml);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$customerUpper}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{40,}\s*<\/w:t>/u', "<w:t>{$supirPlat}</w:t>", $xml, 1);
                    $xml = preg_replace('/<w:t>\s*\.{20,}\s*<\/w:t>/u', "<w:t>{$perusahaan}</w:t>", $xml, 1);
                    break;
            }

            // Ganti placeholder generic standard ${key} / {{key}} bila ada
            foreach ($variables as $key => $val) {
                if (is_array($val) || is_object($val)) {
                    continue;
                }
                $escaped = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');
                $patterns = [
                    '${'.$key.'}',
                    '{{'.$key.'}}',
                    '${'.strtoupper($key).'}',
                    '{{'.strtoupper($key).'}}',
                ];
                foreach ($patterns as $p) {
                    $xml = str_replace($p, $escaped, $xml);
                }
            }

            // Bersihkan semua dot sequences (>= 2 dots/ellipsis) yang tersisa
            $xml = preg_replace('/<w:t>[\s\.\…]{2,}<\/w:t>/u', '<w:t></w:t>', $xml);
            $xml = preg_replace('/[\.…]{3,}/u', '', $xml);

            // Garansi validitas XML: escape setiap ampersand bebas menjadi &amp;
            $xml = preg_replace('/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[a-fA-F0-9]+);)/', '&amp;', $xml);

            $zip->addFromString('word/document.xml', $xml);
        }

        $zip->close();
    }
}
