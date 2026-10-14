<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\OrderDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DocxTemplateService
{
    /**
     * Generate dokumen baru dengan mengisi data ke template master docx.
     *
     * @param  array<string, mixed>  $variables  Data placeholder yang ingin diisi
     * @param  string  $orderNumber  Nomor order terkait
     * @param  string|null  $docNumber  Nomor dokumen resmi (opsional)
     * @param  int|null  $userId  User yang men-generate
     */
    public function generate(
        DocumentTemplate $template,
        array $variables,
        string $orderNumber,
        ?string $docNumber = null,
        ?int $userId = null
    ): OrderDocument {
        $sourcePath = $template->master_full_path;

        if (! file_exists($sourcePath)) {
            throw new \RuntimeException("File template master tidak ditemukan: {$sourcePath}");
        }

        // Folder penyimpanan hasil dokumen per order
        $relativeDir = 'generated_docs/orders/'.Str::slug($orderNumber);
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! File::exists($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        $cleanDocType = Str::slug($template->code);
        $timestamp = date('Ymd_His');
        $fileName = "{$cleanDocType}_{$orderNumber}_{$timestamp}.docx";
        $targetRelativePath = "{$relativeDir}/{$fileName}";
        $targetAbsolutePath = "{$absoluteDir}/{$fileName}";

        // Salin master template ke lokasi tujuan hasil
        File::copy($sourcePath, $targetAbsolutePath);

        // Proses penggantian variabel di dalam file docx XML
        $this->replaceVariablesInDocx($targetAbsolutePath, $variables);

        $fileSize = file_exists($targetAbsolutePath) ? filesize($targetAbsolutePath) : 0;

        return OrderDocument::create([
            'order_id' => $variables['order_id'] ?? null,
            'order_number' => $orderNumber,
            'template_id' => $template->id,
            'doc_type' => $template->category,
            'doc_number' => $docNumber ?: ($variables['nomor_surat'] ?? $variables['nomor_po'] ?? null),
            'title' => "{$template->name} - {$orderNumber}",
            'file_path' => $targetRelativePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'status' => 'FINAL',
            'generated_by' => $userId,
            'metadata' => [
                'variables_used' => $variables,
                'generated_at' => now()->toIso8601String(),
                'template_code' => $template->code,
            ],
            'notes' => $variables['notes'] ?? 'Otomatis di-generate oleh sistem CV Alpharo Artha Jaya.',
        ]);
    }

    /**
     * Buka file docx (zip), baca word/document.xml, dan ganti placeholder dengan data.
     *
     * @param  array<string, mixed>  $variables
     */
    protected function replaceVariablesInDocx(string $docxFilePath, array $variables): void
    {
        $zip = new ZipArchive;

        if ($zip->open($docxFilePath) !== true) {
            throw new \RuntimeException("Gagal membuka file docx untuk rendering: {$docxFilePath}");
        }

        $documentXml = $zip->getFromName('word/document.xml');

        if ($documentXml !== false) {
            foreach ($variables as $key => $val) {
                if (is_array($val) || is_object($val)) {
                    continue;
                }

                $escapedVal = htmlspecialchars((string) $val, ENT_XML1, 'UTF-8');

                // Dukung format ${variable}, {{variable}}, dan $variable
                $patterns = [
                    '${'.$key.'}',
                    '{{'.$key.'}}',
                    '${'.strtoupper($key).'}',
                    '{{'.strtoupper($key).'}}',
                ];

                foreach ($patterns as $pattern) {
                    $documentXml = str_replace($pattern, $escapedVal, $documentXml);
                }
            }

            // Bersihkan sisa-sisa titik/dots template
            $documentXml = preg_replace('/<w:t>[\s\.\…]{2,}<\/w:t>/u', '<w:t></w:t>', $documentXml);
            $documentXml = preg_replace('/[\.…]{3,}/u', '', $documentXml);

            $zip->addFromString('word/document.xml', $documentXml);
        }

        $zip->close();
    }

    /**
     * Perbarui file template master (.docx) yang diunggah oleh admin.
     */
    public function updateTemplateFile(DocumentTemplate $template, UploadedFile $file): void
    {
        $targetMasterPath = $template->master_full_path;
        $dir = dirname($targetMasterPath);

        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file->move($dir, basename($targetMasterPath));

        $template->update([
            'file_size' => file_exists($targetMasterPath) ? filesize($targetMasterPath) : null,
            'updated_at' => now(),
        ]);
    }
}
