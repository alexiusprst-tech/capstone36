<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\OrderDocument;
use App\Services\DocxTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderDocumentController extends Controller
{
    public function __construct(
        protected DocxTemplateService $templateService
    ) {}

    /**
     * Tampilkan daftar arsip dokumen hasil order (BAST, PO, Surat Jalan, dll).
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderDocument::with(['template', 'generator']);

        if ($request->filled('order_number')) {
            $query->where('order_number', $request->query('order_number'));
        }

        if ($request->filled('doc_type')) {
            $query->where('doc_type', $request->query('doc_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $docs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar dokumen arsip berhasil diambil.',
            'data' => $docs,
        ]);
    }

    /**
     * Generate dokumen baru secara otomatis dari data order & template master.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => ['required_without:template_code', 'nullable', 'exists:document_templates,id'],
            'template_code' => ['required_without:template_id', 'nullable', 'exists:document_templates,code'],
            'order_number' => ['required', 'string', 'max:100'],
            'doc_number' => ['nullable', 'string', 'max:100'],
            'variables' => ['required', 'array'],
        ]);

        $template = $request->filled('template_id')
            ? DocumentTemplate::findOrFail($request->input('template_id'))
            : DocumentTemplate::where('code', $request->input('template_code'))->firstOrFail();

        $userId = $request->user()?->id;

        $doc = $this->templateService->generate(
            template: $template,
            variables: $request->input('variables'),
            orderNumber: $request->input('order_number'),
            docNumber: $request->input('doc_number'),
            userId: $userId
        );

        return response()->json([
            'success' => true,
            'message' => "Dokumen {$template->name} berhasil di-generate untuk Order #{$doc->order_number}.",
            'data' => $doc,
            'download_url' => route('api.documents.download', $doc->id),
        ], 201);
    }

    /**
     * Unduh file dokumen hasil SPJ, PO, BAST.
     */
    public function download(int $id): BinaryFileResponse|JsonResponse
    {
        $doc = OrderDocument::findOrFail($id);
        $fullPath = $doc->full_path;

        if (! file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas fisik dokumen tidak ditemukan di penyimpanan server.',
            ], 404);
        }

        return response()->download($fullPath, $doc->file_name, [
            'Content-Type' => $doc->mime_type ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Unggah berkas fisik / scan basah (PDF/DOCX/Gambar) yang sudah ditandatangani.
     */
    public function uploadScan(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => ['required', 'string'],
            'doc_type' => ['required', 'string'],
            'title' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:20480'], // Max 20MB
            'doc_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $orderNumber = $request->input('order_number');
        $file = $request->file('file');
        $relativeDir = 'generated_docs/orders/'.Str::slug($orderNumber);
        $absoluteDir = storage_path('app/'.$relativeDir);

        if (! File::exists($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug($request->input('doc_type'))."_{$orderNumber}_".time().".{$extension}";
        $file->move($absoluteDir, $safeName);

        $doc = OrderDocument::create([
            'order_number' => $orderNumber,
            'doc_type' => $request->input('doc_type'),
            'doc_number' => $request->input('doc_number'),
            'title' => $request->input('title'),
            'file_path' => "{$relativeDir}/{$safeName}",
            'file_name' => $safeName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'status' => 'SIGNED',
            'generated_by' => $request->user()?->id,
            'notes' => $request->input('notes', 'Arsip scan basah diunggah manual.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Arsip scan dokumen berhasil disimpan.',
            'data' => $doc,
        ], 201);
    }

    /**
     * Hapus berkas arsip dokumen.
     */
    public function destroy(int $id): JsonResponse
    {
        $doc = OrderDocument::findOrFail($id);

        if (file_exists($doc->full_path)) {
            @unlink($doc->full_path);
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Arsip dokumen berhasil dihapus.',
        ]);
    }
}
