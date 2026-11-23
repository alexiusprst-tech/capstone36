<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use App\Models\WarehouseRequest;
use App\Services\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseProgressController extends Controller
{
    private const TRANSITIONS = [
        'confirm' => [['Menunggu Konfirmasi'], 'Dikonfirmasi Supplier'],
        'dispatch' => [['Dikonfirmasi Supplier', 'Sedang Disiapkan', 'Diterima Sebagian'], 'Dikirim ke Gudang CV'],
        'tracking' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim', 'Diterima Sebagian'], null],
        'arrival' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim', 'Diterima Sebagian'], null],
        'discrepancy' => [['Dikirim ke Gudang CV', 'Barang Pengganti Dikirim', 'Diterima Sebagian'], 'Ketidaksesuaian Fisik (Menunggu Retur)'],
        'replacement' => [['Barang Pengganti Dipesan', 'Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian'], 'Barang Pengganti Dikirim'],
        'reject' => [['Menunggu Konfirmasi'], 'Ditolak Supplier'],
    ];

    public function store(Request $request, string $code): JsonResponse
    {
        $action = $request->input('action');
        $role = strtoupper((string) $request->user()?->role);
        abort_unless(in_array($action, ['arrival', 'discrepancy']) ? $role === 'WAREHOUSE' : ($action === 'followup' ? $role === 'FINANCE' : in_array($role, ['FINANCE', 'SUPPLIER'], true)), 403);
        $data = $request->validate([
            'action' => ['required', Rule::in([...array_keys(self::TRANSITIONS), 'recover', 'followup'])],
            'version' => ['required', 'integer', 'min:0'],
            'progress' => ['required', 'array'],
            'progress.supplier_status' => ['nullable', 'string', 'max:100'],
            'progress.followup_reference' => ['required_if:action,followup', 'string', 'max:255'],
            'progress.followup_notes' => ['required_if:action,followup', 'string', 'max:5000'],
            'progress.supplier_confirmation_date' => ['nullable', 'date'],
            'progress.supplier_notes' => ['nullable', 'string', 'max:5000'],
            'progress.delivery_info' => ['nullable', 'array'],
            'progress.delivery_info.surat_jalan' => ['nullable', 'string', 'max:100'],
            'progress.delivery_info.courier' => ['nullable', 'string', 'max:150'],
            'progress.delivery_info.tracking_number' => ['nullable', 'string', 'max:150'],
            'progress.delivery_info.dispatch_date' => ['nullable', 'date'],
            'progress.delivery_info.est_arrival' => ['nullable', 'string', 'max:100'],
            'progress.delivery_info.shipping_status' => ['nullable', 'string', 'max:150'],
            'progress.delivery_info.current_location' => ['nullable', 'string', 'max:255'],
            'progress.delivery_info.last_updated' => ['nullable', 'string', 'max:100'],
            'progress.delivery_info.notes' => ['nullable', 'string', 'max:5000'],
            'progress.delivery_info.milestones' => ['nullable', 'array', 'max:100'],
            'progress.delivery_info.milestones.*' => ['array'],
            'progress.delivery_info.milestones.*.timestamp' => ['nullable', 'string', 'max:100'],
            'progress.delivery_info.milestones.*.status' => ['nullable', 'string', 'max:150'],
            'progress.delivery_info.milestones.*.location' => ['nullable', 'string', 'max:255'],
            'progress.delivery_info.milestones.*.notes' => ['nullable', 'string', 'max:5000'],
            'progress.delivery_info.milestones.*.updated_by' => ['nullable', 'string', 'max:255'],
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
        $purchase = InventoryTransaction::run(function () use ($request, $code, $data, $role, $action) {
            $purchase = PurchaseTransaction::where('purchase_code', $code)->lockForUpdate()->firstOrFail();
            if ($role === 'SUPPLIER') {
                $supplier = Supplier::whereRaw('LOWER(email) = ?', [strtolower($request->user()->email)])->first();
                abort_unless($supplier && ($purchase->supplier_id ? $purchase->supplier_id === $supplier->id : strcasecmp($supplier->name, (string) $purchase->default_supplier) === 0), 403, 'PO ini bukan milik Supplier Anda.');
            }
            abort_if($purchase->status === 'DITERIMA_GUDANG', 409, 'PO sudah diterima Gudang.');
            abort_unless($purchase->progress_version === (int) $data['version'], 409, 'Status PO sudah berubah. Muat ulang terlebih dahulu.');
            $current = $purchase->supplier_progress ?? ['supplier_status' => 'Menunggu Konfirmasi'];
            abort_if($action === 'replacement' && $purchase->pending_followup, 409, 'Finance harus mencatat referensi dan hasil tindak lanjut sebelum pengiriman pengganti.');
            $progress = $data['progress'];
            if ($action === 'followup') {
                abort_unless($purchase->pending_followup || in_array($current['supplier_status'], ['Ketidaksesuaian Fisik (Menunggu Retur)', 'Ketidaksesuaian', 'Ditolak Supplier'], true), 409, 'PO tidak sedang memerlukan tindak lanjut.');
                $remaining = collect($purchase->receiptSummary())->sum('remaining_qty');
                $progress = [...$current, 'followup_reference' => $progress['followup_reference'], 'followup_notes' => $progress['followup_notes'],
                    'supplier_status' => $remaining > 0 ? 'Barang Pengganti Dipesan' : 'Diterima Gudang (Lengkap & Sesuai)', 'warehouse_arrived' => false];
                $purchase->pending_followup = false;
                $purchase->status = $remaining > 0 ? 'DIPESAN' : 'DITERIMA_GUDANG';
                WarehouseRequest::whereKey($purchase->warehouse_request_id)->update(['status' => $remaining > 0 ? 'DIPROSES_PEMBELIAN' : 'SELESAI']);
            } elseif ($action === 'recover') {
                abort_unless($purchase->supplier_progress === null && in_array($progress['supplier_status'] ?? '', ['Dikonfirmasi Supplier', 'Sedang Disiapkan', 'Dikirim ke Gudang CV', 'Barang Pengganti Dikirim', 'Ditolak Supplier']), 409, 'Data PO sudah tersimpan di server.');
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
                if (in_array($action, ['tracking', 'arrival'], true)) {
                    if (isset($progress['delivery_info'])) {
                        $patch = array_filter($progress['delivery_info'], fn ($value) => $value !== null);
                        $progress['delivery_info'] = array_replace(is_array($current['delivery_info'] ?? null) ? $current['delivery_info'] : [], $patch);
                    } else {
                        // A nullable partial update must never erase the existing shipment identity.
                        unset($progress['delivery_info']);
                    }
                }
                $progress = [...$current, ...array_intersect_key($progress, array_flip($keys)), 'supplier_status' => $next ?? $current['supplier_status']];
                if ($action === 'arrival') {
                    $progress['warehouse_arrived'] = true;
                }
                if (in_array($action, ['dispatch', 'replacement'])) {
                    $progress['warehouse_arrived'] = false;
                }
                if (in_array($action, ['discrepancy', 'reject'], true)) {
                    abort_if(empty($action === 'reject' ? ($progress['rejection_reason'] ?? '') : ($progress['discrepancy_reason'] ?? '')), 422, 'Catat alasan ketidaksesuaian atau penolakan.');
                    $purchase->pending_followup = true;
                    $purchase->status = 'PERLU_TINDAK_LANJUT';
                    WarehouseRequest::whereKey($purchase->warehouse_request_id)->update(['status' => 'PERLU_TINDAK_LANJUT']);
                }
                if ($action === 'replacement') {
                    $purchase->pending_followup = false;
                    $purchase->status = 'DIPESAN';
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
                'before' => $current, 'after' => $progress,
            ]];
            $purchase->save();

            return $purchase;
        });

        return response()->json(['data' => $purchase]);
    }
}
