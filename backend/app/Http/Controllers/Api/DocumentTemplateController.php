<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Services\DocxTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentTemplateController extends Controller
{
    public function __construct(
        protected DocxTemplateService $templateService
    ) {}

    /**
     * Tampilkan seluruh master template surat.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DocumentTemplate::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $templates = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar master template surat berhasil diambil.',
            'data' => $templates,
        ]);
    }

    /**
     * Tampilkan detail satu template berserta variabel placeholder.
     */
    public function show(int $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Unduh template kosong (blank template) untuk pengisian manual.
     */
    public function downloadBlank(int $id): BinaryFileResponse|JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);
        $filePath = $template->blank_full_path;

        if (! file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File template tidak ditemukan pada sistem penyimpanan.',
            ], 404);
        }

        $cleanName = "BLANK_{$template->code}.docx";

        return response()->download($filePath, $cleanName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Unduh template master (dengan placeholder) untuk evaluasi format.
     */
    public function downloadMaster(int $id): BinaryFileResponse|JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);
        $filePath = $template->master_full_path;

        if (! file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File master template tidak ditemukan.',
            ], 404);
        }

        return response()->download($filePath, $template->file_name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Upload / perbarui file template .docx baru oleh admin.
     */
    public function upload(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'], // Max 10MB
        ]);

        $template = DocumentTemplate::findOrFail($id);

        $this->templateService->updateTemplateFile($template, $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => "File template {$template->name} berhasil diperbarui.",
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Aktifkan atau nonaktifkan template.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Status template berhasil diubah.',
            'data' => $template,
        ]);
    }
}
