<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Renders SPJ Word (.docx) documents by strictly preserving the master template's
 * paragraphs, sentences, fonts, table styling, borders, margins, and layout structure.
 * Only dynamic input data is substituted into the template placeholders.
 */
class SpjDocxRenderer
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function letterhead(string $master): ?array
    {
        $zip = new ZipArchive;
        if ($zip->open($master) !== true) {
            return null;
        }
        try {
            $header = $zip->locateName('word/header1.xml') !== false;
            $part = $header ? 'header1.xml' : 'document.xml';
            $dom = new DOMDocument;
            $dom->loadXML($zip->getFromName('word/'.$part), LIBXML_NONET);
            $xpath = new DOMXPath($dom);
            $image = $xpath->query('//*[local-name()="blip" or local-name()="imagedata"]')->item(0);
            if (! $image) {
                return null;
            }
            $id = $image->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed') ?: $image->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $rels = new DOMDocument;
            $rels->loadXML($zip->getFromName('word/_rels/'.$part.'.rels'), LIBXML_NONET);
            foreach ($rels->documentElement->childNodes as $relation) {
                if ($relation instanceof DOMElement && $relation->getAttribute('Id') === $id) {
                    $bytes = $zip->getFromName('word/'.$relation->getAttribute('Target'));
                    $info = $bytes ? @getimagesizefromstring($bytes) : false;
                    if ($info && in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
                        return ['background' => $header && $info[1] > $info[0], 'url' => 'data:'.$info['mime'].';base64,'.base64_encode($bytes)];
                    }
                }
            }

            return null;
        } finally {
            $zip->close();
        }
    }

    public function institutionalLogo(array $header, string $master): string
    {
        if (! empty($header['logo'])) {
            return $header['logo'];
        }

        return $this->letterhead(config('spj.letterhead_template'))['url'] ?? '';
    }

    public function render(string $master, string $target, array $content): void
    {
        if (! copy($master, $target)) {
            throw new RuntimeException('Template dokumen tidak dapat disalin.');
        }
        $zip = new ZipArchive;
        if ($zip->open($target) !== true) {
            throw new RuntimeException('Template Word tidak dapat dibuka.');
        }

        try {
            $xml = $zip->getFromName('word/document.xml');
            $dom = new DOMDocument;
            if (! $xml || ! $dom->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('Struktur template Word tidak valid.');
            }
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::W);
            $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $xpath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');

            // 1. School Kop Update if school_header is true
            if (! empty($content['school_header']) && ! empty($content['institutional_header'])) {
                $this->updateSchoolLetterhead($dom, $xpath, $zip, $content['institutional_header'], $master);
            }

            // 2. Perform in-place textual and placeholder substitutions
            $this->replaceTextPlaceholders($dom, $xpath, $content);
            $this->replaceSignaturesAndIdentities($dom, $xpath, $content);

            // 3. Process items table (cloning template rows and updating totals)
            if (! empty($content['items']) && ! in_array($content['key'] ?? '', ['kuitansi', 'bukti_foto'], true)) {
                $this->processItemsTable($dom, $xpath, $content);
            }

            // 4. Bukti Foto / Photo embeddings
            if (($content['key'] ?? '') === 'bukti_foto') {
                $this->processPhotoAttachments($dom, $xpath, $zip, $content);
            }

            // 5. Append admin custom notes if present and not yet embedded
            $this->appendAdminNotes($dom, $xpath, $content);

            if (! $zip->addFromString('word/document.xml', $dom->saveXML())) {
                throw new RuntimeException('Isi dokumen Word tidak dapat disimpan.');
            }
        } finally {
            $zip->close();
        }
    }

    private function updateSchoolLetterhead(DOMDocument $dom, DOMXPath $xpath, ZipArchive $zip, array $header, string $master): void
    {
        // Generate reference header elements using institutionalHeader
        $kopXml = $this->institutionalHeader($zip, $header, $master);
        $frag = new DOMDocument;
        $frag->loadXML('<w:body xmlns:w="'.self::W.'">'.$kopXml.'</w:body>', LIBXML_NONET);

        $newTable = null;
        $newPostal = null;
        foreach ($frag->documentElement->childNodes as $node) {
            if ($node->localName === 'tbl' && ! $newTable) {
                $newTable = $dom->importNode($node, true);
            } elseif ($node->localName === 'p' && ! $newPostal) {
                $newPostal = $dom->importNode($node, true);
            }
        }

        $body = $xpath->query('//w:body')->item(0);
        if (! $body || ! $newTable || ! $newPostal) {
            return;
        }

        $existingTable0 = $xpath->query('//w:tbl[1]', $body)->item(0);
        $existingPostal = $xpath->query('//w:tbl[1]/following-sibling::w:p[1]', $body)->item(0);

        if ($existingTable0) {
            $body->insertBefore($newTable, $existingTable0);
            $body->insertBefore($newPostal, $existingTable0);
            $body->removeChild($existingTable0);
            if ($existingPostal) {
                $body->removeChild($existingPostal);
            }
        } else {
            $body->insertBefore($newPostal, $body->firstChild);
            $body->insertBefore($newTable, $newPostal);
        }

        // Check if there is a second kop table (like in Klarifikasi-Nego section 2)
        $secondKop = $xpath->query('//w:tbl[w:tr/w:tc[2]//w:p[contains(.,"DINAS PENDIDIKAN") or contains(.,"PEMERINTAH")]]', $body);
        foreach ($secondKop as $idx => $tbl) {
            if ($idx > 0) {
                $cell2 = $xpath->query('w:tr[1]/w:tc[2]', $tbl)->item(0);
                if ($cell2) {
                    $paragraphs = iterator_to_array($xpath->query('w:p', $cell2));
                    $fields = [
                        0 => ['text' => $header['government'] ?: 'PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA', 'size' => 24, 'bold' => true],
                        1 => ['text' => $header['department'] ?: 'DINAS PENDIDIKAN', 'size' => 22, 'bold' => true],
                        2 => ['text' => mb_strtoupper($header['name'] ?? ''), 'size' => 40, 'bold' => true],
                        3 => ['text' => $header['address'] ?? '', 'size' => 22, 'bold' => false],
                        4 => ['text' => $header['contacts'] ?? '', 'size' => 22, 'bold' => false],
                        5 => ['text' => $header['online'] ?? '', 'size' => 22, 'bold' => false],
                    ];
                    foreach ($fields as $fIdx => $f) {
                        if (isset($paragraphs[$fIdx])) {
                            $this->fillHeaderParagraph($dom, $xpath, $paragraphs[$fIdx], $f['text'], $f['size']);
                        }
                    }
                }
            }
        }
    }

    private function indonesianDateParts(string $dateStr): array
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $d = null;
        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', trim($dateStr), $m)) {
            $monthIdx = array_search(ucfirst(strtolower($m[2])), array_map('ucfirst', $months));
            if ($monthIdx) {
                $d = \DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $m[3], $monthIdx, $m[1]));
            }
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($dateStr), $m)) {
            $d = \DateTime::createFromFormat('d/m/Y', trim($dateStr));
        } else {
            $ts = strtotime($dateStr);
            if ($ts) {
                $d = (new \DateTime)->setTimestamp($ts);
            }
        }
        if (! $d) {
            $d = new \DateTime;
        }
        $dayName = $days[$d->format('l')] ?? 'Senin';
        $day = $d->format('d');
        $monthName = $months[(int) $d->format('n')] ?? 'Januari';
        $year = $d->format('Y');

        return [
            'day_name' => $dayName,
            'day' => $day,
            'month_name' => $monthName,
            'year' => $year,
            'full' => "$day $monthName $year",
        ];
    }

    private function replaceTextPlaceholders(DOMDocument $dom, DOMXPath $xpath, array $content): void
    {
        $dateStr = $content['date'] ?? date('d/m/Y');
        $dateParts = $this->indonesianDateParts($dateStr);
        $city = $content['city'] ?: 'Jakarta';
        $fullCityDate = $city.', '.$dateParts['full'];
        $docNumber = $content['number'] ?? '';
        $schoolRaw = $content['customer_name'] ?? ($content['institutional_header']['name'] ?? ($content['letterhead'] ?? ''));
        $schoolName = SpjLetterhead::expandSchoolName($schoolRaw);
        $schoolUpper = mb_strtoupper($schoolName);
        $customerAddress = $content['institutional_header']['address'] ?? ($content['address'] ?? ($content['metadata']['customer_address'] ?? ''));
        $activity = $content['activity'] ?? ($content['project_name'] ?? '');
        $fiscalYear = $content['fiscal_year'] ?? date('Y');
        $fundingSource = $content['funding_source'] ?? 'BOS';
        $accountCode = $content['account_code'] ?? '-';
        $budgetCeiling = isset($content['budget_ceiling']) && $content['budget_ceiling'] > 0 ? 'Rp '.number_format($content['budget_ceiling'], 0, ',', '.') : '';
        $terbilang = $content['terbilang'] ?? '';
        $picName = $content['pic_name'] ?? '';
        $picNip = $content['pic_nip'] ?? '';

        if (! empty($content['signatures'])) {
            foreach ($content['signatures'] as $sig) {
                if (($sig['label'] ?? '') !== 'Penyedia' && ! empty($sig['name'])) {
                    if (! $picName) {
                        $picName = $sig['name'];
                    }
                    if (! $picNip && ! empty($sig['identity'])) {
                        $picNip = $sig['identity'];
                    }
                }
            }
        }

        $examNumber = $content['exam_number'] ?? $docNumber;
        $orderNumber = $content['order_number'] ?? $docNumber;
        $grossMoney = isset($content['gross']) ? 'Rp '.number_format($content['gross'], 0, ',', '.') : '';

        $replacements = [
            // Exact dotted strings & numbers
            'Nomor : ....................................................' => 'Nomor : '.$docNumber,
            ': ................................' => ': '.$docNumber,
            'Nomor SPK : ................................' => 'Nomor SPK : '.$docNumber,
            'No. ....................................................' => 'No. '.$docNumber,
            'No. ................................' => 'No. '.$docNumber,
            'Nomor : …………………….' => 'Nomor : '.$docNumber,
            'No. : …………………….' => 'No. : '.$docNumber,
            'No : …………………….' => 'No : '.$docNumber,
            'Nomor Surat Pemeriksaan : ' => 'Nomor Surat Pemeriksaan : '.$examNumber,
            'Nomor Surat Pemesanan : ....................................................' => 'Nomor Surat Pemesanan : '.$orderNumber,

            // Date & City
            'Jakarta, .... ................ ....' => $fullCityDate,
            '.... ................ ....' => $dateParts['full'],
            '………………, ……………… 20……' => $fullCityDate,
            'Jakarta, ……………… 20……' => $fullCityDate,
            '……………… 20……' => $dateParts['full'],
            'Pada hari ini, ................ tanggal .... bulan ................ tahun ........' => "Pada hari ini, {$dateParts['day_name']} tanggal {$dateParts['day']} bulan {$dateParts['month_name']} tahun {$dateParts['year']}",

            // Institution / School Name
            'Nama Satuan Pendidikan : ....................................................' => 'Nama Satuan Pendidikan : '.$schoolName,
            'Nama Satuan Pendidikan : ………………………………' => 'Nama Satuan Pendidikan : '.$schoolName,
            'Nama Satuan Pendidikan' => 'Nama Satuan Pendidikan : '.$schoolName,
            'Alamat Satuan Pendidikan : ................................................................................................' => 'Alamat Satuan Pendidikan : '.$customerAddress,
            'Alamat Satuan Pendidikan : ………………………………………………………………' => 'Alamat Satuan Pendidikan : '.$customerAddress,
            'SEKOLAH MENENGAH ...................' => $schoolName,
            'SEKOLAH MENENGAH ...............' => $schoolName,
            'SEKOLAH MENENGAH ………….' => $schoolName,
            'SEKOLAH MENENGAH ................' => $schoolName,
            'SEKOLAH MENENGAH .....................' => $schoolName,
            'Dalam hal ini bertindak untuk dan atas nama .................................................... sebagai PIHAK PERTAMA' => "Dalam hal ini bertindak untuk dan atas nama {$schoolName} sebagai PIHAK PERTAMA",
            'yang diserahkan oleh CV ALPHARO ARTHA JAYA kepada ...................................................., sebagaimana daftar barang/jasa terlampir.' => "yang diserahkan oleh CV ALPHARO ARTHA JAYA kepada {$schoolName}, sebagaimana daftar barang/jasa terlampir.",
            'Sudah Terima dari : ....................................................' => "Sudah Terima dari : {$picName} ({$schoolName})",
            "Sudah Terima dari\n:\n...................................................." => "Sudah Terima dari : {$picName} ({$schoolName})",
            'Menunjuk Surat Permintaan Penawaran Harga dari ………………,' => "Menunjuk Surat Permintaan Penawaran Harga dari {$schoolName},",
            'Menunjuk Surat Permintaan Penawaran Harga dari ………………' => "Menunjuk Surat Permintaan Penawaran Harga dari {$schoolName}",
            'Kepada .................................................... ....................................................' => "Kepada {$schoolName} ({$picName})",
            'Kepada Yth. ....................................................' => "Kepada Yth. {$schoolName} ({$picName})",
            'Kepada Yth ....................................................' => "Kepada Yth. {$schoolName} ({$picName})",

            // Activity / Pekerjaan
            'Sehubungan dengan kegiatan pengadaan ....................................................' => "Sehubungan dengan kegiatan pengadaan {$activity}",
            'kegiatan pengadaan ....................................................' => "kegiatan pengadaan {$activity}",
            'Paket Pekerjaan : ....................................................' => "Paket Pekerjaan : {$activity}",
            'Nama pekerjaan : ....................................................' => "Nama pekerjaan : {$activity}",
            'Nama Kegiatan : ....................................................' => "Nama Kegiatan : {$activity}",
            'Nama Pekerjaan : ………………' => "Nama Pekerjaan : {$activity}",
            'PIHAK PERTAMA menyerahkan pekerjaan .................................................... kepada PIHAK KEDUA' => "PIHAK PERTAMA menyerahkan pekerjaan {$activity} kepada PIHAK KEDUA",
            'penawaran harga untuk ………………………………. Harga penawaran' => "penawaran harga untuk {$activity}. Harga penawaran",
            'penawaran harga untuk ……………………………….' => "penawaran harga untuk {$activity}.",

            // Terbilang & Amounts
            'Terbilang : ....................................................' => 'Terbilang : '.$terbilang,
            'Terbilang : ……………………………' => 'Terbilang : '.$terbilang,
            '# .................................................... #' => '# '.$terbilang.' #',
            'Rp. ……………… (………………………………)' => $grossMoney.' ('.$terbilang.')',
            'Rp. ………..' => 'Rp '.number_format($content['paid_amount'] ?: ($content['net'] ?? 0), 0, ',', '.'),

            // Signers & NIP
            '(………………………..)' => '('.$picName.')',
            '(……………….)' => '('.$picName.')',
            '(…………………………….)' => '('.$picName.')',
            '(………………)' => '('.$picName.')',
            'NIP. ................' => 'NIP. '.$picNip,
            'NIP : …….' => 'NIP. '.$picNip,
            'NIP. …….' => 'NIP. '.$picNip,
            'NIP: ……..' => 'NIP. '.$picNip,
            'NIP. ...........' => 'NIP. '.$picNip,
            'Kepala Sekolah....................................................' => "Kepala Sekolah\n{$picName}",
            "Kepala Sekolah\n...................................................." => "Kepala Sekolah\n{$picName}",
        ];

        // Specific document title & field adjustments
        if (($content['key'] ?? '') === 'perencanaan') {
            $replacements['HARGA PERKIRAAN SENDIRI (HPS)'] = "DOKUMEN PERENCANAAN PENGADAAN\nNomor : {$docNumber}\nTanggal : {$dateStr}";
            $replacements['HARGA PERKIRAAN SENDIRI'] = "PERENCANAAN PENGADAAN\nNomor : {$docNumber}\nTanggal : {$dateStr}";
            if (! empty($content['waktu_pelaksanaan'])) {
                $replacements['Sumber Dana'] = 'Waktu Pelaksanaan : '.$content['waktu_pelaksanaan']."\nSumber Dana";
            }
        } elseif (($content['key'] ?? '') === 'hps') {
            $replacements['HARGA PERKIRAAN SENDIRI (HPS)'] = "HARGA PERKIRAAN SENDIRI (HPS)\nNomor : {$docNumber}\nTanggal : {$dateStr}";
        } elseif (($content['key'] ?? '') === 'cover_project') {
            $replacements['SURAT PERMINTAAN PENAWARAN HARGA'] = 'LAPORAN SURAT PERTANGGUNGJAWABAN PENGADAAN';
            $replacements['PERMINTAAN PENAWARAN HARGA'] = 'LAPORAN SURAT PERTANGGUNGJAWABAN PENGADAAN';
            $replacements['Permintaan Penawaran Harga Barang'] = 'LAPORAN SURAT PERTANGGUNGJAWABAN PENGADAAN';
            $replacements['Permintaan Penawaran Harga'] = 'LAPORAN SURAT PERTANGGUNGJAWABAN PENGADAAN';
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'spph') {
            $replacements['Permintaan Penawaran Harga Barang'] = "SURAT PERMINTAAN PENAWARAN HARGA\nTanggal : {$dateStr}";
            $replacements['Permintaan Penawaran Harga'] = "SURAT PERMINTAAN PENAWARAN HARGA\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'bukti_foto') {
            $replacements['LAMPIRAN DOKUMENTASI PEKERJAAN'] = "LAMPIRAN DOKUMENTASI PEKERJAAN\nNomor : {$docNumber}\nTanggal : {$dateStr}\n{$schoolName} - {$picName}";
        } elseif (($content['key'] ?? '') === 'penawaran') {
            $replacements['Surat Penawaran Harga'] = "SURAT PENAWARAN HARGA\nTanggal : {$dateStr}";
            $replacements['Surat Penawaran'] = "SURAT PENAWARAN HARGA\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
            $tbl0 = $xpath->query('//w:tbl')->item(0);
            if ($tbl0) {
                $r0c2 = $xpath->query('w:tr[1]/w:tc[3]', $tbl0)->item(0);
                if ($r0c2) {
                    $this->setCellText($r0c2, $docNumber, $xpath, $dom);
                }
            }
            $allP = iterator_to_array($xpath->query('//w:p'));
            foreach ($allP as $idx => $pItem) {
                $t = trim($pItem->textContent);
                if ($t === '…………………….') {
                    $this->setParagraphText($pItem, $schoolName, $xpath, $dom);
                } elseif (preg_match('/^…{10,}\.$/u', $t)) {
                    $this->setParagraphText($pItem, $picName ? "Up. {$picName} (Kepala Sekolah)" : $customerAddress, $xpath, $dom);
                }
            }
        } elseif (($content['key'] ?? '') === 'klarifikasi_nego') {
            $replacements['DOKUMEN HASIL NEGOSIASI'] = "BERITA ACARA KLARIFIKASI DAN NEGOSIASI\nTanggal : {$dateStr}";
            $replacements[': ................................'] = ': '.$docNumber;
            $replacements['surat saudara nomor : ………………………………'] = "surat saudara nomor : {$docNumber}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'permintaan_penawaran') {
            $replacements['SURAT PESANAN'] = "SURAT PESANAN\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'spk') {
            $replacements['SURAT PERINTAH KERJA (SPK)'] = "SURAT PERINTAH KERJA (SPK)\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'hasil_pemeriksaan') {
            $replacements['SURAT HASIL PEMERIKSAAN'] = "BERITA ACARA HASIL PEMERIKSAAN\nTanggal : {$dateStr}";
            $replacements['BERITA ACARA HASIL PEMERIKSAAN'] = "BERITA ACARA HASIL PEMERIKSAAN\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'bast_sekolah') {
            $replacements['BERITA ACARA SERAH TERIMA BARANG/JASA'] = "BERITA ACARA SERAH TERIMA SEKOLAH\nTanggal : {$dateStr}";
            $replacements['BERITA ACARA SERAH TERIMA'] = "BERITA ACARA SERAH TERIMA SEKOLAH\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'bast_company') {
            $replacements['BERITA ACARA SERAH TERIMA BARANG/JASA'] = "BERITA ACARA SERAH TERIMA PERUSAHAAN\nTanggal : {$dateStr}";
            $replacements['BERITA ACARA SERAH TERIMA'] = "BERITA ACARA SERAH TERIMA PERUSAHAAN\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
            $itemLines = [];
            foreach (($content['items'] ?? []) as $iIdx => $it) {
                $num = $iIdx + 1;
                $iName = $it['name'] ?? '';
                $iQty = $it['quantity'] ?? '';
                $iUnit = $it['unit'] ?? 'Unit';
                $itemLines[] = "{$num}. {$iName} : {$iQty} {$iUnit}";
            }
            if (! empty($itemLines)) {
                $replacements['1. .................................................... : ....................'] = implode("\n", $itemLines);
            }
            $replacements['TIM PENERIMA'] = "TIM PENERIMA\nKepala Sekolah : {$picName}";
        } elseif (($content['key'] ?? '') === 'faktur') {
            $replacements['FAKTUR'] = "FAKTUR\nTanggal : {$dateStr}";
            $replacements['Kepada Yth'] = "Kepada Yth.\n{$schoolName} ({$picName})";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'kuitansi') {
            $replacements['KUITANSI'] = "KUITANSI PEMBAYARAN\nTanggal : {$dateStr}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        } elseif (($content['key'] ?? '') === 'surat_jalan') {
            $replacements['SURAT JALAN'] = "SURAT JALAN\nTanggal : {$dateStr}";
            $replacements['Penerima Barang/Jasa'] = "Penerima Barang/Jasa\n{$schoolName} - {$picName}";
            $replacements[$fullCityDate] = $fullCityDate.' ('.$dateStr.')';
        }

        $paragraphs = $xpath->query('//w:p');
        foreach ($paragraphs as $p) {
            $this->replaceInP($p, $replacements, $xpath, $dom);
        }
    }

    private function replaceInP(DOMElement $p, array $replacements, DOMXPath $xpath, DOMDocument $dom): void
    {
        $tNodes = iterator_to_array($xpath->query('.//w:t', $p));
        if (empty($tNodes)) {
            return;
        }

        foreach ($tNodes as $t) {
            $val = $t->nodeValue;
            $newVal = $val;
            foreach ($replacements as $search => $replace) {
                if ($search !== '' && str_contains($newVal, $search)) {
                    $newVal = str_replace($search, $replace, $newVal);
                }
            }
            if ($newVal !== $val) {
                $t->nodeValue = '';
                $t->appendChild($dom->createTextNode($newVal));
            }
        }

        $combined = '';
        foreach ($tNodes as $t) {
            $combined .= $t->textContent;
        }
        $updatedCombined = $combined;
        foreach ($replacements as $search => $replace) {
            if ($search !== '' && str_contains($updatedCombined, $search)) {
                $updatedCombined = str_replace($search, $replace, $updatedCombined);
            }
        }
        if ($updatedCombined !== $combined) {
            $tNodes[0]->nodeValue = '';
            $tNodes[0]->appendChild($dom->createTextNode($updatedCombined));
            for ($i = 1; $i < count($tNodes); $i++) {
                $tNodes[$i]->nodeValue = '';
            }
        }
    }

    private function replaceSignaturesAndIdentities(DOMDocument $dom, DOMXPath $xpath, array $content): void
    {
        $schoolRaw = $content['customer_name'] ?? ($content['institutional_header']['name'] ?? '');
        $schoolName = SpjLetterhead::expandSchoolName($schoolRaw);
        $schoolUpper = mb_strtoupper($schoolName);
        $customerAddress = $content['institutional_header']['address'] ?? ($content['address'] ?? ($content['metadata']['customer_address'] ?? ''));
        $picName = $content['pic_name'] ?? '';
        $picNip = $content['pic_nip'] ?? '';

        // 1. In table rows: Nama vs Nama Satuan Pendidikan
        foreach ($xpath->query('//w:tr') as $tr) {
            $cells = iterator_to_array($xpath->query('w:tc', $tr));
            $colCount = count($cells);
            if ($colCount >= 3) {
                $label = '';
                foreach ($cells as $cIdx => $cell) {
                    $t = trim($cell->textContent);
                    if (preg_match('/^(Nama|Nama\s+Satuan\s+Pendidikan|Alamat\s+Satuan\s+Pendidikan|Sudah\s+Terima\s+dari)$/i', $t)) {
                        $label = $t;
                        break;
                    }
                }
                $targetCell = $cells[$colCount - 1];
                $cellText = trim($targetCell->textContent);

                if (preg_match('/^Nama$/i', $label) && (preg_match('/^\.{5,}$/u', $cellText) || empty($cellText))) {
                    $this->setCellText($targetCell, $picName, $xpath, $dom);
                } elseif (preg_match('/Nama\s+Satuan\s+Pendidikan/i', $label) && (preg_match('/^\.{5,}$/u', $cellText) || empty($cellText))) {
                    $this->setCellText($targetCell, $schoolName, $xpath, $dom);
                } elseif (preg_match('/Alamat\s+Satuan\s+Pendidikan/i', $label) && (preg_match('/^\.{5,}$/u', $cellText) || empty($cellText))) {
                    $this->setCellText($targetCell, $customerAddress, $xpath, $dom);
                } elseif (preg_match('/Sudah\s+Terima\s+dari/i', $label)) {
                    $this->setCellText($targetCell, "{$picName} ({$schoolName})", $xpath, $dom);
                }
            }
        }

        // 2. Under Kepala Sekolah / Pelaksana paragraphs:
        foreach ($xpath->query('//w:p') as $p) {
            $pText = trim($p->textContent);
            if (preg_match('/Kepala\s+Sekolah/i', $pText) || preg_match('/Pelaksana/i', $pText)) {
                $sibling = $p->nextSibling;
                while ($sibling) {
                    if ($sibling instanceof DOMElement && $sibling->localName === 'p') {
                        $sText = trim($sibling->textContent);
                        if ($sText !== '') {
                            if (preg_match('/^\.{5,}$/u', $sText) || preg_match('/^\([…\.\s]+\)$/u', $sText)) {
                                $this->setParagraphText($sibling, "({$picName})", $xpath, $dom);
                            }
                            break;
                        }
                    }
                    $sibling = $sibling->nextSibling;
                }
            }
        }
    }

    private function setParagraphText(DOMElement $p, string $text, DOMXPath $xpath, DOMDocument $dom): void
    {
        $rPr = $xpath->query('.//w:rPr', $p)->item(0)?->cloneNode(true);
        foreach (iterator_to_array($p->childNodes) as $child) {
            if ($child->localName !== 'pPr') {
                $p->removeChild($child);
            }
        }
        $r = $dom->createElementNS(self::W, 'w:r');
        if ($rPr) {
            $r->appendChild($rPr);
        }
        $t = $dom->createElementNS(self::W, 'w:t');
        $t->setAttribute('xml:space', 'preserve');
        $t->appendChild($dom->createTextNode($text));
        $r->appendChild($t);
        $p->appendChild($r);
    }

    private function processItemsTable(DOMDocument $dom, DOMXPath $xpath, array $content): void
    {
        $tables = $xpath->query('//w:tbl');
        $itemsTable = null;

        foreach ($tables as $tbl) {
            $tblText = '';
            foreach ($xpath->query('.//w:t', $tbl) as $t) {
                $tblText .= $t->textContent.' ';
            }
            if (preg_match('/(Komponen|Nama\s+Barang|Uraian\s+Barang|Nama\s+Produk|RINCIAN\s+PEKERJAAN)/i', $tblText)) {
                $itemsTable = $tbl;
                break;
            }
        }

        if (! $itemsTable) {
            return;
        }

        $rows = iterator_to_array($xpath->query('w:tr', $itemsTable));
        $rowCount = count($rows);
        if ($rowCount < 2) {
            return;
        }

        $templateRow = null;
        $templateRowIdx = -1;
        $totalRow = null;

        foreach ($rows as $rIdx => $r) {
            $rText = '';
            foreach ($xpath->query('.//w:t', $r) as $t) {
                $rText .= $t->textContent.' ';
            }
            $firstCell = $xpath->query('w:tc[1]', $r)->item(0);
            $firstCellText = $firstCell ? trim($firstCell->textContent) : '';
            if ($templateRow === null && ($firstCellText === '1' || preg_match('/^\s*1\s*$/', $firstCellText))) {
                $templateRow = $r;
                $templateRowIdx = $rIdx;
            }
            if (preg_match('/(JUMLAH|TOTAL|Jumlah Total)/i', $rText) && $templateRowIdx !== -1 && $rIdx > $templateRowIdx) {
                $totalRow = $r;
            }
        }

        if (! $templateRow) {
            $templateRow = $rows[1] ?? null;
            $templateRowIdx = 1;
        }

        if (! $templateRow) {
            return;
        }

        $items = $content['items'] ?? [];
        $key = $content['key'] ?? '';

        foreach ($items as $item) {
            $newRow = $templateRow->cloneNode(true);
            $cells = $xpath->query('w:tc', $newRow);
            $cellCount = $cells->length;

            $cellValues = $this->buildItemCellValues($key, $item, $cellCount);
            foreach ($cellValues as $cIdx => $cVal) {
                $cell = $cells->item($cIdx);
                if ($cell !== null) {
                    $this->setCellText($cell, (string) $cVal, $xpath, $dom);
                }
            }

            $itemsTable->insertBefore($newRow, $templateRow);
        }

        $itemsTable->removeChild($templateRow);

        if ($totalRow) {
            $this->updateTotalsRow($totalRow, $content, $xpath, $dom);
        }
    }

    private function buildItemCellValues(string $key, array $item, int $colCount): array
    {
        $no = (string) ($item['no'] ?? '');
        $name = $item['name'] ?? '';
        $specs = $item['specs'] ?? '';
        $nameWithSpecs = $specs !== '' ? $name."\n".$specs : $name;
        $qty = rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 2, ',', '.'), '0'), ',');
        $unit = $item['unit'] ?? 'Unit';
        $price = (float) ($item['price'] ?? 0);
        $total = (float) ($item['total'] ?? 0);
        $offer = (float) ($item['offer'] ?? $price);
        $vat = round($total * 0.11);
        $gross = $total + $vat;

        $priceMoney = 'Rp '.number_format($price, 0, ',', '.');
        $totalMoney = 'Rp '.number_format($total, 0, ',', '.');
        $offerMoney = 'Rp '.number_format($offer, 0, ',', '.');
        $vatMoney = 'Rp '.number_format($vat, 0, ',', '.');
        $grossMoney = 'Rp '.number_format($gross, 0, ',', '.');

        return match ($key) {
            'permintaan_penawaran', 'hps', 'perencanaan', 'penawaran', 'faktur' => [$no, $nameWithSpecs, $qty, $unit, $priceMoney, $totalMoney, $vatMoney, $grossMoney],
            'spph' => [$no, $nameWithSpecs, $qty, $unit],
            'klarifikasi_nego' => [$no, $nameWithSpecs, $qty.' '.$unit, $offerMoney, $priceMoney, 'Disetujui'],
            'spk' => [$no, $name, $qty, $unit, $specs ?: '-', $priceMoney, $totalMoney],
            'hasil_pemeriksaan' => [$no, $name, $qty.' '.$unit, 'Sesuai', 'Baik', 'v'],
            'bast_sekolah' => [$no, $nameWithSpecs, $qty, $unit, $qty, $unit, 'Baik'],
            'surat_jalan' => $colCount === 5 ? [$no, $nameWithSpecs, $qty, $unit, 'v'] : [$no, $nameWithSpecs, $qty.' '.$unit, 'v'],
            default => match ($colCount) {
                8 => [$no, $nameWithSpecs, $qty, $unit, $priceMoney, $totalMoney, $vatMoney, $grossMoney],
                7 => [$no, $name, $qty, $unit, $specs ?: '-', $priceMoney, $totalMoney],
                6 => [$no, $nameWithSpecs, $qty.' '.$unit, $offerMoney, $priceMoney, 'Sesuai'],
                4 => [$no, $nameWithSpecs, $qty, $unit],
                default => [$no, $nameWithSpecs, $qty],
            },
        };
    }

    private function setCellText(DOMElement $cell, string $text, DOMXPath $xpath, DOMDocument $dom): void
    {
        $p = $xpath->query('w:p[1]', $cell)->item(0);
        if (! $p) {
            $p = $dom->createElementNS(self::W, 'w:p');
            $cell->appendChild($p);
        }
        $rPr = $xpath->query('.//w:rPr', $cell)->item(0)?->cloneNode(true);

        foreach (iterator_to_array($p->childNodes) as $child) {
            if ($child->localName !== 'pPr') {
                $p->removeChild($child);
            }
        }

        $lines = explode("\n", $text);
        foreach ($lines as $idx => $line) {
            if ($idx > 0) {
                $brRun = $dom->createElementNS(self::W, 'w:r');
                $brRun->appendChild($dom->createElementNS(self::W, 'w:br'));
                $p->appendChild($brRun);
            }
            $run = $dom->createElementNS(self::W, 'w:r');
            if ($rPr) {
                $run->appendChild($rPr->cloneNode(true));
            }
            $t = $dom->createElementNS(self::W, 'w:t');
            $t->setAttribute('xml:space', 'preserve');
            $t->appendChild($dom->createTextNode($line));
            $run->appendChild($t);
            $p->appendChild($run);
        }
    }

    private function updateTotalsRow(DOMElement $totalRow, array $content, DOMXPath $xpath, DOMDocument $dom): void
    {
        $key = $content['key'] ?? '';
        $subtotal = $content['subtotal'] ?? 0;
        $vat = $content['vat'] ?? 0;
        $gross = $content['gross'] ?? 0;
        $withholding = $content['withholding'] ?? 0;
        $net = $content['net'] ?? ($gross - $withholding);
        $subtotalMoney = 'Rp '.number_format($subtotal, 0, ',', '.');
        $vatMoney = 'Rp '.number_format($vat, 0, ',', '.');
        $grossMoney = 'Rp '.number_format($gross, 0, ',', '.');
        $netMoney = 'Rp '.number_format($net, 0, ',', '.');

        $cells = iterator_to_array($xpath->query('w:tc', $totalRow));
        $cellCount = count($cells);

        if ($key === 'faktur' && $cellCount >= 3) {
            $this->setCellText($cells[$cellCount - 3], $subtotalMoney, $xpath, $dom);
            $this->setCellText($cells[$cellCount - 2], $vatMoney, $xpath, $dom);
            $this->setCellText($cells[$cellCount - 1], $withholding > 0 ? "{$grossMoney}\nNilai Setelah PPH: {$netMoney}" : $grossMoney, $xpath, $dom);

            return;
        }

        if ($key === 'klarifikasi_nego') {
            $vatRate = isset($content['vat_rate']) ? rtrim(rtrim(number_format((float) $content['vat_rate'], 2, ',', '.'), '0'), ',') : '0';
            if (isset($cells[0])) {
                $this->setCellText($cells[0], "TOTAL (PPN {$vatRate}%)", $xpath, $dom);
            }
            if (isset($cells[1])) {
                $this->setCellText($cells[1], $grossMoney, $xpath, $dom);
            }

            return;
        }

        if ($cellCount === 4 && isset($cells[0]) && preg_match('/JUMLAH|TOTAL/i', $cells[0]->textContent)) {
            $this->setCellText($cells[1], $subtotalMoney, $xpath, $dom);
            $this->setCellText($cells[2], $key === 'perencanaan' ? '' : $vatMoney, $xpath, $dom);
            $this->setCellText($cells[3], $key === 'perencanaan' ? $subtotalMoney : $grossMoney, $xpath, $dom);

            return;
        }

        foreach ($cells as $idx => $cell) {
            $cText = '';
            foreach ($xpath->query('.//w:t', $cell) as $t) {
                $cText .= $t->textContent;
            }
            if (preg_match('/(Rp|\.\.\.|JUMLAH|TOTAL)/i', $cText)) {
                if (! preg_match('/(JUMLAH|TOTAL)/i', $cText)) {
                    if ($idx === $cellCount - 3) {
                        $this->setCellText($cell, $subtotalMoney, $xpath, $dom);
                    } elseif ($idx === $cellCount - 2) {
                        $this->setCellText($cell, $vatMoney, $xpath, $dom);
                    } elseif ($idx === $cellCount - 1) {
                        $this->setCellText($cell, $grossMoney, $xpath, $dom);
                    }
                }
            }
        }
    }

    private function appendAdminNotes(DOMDocument $dom, DOMXPath $xpath, array $content): void
    {
        $notes = $content['keterangan'] ?? '';
        if ($notes === '') {
            return;
        }

        $bodyText = $xpath->query('//w:body')->item(0)?->textContent ?? '';
        if (str_contains($bodyText, $notes)) {
            return;
        }

        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return;
        }

        $p = $dom->createElementNS(self::W, 'w:p');
        $r = $dom->createElementNS(self::W, 'w:r');
        $rPr = $dom->createElementNS(self::W, 'w:rPr');
        $fonts = $dom->createElementNS(self::W, 'w:rFonts');
        $fonts->setAttributeNS(self::W, 'w:ascii', 'Arial');
        $rPr->appendChild($fonts);
        $sz = $dom->createElementNS(self::W, 'w:sz');
        $sz->setAttributeNS(self::W, 'w:val', '22');
        $rPr->appendChild($sz);
        $r->appendChild($rPr);
        $t = $dom->createElementNS(self::W, 'w:t');
        $t->setAttribute('xml:space', 'preserve');
        $t->appendChild($dom->createTextNode('Catatan: '.$notes));
        $r->appendChild($t);
        $p->appendChild($r);

        $tables = $xpath->query('//w:tbl', $body);
        $lastTable = $tables->length > 0 ? $tables->item($tables->length - 1) : null;
        if ($lastTable && $lastTable->parentNode) {
            $lastTable->parentNode->insertBefore($p, $lastTable);
        } else {
            $body->appendChild($p);
        }
    }

    private function processPhotoAttachments(DOMDocument $dom, DOMXPath $xpath, ZipArchive $zip, array $content): void
    {
        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return;
        }

        $imageIndex = 0;
        foreach ($content['blocks'] as $block) {
            if (($block['type'] ?? '') === 'image') {
                if (! empty($block['caption'])) {
                    $this->append($dom, $body, $this->paragraph($block['caption'], true));
                }
                $this->append($dom, $body, $this->image($zip, $block, ++$imageIndex));
            } elseif (($block['type'] ?? '') === 'paragraph' && ! empty($block['text'])) {
                $this->append($dom, $body, $this->paragraph($block['text'], false));
            }
        }
    }

    private function append(DOMDocument $dom, DOMElement $parent, string $xml): void
    {
        if ($xml === '') {
            return;
        }
        $fragment = new DOMDocument;
        if (! $fragment->loadXML('<w:body xmlns:w="'.self::W.'">'.$xml.'</w:body>', LIBXML_NONET)) {
            throw new RuntimeException('Data dokumen tidak dapat dirender.');
        }
        foreach ($fragment->documentElement->childNodes as $node) {
            $parent->appendChild($dom->importNode($node, true));
        }
    }

    private function institutionalHeader(ZipArchive $zip, array $header, string $master): string
    {
        $reference = new ZipArchive;
        if ($reference->open(config('spj.letterhead_template')) !== true) {
            throw new RuntimeException('Template acuan kop surat tidak dapat dibuka.');
        }
        try {
            $dom = new DOMDocument;
            if (! $dom->loadXML($reference->getFromName('word/document.xml'), LIBXML_NONET)) {
                throw new RuntimeException('Struktur kop surat acuan tidak valid.');
            }
        } finally {
            $reference->close();
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::W);
        $table = $xpath->query('/w:document/w:body/w:tbl[1]')->item(0);
        $postal = $xpath->query('/w:document/w:body/w:tbl[1]/following-sibling::w:p[1]')->item(0);
        $cells = $xpath->query('w:tr[1]/w:tc', $table);
        if ($cells->length !== 2 || ! $postal) {
            throw new RuntimeException('Template kop surat harus memiliki kolom logo, identitas, dan kode pos.');
        }
        $logo = $this->institutionalLogo($header, $master);
        $left = $cells->item(0);
        $logoParagraph = $xpath->query('w:p[1]/w:pPr', $left)->item(0)?->cloneNode(true);
        foreach (iterator_to_array($left->childNodes) as $child) {
            if ($child->localName !== 'tcPr') {
                $left->removeChild($child);
            }
        }
        if ($logo !== '') {
            $bytes = base64_decode(explode(',', $logo, 2)[1], true);
            $info = getimagesizefromstring($bytes);
            $this->append($dom, $left, $this->image($zip, ['mime' => $info['mime'], 'width' => $info[0], 'height' => $info[1], 'data' => base64_encode($bytes)], 0, 720000, 720000, empty($header['logo'])));
        } else {
            $left->appendChild($dom->createElementNS(self::W, 'w:p'));
        }
        $leftParagraph = $xpath->query('w:p[1]', $left)->item(0);
        if ($logoParagraph) {
            $leftParagraph->insertBefore($logoParagraph, $leftParagraph->firstChild);
        }

        $paragraphs = iterator_to_array($xpath->query('w:p', $cells->item(1)));
        $fields = ['government', 'department', 'name', 'address', 'contacts', 'online'];
        foreach ($paragraphs as $index => $paragraph) {
            $field = $fields[$index] ?? null;
            $value = $field ? ($header[$field] ?? '') : '';
            $this->fillHeaderParagraph($dom, $xpath, $paragraph, $value, $field === 'name' ? 40 : ($field === 'government' ? 24 : 22));
        }
        $this->fillHeaderParagraph($dom, $xpath, $postal, 'Kode Pos : '.($header['postal_code'] ?? ''), 18);
        foreach ([$table, $postal] as $element) {
            foreach ($xpath->query('namespace::*', $dom->documentElement) as $namespace) {
                if ($namespace->nodeName !== 'xmlns:xml') {
                    $element->setAttribute($namespace->nodeName, $namespace->nodeValue);
                }
            }
        }
        $row = $xpath->query('w:tr[1]', $table)->item(0);
        $properties = $xpath->query('w:trPr', $row)->item(0);
        if (! $properties) {
            $properties = $dom->createElementNS(self::W, 'w:trPr');
            $row->insertBefore($properties, $row->firstChild);
        }
        $properties->appendChild($dom->createElementNS(self::W, 'w:cantSplit'));

        return $dom->saveXML($table).$dom->saveXML($postal);
    }

    private function fillHeaderParagraph(DOMDocument $dom, DOMXPath $xpath, DOMElement $paragraph, string $text, int $size): void
    {
        $properties = $xpath->query('w:r/w:rPr', $paragraph)->item(0)?->cloneNode(true) ?? $dom->createElementNS(self::W, 'w:rPr');
        foreach (iterator_to_array($paragraph->childNodes) as $child) {
            if ($child->localName !== 'pPr') {
                $paragraph->removeChild($child);
            }
        }
        foreach (iterator_to_array($xpath->query('w:sz|w:szCs', $properties)) as $oldSize) {
            $properties->removeChild($oldSize);
        }
        $fontSize = $dom->createElementNS(self::W, 'w:sz');
        $fontSize->setAttributeNS(self::W, 'w:val', (string) $size);
        $properties->appendChild($fontSize);
        $run = $dom->createElementNS(self::W, 'w:r');
        $run->appendChild($properties);
        $value = $dom->createElementNS(self::W, 'w:t');
        $value->setAttribute('xml:space', 'preserve');
        $value->appendChild($dom->createTextNode($text));
        $run->appendChild($value);
        $paragraph->appendChild($run);
    }

    private function paragraph(string $text, bool $bold = false, string $align = 'left', int $size = 22, int $after = 120, int $line = 260, bool $keepNext = false): string
    {
        $runs = [];
        foreach (preg_split('/\R/u', $text) as $textLine) {
            $runs[] = '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:color w:val="000000"/><w:sz w:val="'.$size.'"/>'.($bold ? '<w:b/>' : '').'</w:rPr><w:t xml:space="preserve">'.$this->escape($textLine).'</w:t></w:r>';
        }

        return '<w:p><w:pPr><w:jc w:val="'.$align.'"/><w:spacing w:after="'.$after.'" w:line="'.$line.'" w:lineRule="auto"/>'.($bold || $keepNext ? '<w:keepNext/>' : '').'</w:pPr>'.implode('<w:r><w:br/></w:r>', $runs).'</w:p>';
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function image(ZipArchive $zip, array $block, int $index, int $maxWidth = 6000000, int $maxHeight = 4600000, bool $templateFrame = false): string
    {
        $extension = $block['mime'] === 'image/png' ? 'png' : 'jpg';
        $id = $index.substr(hash('sha256', $block['data']), 0, 12);
        $name = 'spj-photo-'.$id.'.'.$extension;
        $zip->addFromString('word/media/'.$name, base64_decode($block['data'], true));
        $rels = new DOMDocument;
        $rels->loadXML($zip->getFromName('word/_rels/document.xml.rels'), LIBXML_NONET);
        $relationship = $rels->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $relationship->setAttribute('Id', 'rIdSpjPhoto'.$id);
        $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
        $relationship->setAttribute('Target', 'media/'.$name);
        $rels->documentElement->appendChild($relationship);
        $zip->addFromString('word/_rels/document.xml.rels', $rels->saveXML());
        $types = new DOMDocument;
        $types->loadXML($zip->getFromName('[Content_Types].xml'), LIBXML_NONET);
        $type = $types->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Override');
        $type->setAttribute('PartName', '/word/media/'.$name);
        $type->setAttribute('ContentType', $block['mime']);
        $types->documentElement->appendChild($type);
        $zip->addFromString('[Content_Types].xml', $types->saveXML());
        $scale = min($maxWidth / $block['width'], $maxHeight / $block['height']);
        $width = $templateFrame ? $maxWidth : (int) ($block['width'] * $scale);
        $height = $templateFrame ? $maxHeight : (int) ($block['height'] * $scale);
        $drawingId = (string) hexdec(substr($id, 0, 7));

        return '<w:p><w:r><w:drawing><wp:inline xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"><wp:extent cx="'.$width.'" cy="'.$height.'"/><wp:docPr id="'.$drawingId.'" name="Dokumentasi pekerjaan"/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="'.$drawingId.'" name="Dokumentasi"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rIdSpjPhoto'.$id.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$width.'" cy="'.$height.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }
}
