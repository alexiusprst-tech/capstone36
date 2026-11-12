<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SpjDocument;
use App\Models\SpjProject;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpjService
{
    /**
     * Peta file master template untuk 15 jenis dokumen SPJ
     */
    protected array $templateMap = [
        'cover_project' => 'templates/master/SPPH-PR00001-TEMPLATE.docx', // Letterhead package; renderer writes the cover body.
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
                if (empty($project->items[0]['nego_price'] ?? $project->items[0]['price_nego'] ?? null)) {
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
                $order = SalesOrder::where('details->projectId', $project->project_id)->first();
                if ($order && ! $order->isApprovedForInvoice()) {
                    $missing[] = 'Pesanan belum mendapat persetujuan valid untuk invoice';
                }
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
        abort_unless(isset($this->templateMap[$docKey]), 404);
        $doc = SpjDocument::where('project_id', $project->project_id)->where('doc_key', $docKey)->firstOrFail();
        $effective = array_replace($doc->form_data ?? [], $formData);
        $validationProject = clone $project;
        $validationProject->fill(collect($effective)->only(['project_name', 'customer_name', 'items', 'budget_ceiling', 'pic_name', 'funding_source'])->all());
        $validation = $this->validateDocumentData($validationProject, $docKey);
        if (! $validation['is_complete']) {
            throw ValidationException::withMessages(['document' => implode('; ', $validation['missing'])]);
        }
        $content = app(SpjDocumentContent::class)->build($project, $doc, $effective);
        $master = storage_path('app/'.$this->templateMap[$docKey]);
        if (! is_file($master)) {
            throw new \RuntimeException("Template master tidak ditemukan untuk dokumen {$docKey}.");
        }
        $headerHash = $content['school_header'] ? hash_file('sha256', config('spj.letterhead_template')) : '';
        $hash = substr(hash('sha256', 'spj-renderer-v7.0'.$headerHash.hash_file('sha256', $master).json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)), 0, 24);
        $relativeDir = 'generated_docs/spj/'.Str::slug($project->project_id);
        $fileName = $docKey.'_'.$project->project_id.'_'.$hash.'.docx';
        $relative = $relativeDir.'/'.$fileName;
        $root = config('spj.storage_root');
        $path = $root.'/'.$relative;
        if ($doc->file_path === $relative && is_file($path) && $doc->status === 'FINAL' && ($doc->form_data ?? []) === $effective) {
            return $doc;
        }
        File::ensureDirectoryExists($root.'/'.$relativeDir);
        $temporary = $path.'.'.Str::uuid().'.tmp';
        try {
            if (! is_file($path)) {
                app(SpjDocxRenderer::class)->render($master, $temporary, $content);
                File::move($temporary, $path);
            }
        } finally {
            if (is_file($temporary)) {
                File::delete($temporary);
            }
        }
        $source = $doc->source_file_path;
        if (! $source && $doc->file_path && str_contains($doc->file_path, '/external/')) {
            $source = $doc->file_path;
        }
        $doc->update([
            'status' => 'FINAL', 'is_data_complete' => true, 'missing_requirements' => null,
            'doc_number' => $content['number'], 'doc_date' => $content['date'],
            'file_path' => $relative, 'file_name' => $fileName, 'file_type' => 'docx',
            'source_file_path' => $source, 'version' => $doc->version + 1, 'form_data' => $effective,
        ]);

        return $doc->fresh();
    }

    public function renderPdf(SpjProject $project, SpjDocument $doc): string
    {
        $target = preg_replace('/\.docx$/i', '.pdf', $doc->full_path);
        if (is_file($target)) {
            return $target;
        }
        $content = app(SpjDocumentContent::class)->build($project, $doc, $doc->form_data ?? []);
        $letterhead = app(SpjDocxRenderer::class)->letterhead(storage_path('app/'.$this->templateMap[$doc->doc_key]));
        if ($content['school_header']) {
            $letterhead = ['background' => false, 'template_logo' => empty($content['institutional_header']['logo']), 'url' => app(SpjDocxRenderer::class)->institutionalLogo($content['institutional_header'], storage_path('app/'.$this->templateMap[$doc->doc_key]))];
        }
        $cache = storage_path('framework/cache/spj-pdf');
        File::ensureDirectoryExists($cache);
        $options = new Options([
            'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans',
            'tempDir' => $cache, 'fontCache' => $cache,
        ]);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(view('spj.document', compact('content', 'letterhead'))->render(), 'UTF-8');
        $pdf->render();
        $temporary = $target.'.'.Str::uuid().'.tmp';
        try {
            File::put($temporary, $pdf->output());
            File::move($temporary, $target);
        } finally {
            if (is_file($temporary)) {
                File::delete($temporary);
            }
        }

        return $target;
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
                    'reason' => $e instanceof ValidationException ? implode('; ', array_merge(...array_values($e->errors()))) : $e->getMessage(),
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
        $absoluteDir = config('spj.storage_root').'/'.$relativeDir;

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
            'source_file_path' => "{$relativeDir}/{$safeName}",
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
        return app(SpjDocumentContent::class)->words($nilai);
    }
}
