<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SpjDocument;
use App\Models\SpjProject;
use App\Services\SpjLetterhead;
use App\Services\SpjService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpjController extends Controller
{
    public function __construct(
        protected SpjService $spjService
    ) {}

    /**
     * Ambil daftar proyek SPJ
     */
    public function getProjects(Request $request): JsonResponse
    {
        $projects = SpjProject::with(['documents'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar proyek SPJ berhasil diambil.',
            'data' => $projects,
        ]);
    }

    /**
     * Ambil detail proyek SPJ tunggal
     */
    public function getProject(string $id): JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $project->load('documents');

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Perbarui data proyek SPJ (Data Project, Pengadaan, Nilai/Jadwal, Penanggung Jawab)
     */
    public function updateProject(Request $request, string $id): JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        if (! empty($project->metadata['source_order_number']) && SalesOrder::where('order_number', $project->metadata['source_order_number'])->exists()) {
            foreach (['project_value', 'invoice_value', 'invoice_paid', 'finance_status', 'finance_data', 'director_approval', 'warehouse_status', 'warehouse_data', 'items'] as $field) {
                abort_if($request->exists($field) && $request->input($field) != $project->getAttribute($field), 403, 'Data transaksi berasal dari pesanan. Status pembayaran, approval, stok, dan nilainya tidak dapat diubah lewat formulir SPJ.');
            }
        }

        $validated = $request->validate([
            'metadata' => ['sometimes', 'array:customer_profile,customer_address,company_address'],
            'metadata.customer_address' => ['nullable', 'string', 'max:2000'],
            'metadata.company_address' => ['nullable', 'string', 'max:2000'],
            ...SpjLetterhead::rules('metadata.customer_profile'),
            'project_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string'],
            'customer_code' => ['nullable', 'string'],
            'marketing_name' => ['nullable', 'string'],
            'company_name' => ['nullable', 'string'],
            'activity_name' => ['nullable', 'string'],
            'activity_detail' => ['nullable', 'string'],
            'account_code' => ['nullable', 'string'],
            'fiscal_year' => ['nullable', 'string'],
            'funding_source' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'budget_ceiling' => ['nullable', 'numeric'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'project_value' => ['nullable', 'numeric'],
            'invoice_value' => ['nullable', 'numeric'],
            'invoice_paid' => ['nullable', 'numeric'],
            'pic_name' => ['nullable', 'string'],
            'pic_title' => ['nullable', 'string'],
            'pic_nip' => ['nullable', 'string'],
            'city_signature' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'warehouse_data' => ['nullable', 'array'],
            'warehouse_status' => ['nullable', 'string'],
            'finance_data' => ['nullable', 'array'],
            'finance_status' => ['nullable', 'string'],
            'director_approval' => ['nullable', 'array'],
        ]);

        $metadata = $project->metadata ?? [];
        if (isset($validated['customer_name']) && $validated['customer_name'] !== $project->customer_name && ! array_key_exists('customer_profile', $validated['metadata'] ?? [])) {
            $metadata['customer_profile'] = [];
            unset($metadata['customer_address']);
        }
        if (array_key_exists('metadata', $validated)) {
            $metadata = array_replace($metadata, $validated['metadata']);
        }
        if (array_key_exists('customer_profile', $validated['metadata'] ?? [])) {
            $metadata['customer_profile_source'] = 'project';
        }
        $validated['metadata'] = $metadata;
        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Data proyek {$project->project_id} berhasil diperbarui.",
            'data' => $project->fresh()->load('documents'),
        ]);
    }

    /**
     * Ambil daftar 15 dokumen SPJ untuk proyek terpilih lengkap dengan status kelengkapan data
     */
    public function getDocuments(string $id): JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $project->setAttribute('customer_profile', app(SpjLetterhead::class)->profile($project));
        $documents = $project->documents()->orderBy('doc_order')->get();

        // Evaluasi kelengkapan data secara real-time
        $evaluatedDocs = $documents->map(function ($doc) use ($project) {
            $val = $this->spjService->validateDocumentData($project, $doc->doc_key);
            $doc->is_data_complete = $val['is_complete'];
            $doc->missing_requirements = $val['missing'];

            return $doc;
        });

        return response()->json([
            'success' => true,
            'project' => $project,
            'data' => $evaluatedDocs,
        ]);
    }

    /**
     * Ambil detail form satu dokumen SPJ
     */
    public function getDocument(string $id, string $docKey): JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $doc = SpjDocument::where('project_id', $project->project_id)
            ->where('doc_key', $docKey)
            ->firstOrFail();

        $validation = $this->spjService->validateDocumentData($project, $docKey);

        return response()->json([
            'success' => true,
            'project' => $project,
            'document' => $doc,
            'validation' => $validation,
        ]);
    }

    /**
     * Simpan form isian dokumen (HPS, Penawaran, Nego, dll)
     */
    public function updateDocument(Request $request, string $id, string $docKey): JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $doc = SpjDocument::where('project_id', $project->project_id)
            ->where('doc_key', $docKey)
            ->firstOrFail();

        $request->validate([
            'doc_number' => ['nullable', 'string'],
            'doc_date' => ['nullable', 'string'],
            'form_data' => ['required', 'array'],
            'form_data.items' => ['sometimes', 'array'],
            'form_data.items.*' => ['array'],
            'form_data.photos' => ['sometimes', 'array'],
            'form_data.photos.*' => ['array'],
        ]);

        $doc->update([
            'doc_number' => $request->input('doc_number', $doc->doc_number),
            'doc_date' => $request->input('doc_date', $doc->doc_date),
            'form_data' => array_merge($doc->form_data ?? [], $request->input('form_data')),
            'status' => 'DRAFT',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Form dokumen {$doc->doc_name} berhasil disimpan.",
            'data' => $doc,
        ]);
    }

    /**
     * Generate dokumen tunggal (.docx) dari template
     */
    public function generateDocument(Request $request, string $id, string $docKey): JsonResponse
    {
        $request->validate(['form_data' => ['sometimes', 'array'], 'form_data.items' => ['sometimes', 'array'], 'form_data.items.*' => ['array'], 'form_data.photos' => ['sometimes', 'array'], 'form_data.photos.*' => ['array'], 'format' => ['sometimes', 'in:docx,pdf']]);
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $formData = $request->input('form_data', []);

        $doc = $this->spjService->generateDocument($project, $docKey, $formData);
        $format = $request->input('format', 'docx');
        if ($format === 'pdf') {
            $this->spjService->renderPdf($project, $doc);
        }

        return response()->json([
            'success' => true,
            'message' => "Dokumen {$doc->doc_name} berhasil digenerate.",
            'data' => $doc,
            'download_url' => route('api.spj.documents.download', [
                'id' => $project->project_id,
                'docKey' => $docKey,
                'format' => $format,
            ]),
        ]);
    }

    /**
     * Bulk generate dokumen yang dicentang oleh user
     */
    public function bulkGenerate(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'doc_keys' => ['required', 'array', 'min:1'],
        ]);

        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $docKeys = $request->input('doc_keys');

        $result = $this->spjService->bulkGenerate($project, $docKeys);

        return response()->json([
            'success' => true,
            'message' => "Proses generate selesai: {$result['success_count']} berhasil, {$result['failed_count']} terkendala.",
            'data' => $result,
        ]);
    }

    /**
     * Unggah dokumen eksternal (SPPH, SPK basah, Bukti Foto, dll)
     */
    public function uploadExternal(Request $request, string $id, string $docKey): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:20480'],
            'doc_number' => ['nullable', 'string'],
            'doc_date' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $project = SpjProject::where('project_id', $id)->firstOrFail();

        $doc = $this->spjService->uploadExternalDocument(
            $project,
            $docKey,
            $request->file('file'),
            [
                'doc_number' => $request->input('doc_number'),
                'doc_date' => $request->input('doc_date'),
                'notes' => $request->input('notes'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Dokumen eksternal {$doc->doc_name} berhasil diunggah dan diarsipkan.",
            'data' => $doc,
        ]);
    }

    /**
     * Unduh file dokumen SPJ (Word atau PDF)
     */
    public function downloadDocument(Request $request, string $id, string $docKey): BinaryFileResponse
    {
        $request->validate(['format' => ['sometimes', 'in:docx,pdf,original']]);
        $format = $request->query('format', 'docx');
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $doc = SpjDocument::where('project_id', $id)->where('doc_key', $docKey)->firstOrFail();
        if ($format === 'original') {
            $source = $doc->source_file_path ?: ($doc->status === 'DOKUMEN_EKSTERNAL' ? $doc->file_path : null);
            abort_unless($source, 404, 'Berkas upload asli belum tersedia.');
            $fullPath = config('spj.storage_root').'/'.$source;
        } else {
            // Always use the latest saved form/project, never an unfilled master.
            $doc = $this->spjService->generateDocument($project, $docKey);
            $fullPath = $format === 'pdf' ? $this->spjService->renderPdf($project, $doc) : $doc->full_path;
        }
        abort_unless($fullPath && is_file($fullPath), 404, 'Berkas dokumen belum tersedia.');
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'pdf' => 'application/pdf', 'png' => 'image/png', 'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };

        return response()->download($fullPath, basename($fullPath), ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store']);
    }
}
