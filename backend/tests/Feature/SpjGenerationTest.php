<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SpjDocument;
use App\Models\SpjProject;
use App\Models\User;
use App\Services\SpjDocumentContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use ZipArchive;

class SpjGenerationTest extends TestCase
{
    use RefreshDatabase;

    private string $outputRoot;

    private SpjProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputRoot = storage_path('framework/testing/spj-'.Str::uuid());
        config(['spj.storage_root' => $this->outputRoot]);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        $this->project = SpjProject::create([
            'project_id' => 'SPJ-QA', 'project_name' => 'Pengadaan Peralatan Workshop',
            'customer_name' => 'SMK Contoh Bogor', 'company_name' => 'CV ALPHARO ARTHA JAYA',
            'activity_name' => 'Pengadaan Sarana Workshop 2026', 'fiscal_year' => '2026',
            'funding_source' => 'BOS Kinerja & Afirmasi', 'budget_ceiling' => 1000000,
            'account_code' => '5.02.01', 'project_value' => 98550, 'invoice_paid' => 98550,
            'pic_name' => 'Siti Wulandari', 'pic_title' => 'Kepala Sekolah', 'pic_nip' => '198001012006042001',
            'city_signature' => 'Bogor', 'warehouse_status' => 'TERKIRIM', 'finance_status' => 'LUNAS',
            'warehouse_data' => ['driver_name' => 'Dedi Saputra', 'vehicle_plate' => 'F 1234 AA', 'delivery_date' => '2026-11-07'],
            'director_approval' => ['status' => 'Disetujui', 'name' => 'Budi Santoso', 'title' => 'Direktur'],
            'items' => $this->items(), 'metadata' => ['customer_address' => 'Jalan Pendidikan No. 10, Bogor'],
        ]);
        foreach (array_keys(SpjDocumentContent::TITLES) as $index => $key) {
            SpjDocument::create(['project_id' => 'SPJ-QA', 'doc_key' => $key, 'doc_order' => $index + 1,
                'doc_name' => SpjDocumentContent::TITLES[$key], 'status' => 'DRAFT', 'doc_number' => 'QA/'.$key.'/2026', 'doc_date' => '07/11/2026']);
        }
    }

    protected function tearDown(): void
    {
        // Only remove the unique directory this test owns, never project uploads.
        if (is_dir($this->outputRoot) && str_starts_with(realpath($this->outputRoot), realpath(storage_path('framework/testing')).DIRECTORY_SEPARATOR.'spj-')) {
            File::deleteDirectory($this->outputRoot);
        }
        parent::tearDown();
    }

    private function items(): array
    {
        return [
            ['item_name' => 'Gelas & Tutup <Kaca>', 'quantity' => 2, 'unit' => 'Buah', 'price' => 15000, 'price_nego' => 15000],
            ['item_name' => 'Piring Makan', 'quantity' => 3, 'unit' => 'Buah', 'price' => 20000, 'price_nego' => 20000],
        ];
    }

    private function url(string $key, string $action = 'download'): string
    {
        return '/api/spj/projects/SPJ-QA/documents/'.$key.'/'.$action;
    }

    private function doc(string $key): SpjDocument
    {
        return $this->project->documents()->where('doc_key', $key)->firstOrFail();
    }

    private function text(string $path): string
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET));
        $zip->close();

        return $dom->textContent;
    }

    private function assertReferenceHeader(string $path, ?string $expectedLogo = null): void
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET));
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $table = '/w:document/w:body/w:tbl[1]';
        $value = fn ($query) => $xpath->evaluate('string('.$query.')');
        $this->assertSame(2, $xpath->query($table.'/w:tr[1]/w:tc')->length);
        $this->assertSame('9640', $value($table.'/w:tblPr/w:tblW/@w:w'));
        $this->assertSame('fixed', $value($table.'/w:tblPr/w:tblLayout/@w:type'));
        $this->assertSame('1300', $value($table.'/w:tblGrid/w:gridCol[1]/@w:w'));
        $this->assertSame('8340', $value($table.'/w:tblGrid/w:gridCol[2]/@w:w'));
        $this->assertSame('720000', $value($table.'//wp:extent/@cx'));
        $this->assertSame('720000', $value($table.'//wp:extent/@cy'));
        $this->assertSame('40', $value($table.'/w:tr[1]/w:tc[2]/w:p[3]/w:r/w:rPr/w:sz/@w:val'));
        $this->assertSame('Arial', $value($table.'/w:tr[1]/w:tc[2]/w:p[3]/w:r/w:rPr/w:rFonts/@w:ascii'));
        $this->assertSame('nil', $value($table.'/w:tblPr/w:tblBorders/w:bottom/@w:val'));
        $postal = $table.'/following-sibling::w:p[1]';
        $this->assertStringStartsWith('Kode Pos : ', $value($postal.'/w:r/w:t'));
        $this->assertSame('right', $value($postal.'/w:pPr/w:jc/@w:val'));
        $this->assertSame('18', $value($postal.'/w:pPr/w:pBdr/w:bottom/@w:sz'));
        $this->assertSame('18', $value($postal.'/w:r/w:rPr/w:sz/@w:val'));
        $this->assertStringNotContainsString("\u{FFFD}", $xpath->query($table)->item(0)->textContent);
        $blip = $xpath->query($table.'//a:blip')->item(0);
        $this->assertNotNull($blip);
        $id = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
        $rels = new \DOMDocument;
        $rels->loadXML($zip->getFromName('word/_rels/document.xml.rels'));
        $target = null;
        foreach ($rels->documentElement->childNodes as $relation) {
            if ($relation instanceof \DOMElement && $relation->getAttribute('Id') === $id) {
                $target = 'word/'.$relation->getAttribute('Target');
            }
        }
        $this->assertNotNull($target);
        $image = $zip->getFromName($target);
        $this->assertNotFalse($image);
        if ($expectedLogo !== null) {
            $this->assertSame(hash('sha256', $expectedLogo), hash('sha256', $image));
        }
        $zip->close();
    }

    public function test_all_fifteen_documents_download_filled_word_and_pdf_from_saved_input_without_manual_generation(): void
    {
        foreach (SpjDocumentContent::TITLES as $key => $title) {
            $form = ['doc_number' => 'INPUT/'.$key.'/2026', 'doc_date' => '07/11/2026', 'items' => $this->items(),
                'keterangan' => 'Catatan admin khusus '.$key, 'waktu_pelaksanaan' => '30 Hari Kalender',
                'hasil_pemeriksaan' => 'Sesuai spesifikasi', 'nego_result' => 'Sepakat',
                'jumlah' => 98550, 'metode' => 'Transfer Bank', 'lokasi' => 'Workshop Sekolah',
                'photos' => [['title' => 'Serah terima peralatan', 'desc' => 'Diterima lengkap oleh sekolah']]];
            $this->putJson('/api/spj/projects/SPJ-QA/documents/'.$key, ['form_data' => $form])->assertOk();
            $word = $this->get($this->url($key))->assertOk();
            $word->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $text = $this->text($word->baseResponse->getFile()->getPathname());
            foreach ([$title, 'INPUT/'.$key.'/2026', '07/11/2026', 'SMK Contoh Bogor', 'Catatan admin khusus '.$key, 'Siti Wulandari'] as $value) {
                $this->assertStringContainsString($value, $text, $key);
            }
            if (! in_array($key, ['cover_project', 'kuitansi', 'bukti_foto'])) {
                $this->assertStringContainsString('Gelas & Tutup <Kaca>', $text, $key);
                $this->assertStringContainsString('Piring Makan', $text, $key);
            }
            if ($key === 'perencanaan') {
                $this->assertStringContainsString('Rp 90.000', $text);
                $this->assertStringContainsString('30 Hari Kalender', $text);
                $this->assertStringNotContainsString('HARGA PERKIRAAN SENDIRI', $text);
            }
            if ($key === 'hps') {
                $this->assertStringContainsString('Rp 99.900', $text);
            }
            if ($key === 'faktur') {
                $this->assertStringContainsString('Rp 98.550', $text);
            }
            if ($key === 'kuitansi') {
                $this->assertStringContainsString('Sembilan Puluh Delapan Ribu Lima Ratus Lima Puluh Rupiah', $text);
            }
            if ($key === 'bukti_foto') {
                $this->assertStringContainsString('Serah terima peralatan', $text);
            }
            $pdf = $this->get($this->url($key).'?format=pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $pdfPath = $pdf->baseResponse->getFile()->getPathname();
            $this->assertStringStartsWith('%PDF-', file_get_contents($pdfPath));
            $this->assertGreaterThan(5000, filesize($pdfPath));
            if ($export = getenv('SPJ_QA_EXPORT')) {
                File::ensureDirectoryExists($export);
                File::copy($word->baseResponse->getFile()->getPathname(), $export.'/'.$key.'.docx');
                File::copy($pdfPath, $export.'/'.$key.'.pdf');
            }
        }
    }

    public function test_download_reuses_identical_content_and_regenerates_when_form_or_project_changes(): void
    {
        $this->get($this->url('perencanaan'))->assertOk();
        $first = $this->doc('perencanaan');
        $this->get($this->url('perencanaan'))->assertOk();
        $this->assertSame($first->version, $this->doc('perencanaan')->version);
        $this->putJson('/api/spj/projects/SPJ-QA/documents/perencanaan', ['form_data' => ['doc_date' => $first->doc_date]])->assertOk();
        $this->get($this->url('perencanaan'))->assertOk();
        $this->assertSame('FINAL', $this->doc('perencanaan')->status);
        $this->project->update(['customer_name' => 'Sekolah Baru']);
        $this->get($this->url('perencanaan'))->assertOk();
        $this->assertNotSame($first->file_path, $this->doc('perencanaan')->file_path);
        $this->assertStringContainsString('Sekolah Baru', $this->text($this->doc('perencanaan')->full_path));
        $this->putJson('/api/spj/projects/SPJ-QA/documents/perencanaan', ['form_data' => ['items' => [['item_name' => 'Kursi Lipat', 'quantity' => 4, 'price' => 25000, 'unit' => 'Buah']]]])->assertOk()->assertJsonPath('data.status', 'DRAFT');
        $this->get($this->url('perencanaan').'?format=pdf')->assertOk();
        $text = $this->text($this->doc('perencanaan')->full_path);
        $this->assertStringContainsString('Kursi Lipat', $text);
        $this->assertStringContainsString('Rp 100.000', $text);
        $this->assertStringNotContainsString('Piring Makan', $text);
        $this->assertNull($this->doc('hps')->file_path);
    }

    public function test_negotiated_price_and_zero_tax_match_admin_input_instead_of_stale_values(): void
    {
        $items = [['item_name' => 'Meja', 'quantity' => 2.5, 'unit' => 'M', 'original_price' => 20000, 'price_nego' => 19000, 'nego_price' => 18000]];
        $this->postJson($this->url('klarifikasi_nego', 'generate'), ['form_data' => ['items' => $items, 'ppn_rate' => 0]])->assertOk();
        $text = $this->text($this->doc('klarifikasi_nego')->full_path);
        $this->assertStringContainsString('Rp 18.000', $text);
        $this->assertStringContainsString('Rp 45.000', $text);
        $this->assertStringContainsString('2,5', $text);
        $this->assertStringContainsString('PPN 0%', $text);
        $this->assertStringNotContainsString('Rp 19.000', $text);
    }

    public function test_missing_or_invalid_input_and_unmet_business_requirements_never_return_blank_masters(): void
    {
        foreach ([['items' => []], ['items' => 'invalid'], ['items' => ['invalid']], ['items' => [['item_name' => 'Meja', 'quantity' => -1]]], ['doc_date' => '31/02/2026'], ['ppn_rate' => -1]] as $form) {
            $this->postJson($this->url('perencanaan', 'generate'), ['form_data' => $form])->assertUnprocessable();
        }
        $this->assertNull($this->doc('perencanaan')->file_path);
        $this->project->update(['director_approval' => ['status' => 'Menunggu']]);
        $this->getJson($this->url('penawaran'))->assertUnprocessable()->assertJsonValidationErrors('document');
        $this->assertNull($this->doc('penawaran')->file_path);
    }

    public function test_original_photo_survives_generation_and_is_embedded_in_both_formats(): void
    {
        $upload = UploadedFile::fake()->image('serah-terima.png', 240, 160);
        $png = file_get_contents($upload->getPathname());
        $this->postJson($this->url('bukti_foto', 'upload-external'), ['file' => $upload])->assertOk();
        $source = $this->doc('bukti_foto')->source_file_path;
        $this->get($this->url('bukti_foto').'?format=pdf')->assertOk();
        $this->assertSame($source, $this->doc('bukti_foto')->source_file_path);
        $original = $this->get($this->url('bukti_foto').'?format=original')->assertOk();
        $this->assertSame($png, file_get_contents($original->baseResponse->getFile()->getPathname()));
        $zip = new ZipArchive;
        $zip->open($this->doc('bukti_foto')->full_path);
        $photoEntries = array_filter(range(0, $zip->numFiles - 1), fn ($index) => str_starts_with($zip->getNameIndex($index), 'word/media/spj-photo-'));
        $this->assertCount(1, $photoEntries);
        $this->assertSame($png, $zip->getFromIndex(array_values($photoEntries)[0]));
        $zip->close();
        $pdf = preg_replace('/\.docx$/', '.pdf', $this->doc('bukti_foto')->full_path);
        $this->assertStringContainsString('/Subtype /Image', file_get_contents($pdf));
    }

    public function test_multiple_form_photos_are_saved_and_embedded_with_unique_word_relationships(): void
    {
        $upload = UploadedFile::fake()->image('foto.png', 600, 400);
        $dataUrl = 'data:image/png;base64,'.base64_encode(file_get_contents($upload->getPathname()));
        $this->postJson($this->url('bukti_foto', 'generate'), ['format' => 'pdf', 'form_data' => ['photos' => [
            ['title' => 'Foto penerimaan', 'desc' => 'Barang lengkap', 'data_url' => $dataUrl],
            ['title' => 'Foto pemeriksaan', 'desc' => 'Sesuai spesifikasi', 'data_url' => $dataUrl],
        ]]])->assertOk();
        $doc = $this->doc('bukti_foto');
        $this->assertSame($dataUrl, $doc->form_data['photos'][0]['data_url']);
        $zip = new ZipArchive;
        $zip->open($doc->full_path);
        $images = array_filter(range(0, $zip->numFiles - 1), fn ($index) => str_starts_with($zip->getNameIndex($index), 'word/media/spj-photo-'));
        $this->assertCount(2, $images);
        $rels = new \DOMDocument;
        $rels->loadXML($zip->getFromName('word/_rels/document.xml.rels'));
        $ids = [];
        foreach ($rels->documentElement->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                $ids[] = $node->getAttribute('Id');
            }
        }
        $this->assertSameSize(array_unique($ids), $ids);
        $zip->close();
        $this->postJson($this->url('bukti_foto', 'generate'), ['form_data' => ['photos' => [['data_url' => 'https://invalid.example/foto.png']]]])->assertUnprocessable();
        if ($export = getenv('SPJ_QA_EXPORT')) {
            File::copy($doc->full_path, $export.'/photos.docx');
            File::copy(preg_replace('/\.docx$/', '.pdf', $doc->full_path), $export.'/photos.pdf');
        }
    }

    public function test_shipping_and_invoice_preserve_item_names_units_and_explicit_invoice_prices(): void
    {
        $this->postJson($this->url('surat_jalan', 'generate'), ['form_data' => ['items' => [['code' => 'Piring Makan', 'qty_sent' => 3]]]])->assertOk();
        $shipping = $this->text($this->doc('surat_jalan')->full_path);
        $this->assertStringContainsString('Piring Makan', $shipping);
        $this->assertStringContainsString('Buah', $shipping);
        $this->postJson($this->url('faktur', 'generate'), ['form_data' => ['items' => [['code' => 'Piring Makan', 'quantity' => 3, 'price' => 18000, 'price_nego' => 20000]], 'ppn_rate' => 0, 'pph_rate' => 0]])->assertOk();
        $invoice = $this->text($this->doc('faktur')->full_path);
        $this->assertStringContainsString('Rp 54.000', $invoice);
        $this->assertStringNotContainsString('Rp 60.000', $invoice);
    }

    public function test_many_long_item_names_generate_a_valid_multipage_pdf_and_complete_word_table(): void
    {
        $items = array_map(fn ($i) => ['item_name' => 'Komponen '.$i.' untuk kelengkapan sarana laboratorium dan pemeliharaan peralatan sekolah', 'quantity' => 2, 'price' => 15000, 'unit' => 'Buah'], range(1, 45));
        $this->postJson($this->url('perencanaan', 'generate'), ['format' => 'pdf', 'form_data' => ['items' => $items]])->assertOk();
        $doc = $this->doc('perencanaan');
        $text = $this->text($doc->full_path);
        foreach (range(1, 45) as $i) {
            $this->assertStringContainsString('Komponen '.$i.' untuk', $text);
        }
        $this->assertStringContainsString('Rp 1.350.000', $text);
        $pdf = file_get_contents(preg_replace('/\.docx$/', '.pdf', $doc->full_path));
        preg_match_all('/\/Count\s+(\d+)/', $pdf, $counts);
        $this->assertGreaterThan(1, max(array_map('intval', $counts[1])));
        if ($export = getenv('SPJ_QA_EXPORT')) {
            File::copy($doc->full_path, $export.'/many-items.docx');
            File::copy(preg_replace('/\.docx$/', '.pdf', $doc->full_path), $export.'/many-items.pdf');
        }
    }

    public function test_school_letterhead_uses_project_identity_in_every_school_document_and_changes_invalidate_files(): void
    {
        $logo = UploadedFile::fake()->image('logo-sekolah.png', 100, 100);
        $profile = ['name' => 'Sekolah A', 'orgFullName' => 'SMK Negeri 1 Bogor', 'province' => 'JAWA BARAT',
            'letterheadAddress' => 'Jalan Pendidikan No. 7, Kota Bogor', 'orgPhone' => '0251-123456', 'fax' => '0251-654321',
            'website' => 'https://sekolah-a.example.test', 'email' => 'admin@sekolah-a.example.test', 'postalCode' => '16111',
            'logoDataUrl' => 'data:image/png;base64,'.base64_encode(file_get_contents($logo->getPathname()))];
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => $profile]])->assertOk();
        $schoolKeys = ['cover_project', 'perencanaan', 'hps', 'spph', 'klarifikasi_nego', 'permintaan_penawaran', 'spk', 'hasil_pemeriksaan', 'bast_sekolah'];
        foreach ($schoolKeys as $key) {
            $word = $this->get($this->url($key))->assertOk();
            $text = $this->text($word->baseResponse->getFile()->getPathname());
            $this->assertReferenceHeader($word->baseResponse->getFile()->getPathname(), file_get_contents($logo->getPathname()));
            foreach (['PEMERINTAH PROVINSI JAWA BARAT', 'DINAS PENDIDIKAN', 'SMK NEGERI 1 BOGOR', $profile['letterheadAddress'], '0251-123456', '0251-654321', $profile['website'], $profile['email'], 'Kode Pos : 16111'] as $value) {
                $this->assertStringContainsString($value, $text, $key);
            }
            $this->assertStringNotContainsString('IBUKOTA JAKARTA', $text);
            $this->get($this->url($key).'?format=pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
            if ($export = getenv('SPJ_QA_EXPORT')) {
                File::ensureDirectoryExists($export);
                File::copy($this->doc($key)->full_path, $export.'/school-a-'.$key.'.docx');
                File::copy(preg_replace('/\.docx$/', '.pdf', $this->doc($key)->full_path), $export.'/school-a-'.$key.'.pdf');
            }
        }
        $first = $this->doc('perencanaan')->file_path;
        $profile['orgFullName'] = 'SMP Negeri 2 Bandung';
        $profile['letterheadAddress'] = 'Jalan Sekolah No. 20, Bandung';
        $profile['email'] = 'admin@sekolah-b.example.test';
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => $profile]])->assertOk();
        $this->get($this->url('perencanaan').'?format=pdf')->assertOk();
        $this->assertNotSame($first, $this->doc('perencanaan')->file_path);
        $text = $this->text($this->doc('perencanaan')->full_path);
        $this->assertStringContainsString('SMP NEGERI 2 BANDUNG', $text);
        $this->assertStringNotContainsString('SMK NEGERI 1 BOGOR', $text);
        $this->assertStringNotContainsString('admin@sekolah-a.example.test', $text);
    }

    public function test_default_reference_logo_is_used_for_every_province_without_inventing_contact_data(): void
    {
        $this->get($this->url('perencanaan'))->assertOk();
        $zip = new ZipArchive;
        $zip->open($this->doc('perencanaan')->full_path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('rIdSpjPhoto0', $xml);
        $reference = new ZipArchive;
        $reference->open(config('spj.letterhead_template'));
        $logo = $reference->getFromName('word/media/image1.png');
        $reference->close();
        $this->assertReferenceHeader($this->doc('perencanaan')->full_path, $logo);
        $this->assertStringNotContainsString('IBUKOTA JAKARTA', $xml);
        $this->assertStringNotContainsString('Telepon:', $xml);
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => ['province' => 'JAWA BARAT']]])->assertOk();
        $this->get($this->url('perencanaan'))->assertOk();
        $zip->open($this->doc('perencanaan')->full_path);
        $this->assertStringContainsString('rIdSpjPhoto0', $zip->getFromName('word/document.xml'));
        $zip->close();
        $this->assertReferenceHeader($this->doc('perencanaan')->full_path, $logo);
        $this->get($this->url('perencanaan').'?format=pdf')->assertOk();
        if ($export = getenv('SPJ_QA_EXPORT')) {
            File::ensureDirectoryExists($export);
            File::copy($this->doc('perencanaan')->full_path, $export.'/default-jabar.docx');
            File::copy(preg_replace('/\.docx$/', '.pdf', $this->doc('perencanaan')->full_path), $export.'/default-jabar.pdf');
        }
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => ['province' => 'DKI JAKARTA']]])->assertOk();
        $this->get($this->url('perencanaan'))->assertOk();
        $zip->open($this->doc('perencanaan')->full_path);
        $this->assertStringContainsString('rIdSpjPhoto0', $zip->getFromName('word/document.xml'));
        $zip->close();
        foreach (['data:image/svg+xml;base64,'.base64_encode('<svg/>'), 'https://example.test/logo.png', 'data:image/png;base64,invalid'] as $logo) {
            $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => ['logoDataUrl' => $logo]]])->assertUnprocessable()->assertJsonValidationErrors('metadata.customer_profile.logoDataUrl');
        }
    }

    public function test_school_profile_is_persisted_from_order_and_visible_to_administration_without_browser_client_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        Product::create(['sku' => 'KOP-ITEM', 'name' => 'Gelas', 'category' => 'Peralatan', 'unit' => 'Buah', 'stock' => 10]);
        $details = ['projectId' => 'KOP-NEW', 'projectName' => 'Pengadaan Sekolah B', 'clientName' => 'Sekolah B', 'clientId' => 22,
            'customerProfile' => ['code' => 'CS-B', 'name' => 'Sekolah B', 'orgFullName' => 'SMA Negeri Contoh B', 'province' => 'JAWA TENGAH', 'letterheadAddress' => 'Jalan Sekolah B', 'orgPhone' => '024-123456']];
        $payload = ['version' => 0, 'details' => $details, 'items' => [['sku' => 'KOP-ITEM', 'name' => 'Gelas', 'unit' => 'Buah', 'qty' => 2, 'price' => 15000]]];
        $saved = $this->putJson('/api/orders/ORD-KOP-NEW', $payload)->assertOk()->assertJsonPath('data.customerProfile.orgFullName', 'SMA Negeri Contoh B')->json('data');
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        $this->getJson('/api/spj/projects/KOP-NEW/documents')->assertOk()->assertJsonPath('project.customer_profile.orgFullName', 'SMA Negeri Contoh B')->assertJsonCount(15, 'data');
        $project = SpjProject::where('project_id', 'KOP-NEW')->firstOrFail();
        $this->assertSame('PENDING', $project->warehouse_status);
        $this->assertSame('MENUNGGU_PEMBAYARAN', $project->finance_status);
        $word = $this->get('/api/spj/projects/KOP-NEW/documents/perencanaan/download')->assertOk();
        $text = $this->text($word->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('PEMERINTAH PROVINSI JAWA TENGAH', $text);
        $this->assertStringContainsString('SMA NEGERI CONTOH B', $text);
        $this->assertStringNotContainsString('SMK Contoh Bogor', $text);
        $this->putJson('/api/spj/projects/KOP-NEW', ['metadata' => ['customer_profile' => ['orgFullName' => 'SMA Negeri Contoh B', 'province' => 'JAWA TENGAH', 'orgPhone' => '024-999999']]])->assertOk();
        $this->getJson('/api/spj/projects/KOP-NEW/documents')->assertJsonPath('project.customer_profile.orgPhone', '024-999999');
        Sanctum::actingAs(User::factory()->create(['role' => 'MARKETING']));
        $payload['version'] = $saved['version'];
        $this->putJson('/api/orders/ORD-KOP-NEW', $payload)->assertOk();
        $otherPayload = $payload;
        $otherPayload['version'] = 0;
        $otherPayload['details']['clientName'] = 'Sekolah C';
        $otherPayload['details']['customerProfile'] = ['code' => 'CS-C', 'name' => 'Sekolah C', 'orgFullName' => 'SMA Negeri Contoh C'];
        $this->putJson('/api/orders/ORD-KOP-OTHER', $otherPayload)->assertConflict();
        $this->assertDatabaseMissing('sales_orders', ['order_number' => 'ORD-KOP-OTHER']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRASI']));
        $this->getJson('/api/spj/projects/KOP-NEW/documents')->assertJsonPath('project.customer_profile.orgPhone', '024-999999');
        $project->update(['metadata' => []]);
        $this->getJson('/api/spj/projects/KOP-NEW/documents')->assertJsonPath('project.customer_profile.orgPhone', '024-123456');
        $this->putJson('/api/spj/projects/KOP-NEW', ['customer_name' => 'Sekolah C'])->assertOk();
        $this->getJson('/api/spj/projects/KOP-NEW/documents')->assertJsonPath('project.customer_profile', []);
        $this->getJson('/api/spj/projects/SPJ-QA/documents')->assertJsonPath('project.customer_profile', []);
    }

    public function test_reference_header_keeps_two_columns_for_empty_contacts_and_wraps_long_school_identity(): void
    {
        $name = 'SEKOLAH MENENGAH KEJURUAN NEGERI PUSAT PENGEMBANGAN TEKNOLOGI DAN KETERAMPILAN KOTA BOGOR';
        $address = 'Jalan Pendidikan dan Pengembangan Keterampilan No. 127, Kelurahan Contoh, Kecamatan Pendidikan, Kota Bogor, Jawa Barat';
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => ['orgFullName' => $name, 'province' => 'JAWA BARAT', 'letterheadAddress' => $address]]])->assertOk();
        $this->get($this->url('perencanaan').'?format=pdf')->assertOk();
        $doc = $this->doc('perencanaan');
        $this->assertReferenceHeader($doc->full_path);
        $text = $this->text($doc->full_path);
        $this->assertStringContainsString($name, $text);
        $this->assertStringContainsString($address, $text);
        $this->assertStringNotContainsString('Telepon:', $text);
        if ($export = getenv('SPJ_QA_EXPORT')) {
            File::ensureDirectoryExists($export);
            File::copy($doc->full_path, $export.'/long-header.docx');
            File::copy(preg_replace('/\.docx$/', '.pdf', $doc->full_path), $export.'/long-header.pdf');
        }
        $this->putJson('/api/spj/projects/SPJ-QA', ['metadata' => ['customer_profile' => []]])->assertOk();
        $this->get($this->url('perencanaan'))->assertOk();
        $this->assertReferenceHeader($this->doc('perencanaan')->full_path);
    }
}
