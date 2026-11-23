<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

class FinanceInvoiceDocument
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private static array $cachedAssets = [];

    public function render(array $invoice, string $format): string
    {
        $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/temp/alpharo-inv-'.Str::uuid()));
        File::makeDirectory($dir, 0700, true);
        try {
            $docx = $dir.DIRECTORY_SEPARATOR.'invoice.docx';
            File::copy(public_path('docs/Kuitansi-TEMPLATE.docx'), $docx);
            $zip = new ZipArchive;
            if ($zip->open($docx) !== true) {
                throw new RuntimeException('Template Kuitansi tidak dapat dibuka.');
            }
            try {
                $xml = $this->filledDocument($zip->getFromName('word/document.xml'), $invoice);
                if (! $zip->addFromString('word/document.xml', $xml)) {
                    throw new RuntimeException('Isi invoice tidak dapat ditulis.');
                }
            } finally {
                if (! $zip->close()) {
                    throw new RuntimeException('Dokumen invoice tidak dapat disimpan.');
                }
            }

            if ($format === 'docx') {
                return File::get($docx);
            }
            return $this->pdf($dir, $docx, $invoice);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    private function filledDocument(string $source, array $invoice): string
    {
        $dom = new DOMDocument;
        $dom->loadXML($source, LIBXML_NONET);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);
        $body = '/w:document/w:body';
        $info = $body.'/w:tbl[1]';
        $closing = $body.'/w:tbl[2]/w:tr';
        $receipt = $invoice['receipt'] ?? [];
        $amountBox = new DOMDocument;
        $amountBox->load(resource_path('views/invoice/amount.xml'), LIBXML_NONET);
        $oldBox = $xp->query($closing.'/w:tc[1]/w:tbl')->item(0);
        if (! $oldBox) {
            throw new RuntimeException('Kotak nominal pada template Kuitansi tidak ditemukan.');
        }
        $oldBox->parentNode->replaceChild($dom->importNode($amountBox->documentElement, true), $oldBox);
        $amount = 'Rp. '.number_format($invoice['totalAmount'], 0, '.', ',').',-';
        $slots = [
            $body.'/w:p[2]' => 'Nomor : '.(trim($receipt['number'] ?? '') ?: $invoice['invoiceNumber']),
            $info.'/w:tr[1]/w:tc[3]/w:p' => $invoice['clientName'] ?? '',
            $info.'/w:tr[2]/w:tc[3]/w:p' => '# '.app(SpjDocumentContent::class)->words($invoice['totalAmount']).' #',
            $info.'/w:tr[3]/w:tc[3]/w:p' => $this->paymentDescription($invoice, $receipt),
            $closing.'/w:tc[1]//w:txbxContent/w:p' => $amount,
            $closing.'/w:tc[2]/w:p[1]' => 'Jakarta, '.$this->printedDate($invoice['invoiceDate'] ?? ''),
        ];
        foreach ($slots as $selector => $value) {
            $runs = $xp->query($selector.'/w:r/w:t');
            if (! $runs->length) {
                throw new RuntimeException('Susunan kolom pada template Kuitansi telah berubah.');
            }
            foreach ($runs as $index => $text) {
                $text->textContent = $index === 0 ? preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) : '';
            }
        }

        // Match the reference body while keeping the stationery and section from the master.
        foreach ($xp->query($body.'//w:rFonts') as $font) {
            $font->setAttributeNS(self::W, 'w:ascii', 'Calibri');
            $font->setAttributeNS(self::W, 'w:hAnsi', 'Calibri');
        }
        foreach ($xp->query($body.'//w:sz[@w:val="20"]') as $size) {
            $size->setAttributeNS(self::W, 'w:val', '26');
        }
        $xp->query($body.'/w:p[1]//w:sz')->item(0)->setAttributeNS(self::W, 'w:val', '40');
        foreach ($xp->query($body.'/w:p[2]//w:b | '.$closing.'/w:tc[2]//w:b') as $bold) {
            $bold->parentNode->removeChild($bold);
        }
        foreach ($xp->query($body.'/w:p/w:pPr/w:ind | '.$body.'/w:tbl/w:tblPr/w:tblInd') as $indent) {
            $indent->setAttributeNS(self::W, $indent->localName === 'ind' ? 'w:left' : 'w:w', '0');
        }
        foreach ($xp->query($closing.'/w:tc[2]//w:pPr/w:ind') as $indent) {
            $indent->setAttributeNS(self::W, 'w:left', '1550');
        }
        if (strlen($amount) > 19) {
            $xp->query($closing.'//w:txbxContent//w:sz')->item(0)->setAttributeNS(self::W, 'w:val', '24');
        }

        return $dom->saveXML();
    }

    private function paymentDescription(array $invoice, array $receipt): string
    {
        $activity = trim($receipt['activity'] ?? '') ?: trim($invoice['projectName'] ?? '');
        $detail = trim($receipt['activityDetail'] ?? '');
        if ($detail !== '' && ! str_contains(mb_strtolower($activity), mb_strtolower($detail))) {
            $activity = $activity === '' ? $detail : $activity.' ('.trim($detail, '()').')';
        }
        $text = preg_match('/^(pembelian|pengadaan)\b/iu', $activity) ? $activity : trim('Pembelian Barang/Jasa '.$activity);
        $funding = trim($receipt['fundingSource'] ?? '');
        if ($funding !== '') {
            $text .= ', yang dibiayai dari '.(strtoupper($funding) === 'BOP' ? 'Dana Bantuan Operasional Pendidikan (BOP)' : $funding);
        }
        if (trim($receipt['fiscalYear'] ?? '') !== '') {
            $text .= ' Tahun Anggaran '.$receipt['fiscalYear'];
        }

        return $text.', sesuai Faktur Penjualan Nomor: '.(trim($receipt['salesInvoiceNumber'] ?? '') ?: $invoice['invoiceNumber']);
    }

    private function printedDate(string $value): string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            if (Carbon::canBeCreatedFromFormat($value, $format)) {
                return Carbon::createFromFormat('!'.$format, $value)->locale('id')->translatedFormat('j F Y');
            }
        }
        return $value;
    }

    private function pdf(string $dir, string $docx, array $invoice): string
    {
        $output = $dir.DIRECTORY_SEPARATOR.'invoice.pdf';
        $office = config('services.invoice.soffice') ?: (new ExecutableFinder)->find('soffice');
        $command = null;
        if ($office) {
            $profile = str_replace('\\', '/', $dir).'/profile';
            $uri = 'file://'.(str_starts_with($profile, '/') ? '' : '/').str_replace(['%',' ','#','?'], ['%25','%20','%23','%3F'], $profile);
            $command = [$office, '-env:UserInstallation='.$uri, '--headless', '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $dir, $docx];
        } elseif (PHP_OS_FAMILY === 'Windows' && app()->environment('local', 'testing')) {
            $powershell = (new ExecutableFinder)->find('powershell.exe') ?: 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
            if (is_file($powershell)) {
                $command = [$powershell, '-NoProfile', '-NonInteractive', '-File', resource_path('scripts/invoice-pdf.ps1'), $docx, $output];
            }
        }

        if ($command !== null) {
            $process = new Process($command, $dir, ['TEMP' => $dir, 'TMP' => $dir]);
            try {
                $process->setTimeout(45)->run();
                if ($process->isSuccessful() && is_file($output)) {
                    $bytes = File::get($output);
                    if (str_starts_with($bytes, '%PDF-')) {
                        return $bytes;
                    }
                }
            } catch (ExceptionInterface $error) {
                report($error);
            }
        }

        return $this->renderPdfWithDompdf($invoice);
    }

    private function renderPdfWithDompdf(array $invoice): string
    {
        $receipt = $invoice['receipt'] ?? [];
        $totalAmount = (float) ($invoice['totalAmount'] ?? 0);
        $amountFormatted = 'Rp. '.number_format($totalAmount, 0, '.', ',').',-';
        $receiptNumber = trim($receipt['number'] ?? '') ?: ($invoice['invoiceNumber'] ?? $invoice['orderNumber'] ?? '-');
        $clientName = $invoice['clientName'] ?? '';
        $amountWords = '# '.app(SpjDocumentContent::class)->words($totalAmount).' #';
        $paymentDescription = $this->paymentDescription($invoice, $receipt);
        $printedDate = $this->printedDate($invoice['invoiceDate'] ?? '');
        $assets = $this->extractTemplateAssets(public_path('docs/Kuitansi-TEMPLATE.docx'));
        $amountBoxSvg = $this->amountBoxDataUri($amountFormatted);

        $cache = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('framework/cache/invoice-pdf'));
        File::ensureDirectoryExists($cache);

        $options = new Options([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
            'tempDir' => $cache,
            'fontCache' => $cache,
        ]);

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(view('invoice.kuitansi', compact(
            'assets',
            'amountBoxSvg',
            'receiptNumber',
            'clientName',
            'amountWords',
            'paymentDescription',
            'amountFormatted',
            'printedDate',
        ))->render(), 'UTF-8');
        $pdf->render();

        $bytes = $pdf->output();
        if (! is_string($bytes) || ! str_starts_with($bytes, '%PDF-')) {
            abort(503, 'Konverter belum menghasilkan PDF yang valid.');
        }

        return $bytes;
    }

    private function extractTemplateAssets(string $templatePath): array
    {
        if (isset(self::$cachedAssets[$templatePath])) {
            return self::$cachedAssets[$templatePath];
        }
        $assets = [
            'background' => null,
            'logo' => null,
            'icons' => [],
        ];
        $zip = new ZipArchive;
        if ($zip->open($templatePath) !== true) {
            return $assets;
        }
        try {
            $toDataUri = static function (?string $bytes): ?string {
                if (! $bytes) {
                    return null;
                }
                $info = @getimagesizefromstring($bytes);
                if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg'], true)) {
                    return null;
                }

                return 'data:'.$info['mime'].';base64,'.base64_encode($bytes);
            };

            // image2.png (1044x1491) is the full-page A4 stationery; image1.png (1254x1254) is the top-left logo.
            for ($i = 1; $i <= 5; $i++) {
                $bytes = $zip->getFromName('word/media/image'.$i.'.png');
                if (! $bytes) {
                    continue;
                }
                $info = @getimagesizefromstring($bytes);
                if (! $info) {
                    continue;
                }
                $uri = $toDataUri($bytes);
                if ($info[1] > $info[0] && $assets['background'] === null) {
                    $assets['background'] = $uri;
                } elseif ($info[0] >= 500 && $assets['logo'] === null) {
                    $assets['logo'] = $uri;
                } elseif ($info[0] <= 300) {
                    $assets['icons'][] = $uri;
                }
            }
        } finally {
            $zip->close();
        }

        return self::$cachedAssets[$templatePath] = $assets;
    }

    private function amountBoxDataUri(string $amount): string
    {
        $fontSize = strlen($amount) > 19 ? 15 : 18;
        $escaped = htmlspecialchars($amount, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="52" viewBox="0 0 240 52">
  <polygon points="16,2 238,2 224,50 2,50" fill="#FFC000" stroke="#000000" stroke-width="2.2" stroke-linejoin="round" />
  <text x="120" y="32" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="{$fontSize}" font-weight="bold" font-style="italic" fill="#000000">{$escaped}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
