<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpjDocument;
use App\Models\SpjProject;
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

        $validated = $request->validate([
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
        ]);

        $doc->update([
            'doc_number' => $request->input('doc_number', $doc->doc_number),
            'doc_date' => $request->input('doc_date', $doc->doc_date),
            'form_data' => array_merge($doc->form_data ?? [], $request->input('form_data')),
            'status' => $doc->status === 'BELUM_DIBUAT' ? 'DRAFT' : $doc->status,
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
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $formData = $request->input('form_data', []);

        $doc = $this->spjService->generateDocument($project, $docKey, $formData);

        return response()->json([
            'success' => true,
            'message' => "Dokumen {$doc->doc_name} berhasil digenerate.",
            'data' => $doc,
            'download_url' => route('api.spj.documents.download', [
                'id' => $project->project_id,
                'docKey' => $docKey,
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
    public function downloadDocument(string $id, string $docKey): BinaryFileResponse|JsonResponse
    {
        $project = SpjProject::where('project_id', $id)->firstOrFail();
        $doc = SpjDocument::where('project_id', $project->project_id)
            ->where('doc_key', $docKey)
            ->firstOrFail();

        $fullPath = $doc->full_path;

        if (! $fullPath || ! file_exists($fullPath)) {
            // Jika belum ada file spesifik hasil generate, coba sajikan master template terkait
            $templateRelPath = $this->spjService->validateDocumentData($project, $docKey);
            $templateAbsPath = storage_path("app/templates/master/{$doc->file_name}");

            if (file_exists($templateAbsPath)) {
                $fullPath = $templateAbsPath;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Berkas dokumen {$doc->doc_name} belum tersedia di server.",
                ], 404);
            }
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };

        return response()->download($fullPath, $doc->file_name ?: "{$docKey}_{$project->project_id}.docx", [
            'Content-Type' => $mime,
        ]);
    }
}
