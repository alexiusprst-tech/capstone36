<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseProgressController extends Controller
{
    private const TRANSITIONS = [
        'confirm' => [['Menunggu Konfirmasi'], 'Dikonfirmasi Supplier'],
        'dispatch' => [['Dikonfirmasi Supplier', 'Sedang Disiapkan'], 'Dikirim ke Gudang CV'],
        'tracking' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim'], null],
        'arrival' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim'], null],
        'discrepancy' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim'], 'Ketidaksesuaian Fisik (Menunggu Retur)'],
        'replacement' => [['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian'], 'Barang Pengganti Dikirim'],
        'reject' => [['Menunggu Konfirmasi'], 'Ditolak Supplier'],
    ];

    public function store(Request $request, string $code): JsonResponse
    {
        $action = $request->input('action');
        $role = strtoupper((string) $request->user()?->role);
        abort_unless(in_array($action, ['arrival', 'discrepancy']) ? $role === 'WAREHOUSE' : $role === 'SUPPLIER', 403);
        $data = $request->validate([
            'action' => ['required', Rule::in([...array_keys(self::TRANSITIONS), 'recover'])],
            'version' => ['required', 'integer', 'min:0'],
            'progress' => ['required', 'array'],
            'progress.supplier_status' => ['required', 'string', 'max:100'],
            'progress.supplier_confirmation_date' => ['nullable', 'date'],
            'progress.supplier_notes' => ['nullable', 'string', 'max:5000'],
            'progress.delivery_info' => ['nullable', 'array'],
            'progress.delivery_info.*' => ['nullable'],
            'progress.warehouse_arrived' => ['nullable', 'boolean'],
            'progress.discrepancy_reason' => ['nullable', 'string', 'max:5000'],
            'progress.discrepancy_reported_at' => ['nullable', 'date'],
            'progress.discrepancy_media' => ['nullable', 'array', 'max:20'],
            'progress.discrepancy_items' => ['nullable', 'array', 'max:100'],
            'progress.discrepancy_items.*' => ['nullable'],
            'progress.discrepancy_info' => ['nullable', 'array'],
            'progress.discrepancy_info.*' => ['nullable'],
            'progress.rejection_reason' => ['nullable', 'string', 'max:5000'],
            'progress.discrepancy_resolution' => ['nullable', 'array'],
            'progress.discrepancy_resolution.*' => ['nullable'],
        ]);
        $purchase = DB::transaction(function () use ($request, $code, $data, $role, $action) {
            $purchase = PurchaseTransaction::where('purchase_code', $code)->lockForUpdate()->firstOrFail();
            if ($role === 'SUPPLIER') {
                $supplier = Supplier::whereRaw('LOWER(email) = ?', [strtolower($request->user()->email)])->first();
                abort_unless($supplier && strcasecmp($supplier->name, (string) $purchase->default_supplier) === 0, 403, 'PO ini bukan milik Supplier Anda.');
            }
            abort_if($purchase->status === 'DITERIMA_GUDANG', 409, 'PO sudah diterima Gudang.');
            abort_unless($purchase->progress_version === (int) $data['version'], 409, 'Status PO sudah berubah. Muat ulang terlebih dahulu.');
            $current = $purchase->supplier_progress ?? ['supplier_status' => 'Menunggu Konfirmasi'];
            $progress = $data['progress'];
            if ($action === 'recover') {
                abort_unless($purchase->supplier_progress === null && in_array($progress['supplier_status'], ['Dikonfirmasi Supplier', 'Sedang Disiapkan', 'Dikirim ke Gudang CV', 'Barang Pengganti Dikirim', 'Ditolak Supplier']), 409, 'Data PO sudah tersimpan di server.');
                $progress = array_intersect_key($progress, array_flip(['supplier_status', 'supplier_notes', 'supplier_confirmation_date', 'delivery_info', 'rejection_reason']));
                $progress['warehouse_arrived'] = false;
            } else {
                [$allowed, $next] = self::TRANSITIONS[$action];
                abort_unless(in_array($current['supplier_status'], $allowed), 409, 'Tahap PO sudah diproses atau tidak sesuai.');
                abort_if($action === 'arrival' && ! empty($current['warehouse_arrived']), 409, 'Kedatangan barang sudah dicatat.');
                $keys = match ($action) {
                    'confirm' => ['supplier_notes', 'supplier_confirmation_date'],
                    'dispatch', 'tracking' => ['delivery_info'],
                    'arrival' => ['warehouse_arrived', 'delivery_info'],
                    'discrepancy' => ['discrepancy_reason', 'discrepancy_reported_at', 'discrepancy_media', 'discrepancy_info', 'discrepancy_items'],
                    'replacement' => ['delivery_info', 'discrepancy_resolution', 'warehouse_arrived'],
                    'reject' => ['rejection_reason'],
                };
                $progress = [...$current, ...array_intersect_key($progress, array_flip($keys)), 'supplier_status' => $next ?? $current['supplier_status']];
                if ($action === 'arrival') {
                    $progress['warehouse_arrived'] = true;
                }
                if (in_array($action, ['dispatch', 'replacement'])) {
                    $progress['warehouse_arrived'] = false;
                }
            }
            if (in_array($action, ['dispatch', 'replacement']) || ($action === 'recover' && isset($progress['delivery_info']))) {
                foreach (['surat_jalan', 'courier', 'tracking_number', 'dispatch_date'] as $field) {
                    abort_if(empty($progress['delivery_info'][$field]), 422, 'Lengkapi surat jalan, kurir, resi, dan tanggal kirim.');
                }
            }
            $purchase->supplier_progress = $progress;
            $purchase->progress_version++;
            $purchase->progress_history = [...($purchase->progress_history ?? []), [
                'action' => $action, 'version' => $purchase->progress_version,
                'user_id' => $request->user()->id, 'user' => $request->user()->name,
                'role' => $role, 'timestamp' => now()->toIso8601String(),
            ]];
            $purchase->save();

            return $purchase;
        }, 3);

        return response()->json(['data' => $purchase]);
    }
}
