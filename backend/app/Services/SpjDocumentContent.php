<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SpjDocument;
use App\Models\SpjProject;
use Illuminate\Validation\ValidationException;

/** One document model feeds both the Word template and the PDF view. */
class SpjDocumentContent
{
    public const TITLES = [
        'cover_project' => 'LAPORAN SURAT PERTANGGUNGJAWABAN PENGADAAN',
        'perencanaan' => 'DOKUMEN PERENCANAAN PENGADAAN',
        'hps' => 'HARGA PERKIRAAN SENDIRI (HPS)',
        'spph' => 'SURAT PERMINTAAN PENAWARAN HARGA',
        'penawaran' => 'SURAT PENAWARAN HARGA',
        'klarifikasi_nego' => 'BERITA ACARA KLARIFIKASI DAN NEGOSIASI',
        'permintaan_penawaran' => 'SURAT PESANAN',
        'spk' => 'SURAT PERINTAH KERJA (SPK)',
        'hasil_pemeriksaan' => 'BERITA ACARA HASIL PEMERIKSAAN',
        'bast_sekolah' => 'BERITA ACARA SERAH TERIMA SEKOLAH',
        'bast_company' => 'BERITA ACARA SERAH TERIMA PERUSAHAAN',
        'surat_jalan' => 'SURAT JALAN',
        'faktur' => 'FAKTUR',
        'kuitansi' => 'KUITANSI PEMBAYARAN',
        'bukti_foto' => 'LAMPIRAN DOKUMENTASI PEKERJAAN',
    ];

    public function build(SpjProject $project, SpjDocument $doc, array $form): array
    {
        $key = $doc->doc_key;
        abort_unless(isset(self::TITLES[$key]), 404);
        $wh = $project->warehouse_data ?? [];
        $finance = $project->finance_data ?? [];
        $meta = $project->metadata ?? [];
        $customer = $form['customer_name'] ?? $project->customer_name;
        $company = $project->company_name ?: 'CV ALPHARO ARTHA JAYA';
        $activity = $form['activity_name'] ?? $form['nama_kegiatan'] ?? $form['pekerjaan'] ?? $form['paket_pekerjaan'] ?? $project->activity_name ?: $project->project_name;
        $number = $form['doc_number'] ?? $form['nomor'] ?? $doc->doc_number ?: strtoupper($key.'-'.$project->project_id.'/'.date('Y'));
        $date = $this->date($form['doc_date'] ?? $form['tanggal'] ?? $doc->doc_date ?: date('d/m/Y'));
        $sourceItems = array_key_exists('items', $form) ? $form['items'] : ($project->items ?? []);
        if (! is_array($sourceItems) || collect($sourceItems)->contains(fn ($line) => ! is_array($line))) {
            throw ValidationException::withMessages(['form_data.items' => 'Rincian barang tidak valid.']);
        }
        $items = [];
        $projectItems = collect($project->items ?? []);
        $catalog = Product::whereIn('sku', array_filter(array_column($sourceItems, 'code')))->get()->keyBy('sku');
        $useNegotiated = in_array($key, ['klarifikasi_nego', 'permintaan_penawaran', 'spk', 'faktur', 'kuitansi'], true);
        foreach ($sourceItems as $index => $line) {
            $reference = $projectItems->first(fn ($item) => ! empty($line['code']) && in_array($line['code'], [$item['code'] ?? null, $item['item_name'] ?? null, $item['component'] ?? null, $item['name'] ?? null], true)) ?? [];
            $product = $catalog->get($line['code'] ?? '');
            $line = array_replace(['item_name' => $reference['item_name'] ?? $reference['component'] ?? $reference['name'] ?? $product?->name ?? $line['name'] ?? $line['component'] ?? $line['code'] ?? '', 'unit' => $line['satuan'] ?? $reference['unit'] ?? $product?->unit ?? ''], $line);
            $name = trim((string) ($line['item_name'] ?? $line['name'] ?? $line['component'] ?? $line['komponen'] ?? $line['code'] ?? ''));
            if ($name === '' && empty($line['quantity']) && empty($line['qty']) && empty($line['qty_sent'])) {
                continue; // The UI may include one completely empty trailing input row.
            }
            $qty = $this->number($line['quantity'] ?? $line['qty'] ?? $line['volume'] ?? $line['qty_sent'] ?? 0);
            $offer = $this->number($key === 'klarifikasi_nego' ? ($line['original_price'] ?? $line['price_offer'] ?? $line['price'] ?? 0) : ($line['price'] ?? $line['price_offer'] ?? $line['original_price'] ?? $line['harga'] ?? 0));
            $negotiated = $this->number($line['nego_price'] ?? $line['price_nego'] ?? $offer);
            // Invoice rows already contain the final price entered by Administration.
            $price = $key === 'faktur' && array_key_exists('price', $line) ? $offer : ($useNegotiated ? $negotiated : $offer);
            if ($name === '' || $qty <= 0 || $price < 0 || ! is_finite($qty * $price) || $qty * $price > 1e12) {
                throw ValidationException::withMessages(['form_data.items' => 'Lengkapi nama, volume, satuan, dan harga rincian barang.']);
            }
            $items[] = ['no' => count($items) + 1, 'name' => $name, 'quantity' => $qty,
                'unit' => $line['unit'] ?? $line['satuan'] ?? '', 'specs' => $line['specs'] ?? $line['description'] ?? '',
                'price' => $price, 'offer' => $offer, 'total' => round($qty * $price, 2)];
        }
        $subtotal = round(array_sum(array_column($items, 'total')), 2);
        $vatRate = $this->number($form['ppn_rate'] ?? 11);
        $withholdingRate = $this->number($form['pph_rate'] ?? 1.5);
        if ($vatRate < 0 || $vatRate > 100 || $withholdingRate < 0 || $withholdingRate > 100) {
            throw ValidationException::withMessages(['form_data' => 'Tarif pajak harus berada di antara 0 dan 100.']);
        }
        $vat = round($subtotal * $vatRate / 100);
        $gross = $subtotal + $vat;
        $withholding = round($subtotal * $withholdingRate / 100);
        $net = $gross - $withholding;
        $blocks = [];
        $fields = [
            'Nomor Dokumen' => $number, 'Tanggal' => $date,
            'Proyek' => $form['project_name'] ?? $project->project_name,
            'Customer / Instansi' => $customer,
        ];
        $fields += match ($key) {
            'cover_project' => ['Sumber Dana' => $form['funding_source'] ?? $project->funding_source,
                'Tahun Anggaran' => $form['fiscal_year'] ?? $project->fiscal_year,
                'Nilai Pekerjaan' => $this->money($this->number($form['total_value'] ?? $project->project_value)), 'Penyedia' => $company],
            'perencanaan' => ['Nama Kegiatan Pengadaan' => $activity, 'Sumber Dana' => $form['funding_source'] ?? $project->funding_source,
                'Waktu Pelaksanaan' => $form['waktu_pelaksanaan'] ?? '', 'Lokasi Pekerjaan' => $form['lokasi_pekerjaan'] ?? '',
                'Tahun Anggaran' => $form['fiscal_year'] ?? $project->fiscal_year],
            'hps' => ['Nama Pekerjaan' => $activity, 'Pagu Anggaran' => $this->money($this->number($form['budget_ceiling'] ?? $project->budget_ceiling)),
                'No. Rekening' => $form['account_code'] ?? $project->account_code, 'Sumber Dana' => $form['funding_source'] ?? $project->funding_source],
            'spph' => ['Pekerjaan' => $activity, 'Batas Akhir Penawaran' => $this->optionalDate($form['batas_akhir_penawaran'] ?? ''), 'Penyedia Tujuan' => $company],
            'klarifikasi_nego' => ['Tanggal Negosiasi' => $date, 'Hasil Negosiasi' => $form['nego_result'] ?? ''],
            'spk' => ['Paket Pekerjaan' => $activity, 'Mulai Pekerjaan' => $this->optionalDate($project->start_date), 'Selesai Pekerjaan' => $this->optionalDate($project->end_date), 'Penyedia' => $company],
            'hasil_pemeriksaan' => ['Hasil Pemeriksaan' => $form['hasil_pemeriksaan'] ?? '', 'Catatan Pemeriksaan' => $form['catatan'] ?? ''],
            'bast_sekolah', 'bast_company' => ['Pekerjaan' => $activity, 'Pihak Penyedia' => $company, 'Pihak Penerima' => $customer],
            'surat_jalan' => ['Pengemudi' => $form['nama_supir'] ?? $wh['driver_name'] ?? '', 'Kendaraan' => $form['kendaraan'] ?? '',
                'Nomor Kendaraan' => $form['no_kendaraan'] ?? $wh['vehicle_plate'] ?? ''],
            'faktur' => ['Jenis Harga' => $form['jenis_harga'] ?? '', 'Nama Pekerjaan' => $activity],
            'kuitansi' => ['Sudah Diterima Dari' => $customer, 'Metode Pembayaran' => $form['metode'] ?? $finance['payment_method'] ?? '',
                'Untuk Pembayaran' => $form['keterangan'] ?? $activity],
            'bukti_foto' => ['Lokasi Dokumentasi' => $form['lokasi'] ?? '', 'Tahap Pekerjaan' => $form['tahap'] ?? ''],
            default => ['Nama Pekerjaan' => $activity, 'Penyedia' => $company],
        };
        $blocks[] = ['type' => 'fields', 'rows' => array_map(fn ($label, $value) => [$label, (string) ($value ?? '')], array_keys($fields), array_values($fields))];
        $legacyContent = match ($key) {
            'penawaran' => $form['isi_penawaran'] ?? '',
            'klarifikasi_nego' => $form['isi_undangan'] ?? '',
            'bast_sekolah', 'bast_company' => $form['isi_bast'] ?? '',
            default => '',
        };
        if (! array_key_exists('content', $form) && $legacyContent !== '') {
            $form['content'] = $legacyContent;
        }
        foreach (['content', 'keterangan', 'instruksi_penyedia', 'notes'] as $field) {
            if (! empty($form[$field]) && ! ($key === 'kuitansi' && $field === 'keterangan')) {
                $blocks[] = ['type' => 'paragraph', 'text' => $form[$field]];
            }
        }

        if (! in_array($key, ['cover_project', 'kuitansi', 'bukti_foto'], true)) {
            if (! $items) {
                throw ValidationException::withMessages(['form_data.items' => 'Tambahkan minimal satu rincian barang sebelum mengunduh dokumen.']);
            }
            $quantitiesOnly = in_array($key, ['spph', 'hasil_pemeriksaan', 'bast_sekolah', 'bast_company', 'surat_jalan'], true);
            $headers = $quantitiesOnly ? ['No.', 'Komponen / Spesifikasi Barang', 'Volume', 'Satuan']
                : ($key === 'klarifikasi_nego' ? ['No.', 'Komponen', 'Volume', 'Satuan', 'Harga Penawaran', 'Harga Negosiasi', 'Jumlah']
                    : ['No.', 'Komponen / Spesifikasi Barang', 'Volume', 'Satuan', $key === 'perencanaan' ? 'Harga Estimasi (Rp)' : 'Harga Satuan (Rp)', 'Jumlah (Rp)']);
            $rows = [];
            foreach ($items as $item) {
                $row = [(string) $item['no'], $item['name'].($item['specs'] !== '' ? "\n".$item['specs'] : ''), $this->decimal($item['quantity']), $item['unit']];
                if (! $quantitiesOnly) {
                    if ($key === 'klarifikasi_nego') {
                        $row[] = $this->money($item['offer']);
                    }
                    $row[] = $this->money($item['price']);
                    $row[] = $this->money($item['total']);
                }
                $rows[] = $row;
            }
            $blocks[] = ['type' => 'table', 'headers' => $headers, 'rows' => $rows];
            if (! $quantitiesOnly) {
                $totals = $key === 'perencanaan' ? [['TOTAL ESTIMASI PERENCANAAN', $this->money($subtotal)]]
                    : [['Subtotal', $this->money($subtotal)], ['PPN '.$this->decimal($vatRate).'%', $this->money($vat)], ['Total', $this->money($gross)]];
                if (in_array($key, ['faktur', 'spk'], true)) {
                    $totals[] = ['PPH '.$this->decimal($withholdingRate).'%', $this->money($withholding)];
                    $totals[] = ['Nilai Setelah PPH', $this->money($net)];
                }
                $blocks[] = ['type' => 'totals', 'rows' => $totals];
                $blocks[] = ['type' => 'paragraph', 'text' => 'Terbilang: '.$this->words($key === 'perencanaan' ? $subtotal : (in_array($key, ['faktur', 'spk'], true) ? $net : $gross))];
            }
        }
        if ($key === 'kuitansi') {
            $amount = $this->number($form['nilai_setelah_pph'] ?? $form['jumlah'] ?? $project->invoice_paid ?? 0);
            $blocks[] = ['type' => 'totals', 'rows' => [['Jumlah Pembayaran', $this->money($amount)]]];
            $blocks[] = ['type' => 'paragraph', 'text' => 'Terbilang: '.$this->words($amount)];
        }
        if ($key === 'bukti_foto') {
            foreach ($form['photos'] ?? [] as $photo) {
                $caption = trim(($photo['title'] ?? '')."\n".($photo['desc'] ?? $photo['description'] ?? ''));
                if (! empty($photo['data_url'])) {
                    if (! is_string($photo['data_url']) || strlen($photo['data_url']) > 4200000 || ! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $photo['data_url'], $match)) {
                        throw ValidationException::withMessages(['form_data.photos' => 'Gunakan foto JPG/PNG maksimal 3 MB per foto.']);
                    }
                    $bytes = base64_decode($match[2], true);
                    $image = $bytes ? @getimagesizefromstring($bytes) : false;
                    if (! $image || ! in_array($image['mime'], ['image/jpeg', 'image/png'], true) || $image[0] * $image[1] > 24000000) {
                        throw ValidationException::withMessages(['form_data.photos' => 'Berkas foto tidak valid atau resolusinya terlalu besar.']);
                    }
                    $blocks[] = ['type' => 'image', 'caption' => $caption, 'mime' => $image['mime'], 'width' => $image[0], 'height' => $image[1], 'data' => base64_encode($bytes)];
                } else {
                    $blocks[] = ['type' => 'paragraph', 'text' => $caption];
                }
            }
            $source = $doc->source_file_path ?: (str_contains($doc->file_path ?? '', '/external/') ? $doc->file_path : null);
            $root = rtrim(config('spj.storage_root'), '/\\');
            $path = $source ? $root.'/'.$source : null;
            if ($path && is_file($path)) {
                $image = @getimagesize($path);
                if ($image && in_array($image['mime'], ['image/jpeg', 'image/png'], true)) {
                    $blocks[] = ['type' => 'image', 'mime' => $image['mime'], 'width' => $image[0], 'height' => $image[1], 'data' => base64_encode(file_get_contents($path))];
                }
            }
        }
        $schoolHeader = in_array($key, ['cover_project', 'perencanaan', 'hps', 'spph', 'klarifikasi_nego', 'permintaan_penawaran', 'spk', 'hasil_pemeriksaan', 'bast_sekolah'], true);
        $terbilangAmount = $key === 'perencanaan' ? $subtotal : (in_array($key, ['faktur', 'spk'], true) ? $net : ($key === 'kuitansi' ? $this->number($form['nilai_setelah_pph'] ?? $form['jumlah'] ?? $project->invoice_paid ?? $net) : $gross));
        $terbilang = $this->words($terbilangAmount);

        return [
            'key' => $key,
            'title' => self::TITLES[$key],
            'number' => $number,
            'date' => $date,
            'school_header' => $schoolHeader,
            'institutional_header' => $schoolHeader ? app(SpjLetterhead::class)->build($project) : null,
            'letterhead' => $schoolHeader ? $customer : $company,
            'address' => $schoolHeader ? ($form['alamat_sekolah'] ?? $meta['customer_address'] ?? '') : ($meta['company_address'] ?? ''),
            'blocks' => $blocks,
            'city' => $form['city_signature'] ?? $project->city_signature ?? '',
            'signatures' => [
                ['label' => 'Penyedia', 'name' => $project->director_approval['name'] ?? '', 'title' => $project->director_approval['title'] ?? 'Direktur', 'identity' => ''],
                ['label' => $form['pic_title'] ?? $project->pic_title ?: 'Penanggung Jawab', 'name' => $form['pic_name'] ?? $project->pic_name ?? '',
                    'title' => $customer, 'identity' => $form['pic_nip'] ?? $project->pic_nip ?? ''],
            ],
            // Enriched fields for in-place docx rendering and blade template
            'activity' => $activity,
            'project_name' => $form['project_name'] ?? $project->project_name,
            'customer_name' => $customer,
            'company_name' => $company,
            'funding_source' => $form['funding_source'] ?? $project->funding_source ?? '',
            'account_code' => $form['account_code'] ?? $project->account_code ?? '',
            'fiscal_year' => $form['fiscal_year'] ?? $project->fiscal_year ?? date('Y'),
            'budget_ceiling' => $this->number($form['budget_ceiling'] ?? $project->budget_ceiling ?? 0),
            'items' => $items,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat' => $vat,
            'gross' => $gross,
            'withholding_rate' => $withholdingRate,
            'withholding' => $withholding,
            'net' => $net,
            'terbilang' => $terbilang,
            'keterangan' => $form['keterangan'] ?? $form['notes'] ?? $form['content'] ?? '',
            'waktu_pelaksanaan' => $form['waktu_pelaksanaan'] ?? '',
            'hasil_pemeriksaan' => $form['hasil_pemeriksaan'] ?? '',
            'catatan' => $form['catatan'] ?? '',
            'pic_name' => $form['pic_name'] ?? $project->pic_name ?? '',
            'pic_nip' => $form['pic_nip'] ?? $project->pic_nip ?? '',
            'pic_title' => $form['pic_title'] ?? $project->pic_title ?: 'Kepala Sekolah',
            'nego_date' => $this->optionalDate($form['tanggal_negosiasi'] ?? $form['nego_date'] ?? ''),
            'nego_result' => $form['nego_result'] ?? '',
            'start_date' => $this->optionalDate($project->start_date),
            'end_date' => $this->optionalDate($project->end_date),
            'order_number' => $form['order_number'] ?? $form['nomor_pesanan'] ?? '',
            'exam_number' => $form['exam_number'] ?? $form['nomor_pemeriksaan'] ?? '',
            'driver_name' => $form['nama_supir'] ?? $wh['driver_name'] ?? '',
            'vehicle_plate' => $form['no_kendaraan'] ?? $wh['vehicle_plate'] ?? '',
            'vehicle_name' => $form['kendaraan'] ?? '',
            'payment_method' => $form['metode'] ?? $finance['payment_method'] ?? '',
            'paid_amount' => $this->number($form['nilai_setelah_pph'] ?? $form['jumlah'] ?? $project->invoice_paid ?? 0),
        ];
    }

    public function money(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    public function number(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $text = preg_replace('/[^0-9,.-]/', '', (string) $value);

        return (float) str_replace(',', '.', str_replace('.', '', $text));
    }

    private function decimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function optionalDate(?string $date): string
    {
        return $date ? $this->date($date) : '';
    }

    private function date(string $date): string
    {
        foreach (['!d/m/Y', '!Y-m-d', '!d-m-Y'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, substr($date, 0, 10));
            $errors = \DateTimeImmutable::getLastErrors();
            if ($parsed && (! $errors || (! $errors['warning_count'] && ! $errors['error_count']))) {
                return $parsed->format('d/m/Y');
            }
        }
        throw ValidationException::withMessages(['doc_date' => 'Tanggal dokumen tidak valid. Gunakan format tanggal/bulan/tahun.']);
    }

    public function words(float|int $amount): string
    {
        $convert = function (int $value) use (&$convert): string {
            $ones = ['Nol', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

            return match (true) {
                $value < 12 => $ones[$value],
                $value < 20 => $convert($value - 10).' Belas',
                $value < 100 => $convert(intdiv($value, 10)).' Puluh'.($value % 10 ? ' '.$convert($value % 10) : ''),
                $value < 200 => 'Seratus'.($value > 100 ? ' '.$convert($value - 100) : ''),
                $value < 1000 => $convert(intdiv($value, 100)).' Ratus'.($value % 100 ? ' '.$convert($value % 100) : ''),
                $value < 2000 => 'Seribu'.($value > 1000 ? ' '.$convert($value - 1000) : ''),
                default => $this->largeWords($value, $convert),
            };
        };

        return $convert((int) round(abs($amount))).' Rupiah';
    }

    private function largeWords(int $value, callable $convert): string
    {
        foreach ([1000000000000 => 'Triliun', 1000000000 => 'Miliar', 1000000 => 'Juta', 1000 => 'Ribu'] as $scale => $name) {
            if ($value >= $scale) {
                return $convert(intdiv($value, $scale)).' '.$name.($value % $scale ? ' '.$convert($value % $scale) : '');
            }
        }

        return '';
    }
}
