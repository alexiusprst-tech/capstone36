<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\User;
use Database\Seeders\DocumentTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTemplateSeeder::class);
    }

    public function test_can_list_document_templates(): void
    {
        $response = $this->getJson('/api/document-templates');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code', 'category', 'type', 'available_variables'],
                ],
            ]);
    }

    public function test_can_download_blank_template(): void
    {
        $template = DocumentTemplate::first();

        $response = $this->get('/api/document-templates/'.$template->id.'/download-blank');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'wordprocessingml.document'));
    }

    public function test_can_generate_order_document(): void
    {
        $user = User::factory()->create();
        $template = DocumentTemplate::where('code', 'BAST_SEKOLAH')->first();

        $payload = [
            'template_id' => $template->id,
            'order_number' => 'ORD-TEST-001',
            'doc_number' => '001/BAST/AAJ/2026',
            'variables' => [
                'nama_sekolah' => 'SMK Negeri 1 Jakarta',
                'nomor_surat' => '001/BAST/AAJ/2026',
                'tanggal' => '12 Oktober 2026',
                'nilai_kontrak' => 'Rp 50.000.000',
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/documents/generate', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('order_documents', [
            'order_number' => 'ORD-TEST-001',
            'doc_number' => '001/BAST/AAJ/2026',
        ]);
    }
}
