<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Database\Seeders\SpjProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpjIntegratedTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTemplateSeeder::class);
        $this->seed(SpjProjectSeeder::class);

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test fetching list of SPJ projects
     */
    public function test_can_fetch_spj_projects(): void
    {
        $response = $this->getJson('/api/spj/projects');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'project_name',
                        'customer_name',
                    ],
                ],
            ]);
    }

    /**
     * Test fetching exactly 15 documents in fixed vertical sequence
     */
    public function test_project_has_exactly_15_ordered_documents(): void
    {
        $response = $this->getJson('/api/spj/projects/PR00001/documents');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $docs = $response->json('data');
        $this->assertCount(15, $docs);

        $expectedOrder = [
            'cover_project',
            'perencanaan',
            'hps',
            'spph',
            'penawaran',
            'klarifikasi_nego',
            'permintaan_penawaran',
            'spk',
            'hasil_pemeriksaan',
            'bast_sekolah',
            'bast_company',
            'surat_jalan',
            'faktur',
            'kuitansi',
            'bukti_foto',
        ];

        foreach ($expectedOrder as $index => $expectedKey) {
            $this->assertEquals($expectedKey, $docs[$index]['doc_key']);
            $this->assertEquals($index + 1, $docs[$index]['doc_order']);
        }
    }

    /**
     * Test updating project data across 4 quadrants
     */
    public function test_can_update_project_data(): void
    {
        $response = $this->putJson('/api/spj/projects/PR00001', [
            'project_name' => 'pengadaan komputer sekolah update',
            'budget_ceiling' => 120000000,
            'funding_source' => 'BOP',
            'pic_name' => 'Dr. Endang M.Pd',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.project_name', 'pengadaan komputer sekolah update')
            ->assertJsonPath('data.pic_name', 'Dr. Endang M.Pd');
    }

    /**
     * Test generating a document (Penawaran)
     */
    public function test_can_generate_document(): void
    {
        $response = $this->postJson('/api/spj/projects/PR00001/documents/penawaran/generate', [
            'form_data' => [
                'subject' => 'Surat Penawaran Pengadaan Komputer Uji',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.doc_key', 'penawaran');

        $this->assertNotEmpty($response->json('download_url'));
    }

    /**
     * Test downloading generated document
     */
    public function test_can_download_document(): void
    {
        // Generate first to ensure file exists in storage
        $this->postJson('/api/spj/projects/PR00001/documents/penawaran/generate', []);

        $response = $this->get('/api/spj/projects/PR00001/documents/penawaran/download');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'wordprocessingml.document'));
    }

    /**
     * Test bulk generating multiple documents
     */
    public function test_can_bulk_generate_documents(): void
    {
        $response = $this->postJson('/api/spj/projects/PR00001/bulk-generate', [
            'doc_keys' => ['hps', 'spph', 'penawaran'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success_count', 3);
    }

    /**
     * Test uploading external document
     */
    public function test_can_upload_external_document(): void
    {
        Storage::fake('local');
        $fakeFile = UploadedFile::fake()->create('bukti_foto_lapangan.jpg', 500, 'image/jpeg');

        $response = $this->postJson('/api/spj/projects/PR00001/documents/bukti_foto/upload-external', [
            'file' => $fakeFile,
            'doc_number' => 'DOK-FOTO-001',
            'doc_date' => '2026-10-09',
            'notes' => 'Dokumentasi serah terima di lab komputer sekolah',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'DOKUMEN_EKSTERNAL');
    }
}
