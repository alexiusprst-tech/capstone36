<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseTransaction;
use App\Models\SalesOrder;
use App\Models\WarehouseRequest;
use App\Services\InventoryTransaction;
use App\Services\OrderProcurement;
use App\Services\SalesOrderInventory;
use App\Services\SpjLetterhead;
use App\Services\SpjProjectFromOrder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    private array $procurement = [];

    private const APPROVAL_LIMIT = 5_000_000;

    public function __construct(private SalesOrderInventory $inventory) {}

    public function index(Request $request): JsonResponse
    {
        $this->allow($request, ['MARKETING', 'DIREKTUR', 'FINANCE', 'WAREHOUSE', 'ADMINISTRASI']);

        $orders = SalesOrder::query()
            ->orderByDesc('details->date')
            ->orderByDesc('id')
            ->get();
        $summaries = $this->inventory->summaries($orders);
        $this->procurement = app(OrderProcurement::class)->summaries($orders->pluck('id')->all());

        return response()->json(['data' => $orders->map(fn ($order) => $this->present($order, $summaries[$order->id]))]);
    }

    public function update(Request $request, string $number): JsonResponse
    {
        $this->allow($request, ['MARKETING']);
        abort_unless(preg_match('/^[A-Za-z0-9-]{1,80}$/', $number), 422, 'Nomor pesanan tidak valid.');
        $data = $request->validate([
            ...SpjLetterhead::rules('details.customerProfile'),
            'version' => ['required', 'integer', 'min:0'],
            'details' => ['required', 'array'],
            'details.clientName' => ['required', 'string', 'max:255'],
            'details.projectName' => ['required', 'string', 'max:255'],
            'details.clientId' => ['required', 'integer', 'exists:customers,id'],
            'details.projectId' => ['nullable', 'string', 'max:80'],
            'details.description' => ['nullable', 'string', 'max:5000'],
            'details.notes' => ['nullable', 'string', 'max:5000'],
            'details.date' => ['nullable', 'date'],
            'details.company' => ['nullable', 'string', 'max:255'],
            'details.marketingName' => ['nullable', 'string', 'max:255'],
            'details.namaKegiatan' => ['nullable', 'string', 'max:255'],
            'details.rincianKegiatan' => ['nullable', 'string', 'max:5000'],
            'details.kodeRekening' => ['nullable', 'string', 'max:100'],
            'details.nomorRekening' => ['nullable', 'string', 'max:100'],
            'details.bankPenerima' => ['nullable', 'string', 'max:255'],
            'details.tahunAnggaran' => ['nullable', 'string', 'max:20'],
            'details.sumberDana' => ['nullable', 'string', 'max:100'],
            'details.kategori' => ['nullable', 'string', 'max:100'],
            'details.paguAnggaran' => ['nullable', 'numeric', 'min:0', 'max:1000000000000'],
            'details.tglMulai' => ['nullable', 'date'],
            'details.estimasiSelesai' => ['nullable', 'date'],
            'details.penanggungJawabNama' => ['nullable', 'string', 'max:255'],
            'details.penanggungJawabJabatan' => ['nullable', 'string', 'max:255'],
            'details.penanggungJawabNip' => ['nullable', 'string', 'max:100'],
            'details.kotaTandaTangan' => ['nullable', 'string', 'max:100'],
            'details.docNumbers' => ['nullable', 'array:penawaran,suratJalan,faktur,kuitansi'],
            'details.docNumbers.*' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.sku' => ['required', 'string', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.specs' => ['nullable', 'string', 'max:5000'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000000'],
        ]);
        $order = InventoryTransaction::run(function () use ($request, $number, $data) {
            $customer = Customer::whereKey($data['details']['clientId'])->lockForUpdate()->firstOrFail();
            // The unique order number also prevents duplicate inserts from separate browsers.
            $order = SalesOrder::firstOrCreate(['order_number' => $number], [
                'created_by' => $request->user()->id, 'details' => [], 'items' => [],
                'subtotal' => 0, 'tax' => 0, 'grand_total' => 0, 'history' => [],
            ]);
            $order = SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === 'Draft', 409, 'Rincian pesanan terkunci setelah diajukan.');
            abort_if(! empty($order->history) && $data['version'] !== $order->version, 409, 'Data pesanan berubah. Muat ulang sebelum menyimpan.');
            $before = ['details' => $order->details, 'items' => $order->items, 'subtotal' => $order->subtotal, 'tax' => $order->tax, 'grandTotal' => $order->grand_total];
            $catalog = Product::whereIn('sku', array_column($data['items'], 'sku'))->get()->keyBy(fn ($product) => strtolower($product->sku));
            $items = collect($data['items'])->map(function ($item) use ($catalog) {
                $product = $catalog->get(strtolower($item['sku']));
                abort_if($product && strcasecmp($product->unit, $item['unit']) !== 0, 422, 'Satuan pesanan harus mengikuti master barang.');

                return [
                    'sku' => $product?->sku ?? $item['sku'], 'name' => $product?->name ?? $item['name'], 'unit' => $product?->unit ?? $item['unit'],
                    'specs' => $item['specs'] ?? '',
                    'qty' => (int) $item['qty'], 'price' => (float) $item['price'],
                    'total' => (int) $item['qty'] * (int) round($item['price'] * 100) / 100,
                ];
            })->all();
            $subtotalCents = 0;
            if (WarehouseRequest::itemsHash($order->items) !== WarehouseRequest::itemsHash($items)) {
                $requests = WarehouseRequest::where('sales_order_id', $order->id)->pluck('id');
                abort_if(PurchaseTransaction::whereIn('warehouse_request_id', $requests)
                    ->whereNotIn('status', ['DITERIMA_GUDANG', 'DIBATALKAN', 'CANCELLED'])->exists(), 409,
                    'Barang atau jumlah belum dapat diubah selama PO terkait masih berjalan. Harga dan informasi pesanan tetap dapat diperbarui.');
            }
            foreach ($items as $item) {
                $subtotalCents += $item['qty'] * (int) round($item['price'] * 100);
                abort_if($subtotalCents > 100_000_000_000_000, 422, 'Nilai pesanan melebihi batas sistem.');
            }
            $order->details = collect($data['details'])->only([
                'clientId', 'clientName', 'projectName', 'projectId', 'description', 'notes', 'date',
                'company', 'marketingName', 'namaKegiatan', 'rincianKegiatan', 'kodeRekening',
                'nomorRekening', 'bankPenerima', 'tahunAnggaran', 'sumberDana', 'kategori',
                'paguAnggaran', 'tglMulai', 'estimasiSelesai', 'penanggungJawabNama',
                'penanggungJawabJabatan', 'penanggungJawabNip', 'kotaTandaTangan', 'docNumbers', 'customerProfile',
            ])->all();
            $order->items = $items;
            $order->customer_id = $customer->id;
            $details = $order->details;
            $details['clientId'] = $customer->id;
            $details['clientName'] = $customer->name;
            $details['customerProfile'] = [...collect($customer->present())->only(SpjLetterhead::FIELDS)->all(), ...($data['details']['customerProfile'] ?? []), 'code' => $customer->code, 'name' => $customer->name];
            $order->details = $details;
            // Calculate in cents, then round the final amount once to whole rupiah.
            $order->subtotal = intdiv($subtotalCents + 50, 100);
            $order->grand_total = intdiv($subtotalCents * 111 + 5000, 10000);
            $order->tax = $order->grand_total - $order->subtotal;
            $this->inventory->allocate($order);
            $this->record($order, $request, 'Draft Disimpan', 'Rincian pesanan disimpan oleh Marketing.');
            $history = $order->history;
            $history[array_key_last($history)]['before'] = $before;
            $history[array_key_last($history)]['after'] = ['details' => $order->details, 'items' => $order->items, 'subtotal' => $order->subtotal, 'tax' => $order->tax, 'grandTotal' => $order->grand_total];
            $order->history = $history;
            $order->save();
            app(SpjProjectFromOrder::class)->sync($order);

            return $order;
        });

        return response()->json(['data' => $this->present($order)]);
    }

    public function destroy(Request $request, SalesOrder $order): JsonResponse
    {
        $this->allow($request, ['MARKETING']);
        InventoryTransaction::run(function () use ($order) {
            $order = SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === 'Draft', 409, 'Pesanan yang telah diajukan tidak dapat dihapus.');
            abort_if(WarehouseRequest::where('sales_order_id', $order->id)->exists(), 409, 'Pesanan memiliki riwayat pengadaan dan tidak dapat dihapus.');
            $this->inventory->release($order);
            $order->delete();
            $this->inventory->reallocateDrafts();
        });

        return response()->json(['success' => true]);
    }

    public function submit(Request $request, SalesOrder $order): JsonResponse
    {
        $this->allow($request, ['MARKETING']);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);

        return $this->transition($order, function ($order) use ($request, $data) {
            abort_unless($order->status === 'Draft', 409, 'Pesanan telah diajukan; pengajuan ulang tidak diizinkan.');
            abort_unless($order->version === $data['version'], 409, 'Rincian pesanan berubah. Periksa ulang sebelum mengajukan.');
            $this->inventory->assertReady($order);
            $order->status = $order->grand_total > self::APPROVAL_LIMIT ? 'Menunggu Verifikasi' : 'Siap Invoice';
            $order->submitted_by = $request->user()->id;
            $order->submitted_at = now();
            $this->record($order, $request, 'Pengajuan Persetujuan', $order->status === 'Siap Invoice'
                ? 'Stok terpenuhi. Nilai tidak di atas Rp5.000.000; diteruskan ke Finance.'
                : 'Stok terpenuhi. Menunggu persetujuan Direktur sebelum invoice.');
        });
    }

    public function stockCheck(Request $request, SalesOrder $order): JsonResponse
    {
        $this->allow($request, ['MARKETING']);

        return $this->transition($order, function ($order) use ($request) {
            abort_unless($order->status === 'Draft', 409, 'Alokasi pesanan yang telah diajukan terkunci.');
            $this->inventory->allocate($order);
            $this->record($order, $request, 'Pemeriksaan Alokasi Stok', 'Ketersediaan stok gudang dibaca dan alokasi pesanan diperbarui oleh sistem.');
        });
    }

    public function approve(Request $request, SalesOrder $order): JsonResponse
    {
        $this->allow($request, ['DIREKTUR']);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        return $this->transition($order, function ($order) use ($request, $data) {
            abort_unless($order->status === 'Menunggu Verifikasi' && $order->grand_total > self::APPROVAL_LIMIT, 409, 'Pesanan tidak sedang menunggu persetujuan Direktur.');
            $this->inventory->assertReady($order);
            $order->status = 'Siap Invoice';
            $order->approved_by = $request->user()->id;
            $order->approved_at = now();
            $order->approval_notes = $data['notes'] ?? 'Disetujui oleh Direktur.';
            $this->record($order, $request, 'Disetujui Direktur', $order->approval_notes);
        });
    }

    public function invoice(Request $request, SalesOrder $order): JsonResponse
    {
        $this->allow($request, ['FINANCE']);
        $data = $request->validate([
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:invoiceDate'],
            'paymentType' => ['nullable', Rule::in(['Penuh', 'Termin'])],
            'terminCount' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        return $this->transition($order, function ($order) use ($request, $data) {
            abort_unless($order->status === 'Siap Invoice' && $order->submitted_at
                && ($order->grand_total <= self::APPROVAL_LIMIT || $order->approved_at), 409, 'Invoice hanya dapat diterbitkan setelah pengajuan dan persetujuan valid.');
            abort_if($order->invoice !== null, 409, 'Invoice pesanan sudah diterbitkan.');
            $this->inventory->assertReady($order);
            $order->invoice = [
                'id' => 'server-'.$order->id, 'invoiceNumber' => 'INV-'.now()->year.'-S'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'orderNumber' => $order->order_number, 'clientId' => $order->details['clientId'] ?? null,
                'clientName' => $order->details['clientName'], 'invoiceDate' => $data['invoiceDate'], 'dueDate' => $data['dueDate'],
                'totalAmount' => $order->grand_total, 'paidAmount' => 0, 'remainingAmount' => $order->grand_total,
                'status' => 'Belum Dibayar', 'paymentType' => 'Penuh', 'terminCount' => 1, 'termins' => [],
            ];
            $order->status = 'Menunggu Pembayaran';
            $this->record($order, $request, 'Invoice Diterbitkan', $order->invoice['invoiceNumber'].' diterbitkan oleh Finance.');
        });
    }

    public function downloadInvoice(Request $request, string $order): Response
    {
        $this->allow($request, ['FINANCE', 'MARKETING', 'DIREKTUR', 'ADMINISTRASI', 'CLIENT']);
        $salesOrder = SalesOrder::where('order_number', $order)
            ->orWhere('id', $order)
            ->firstOrFail();

        abort_unless($salesOrder->invoice !== null, 404, 'Invoice belum diterbitkan untuk pesanan ini.');

        if (strtoupper((string) $request->user()?->role) === 'CLIENT') {
            $clientId = $salesOrder->details['clientId'] ?? null;
            abort_unless($clientId && $request->user()?->client_id
                && (int) $clientId === (int) $request->user()->client_id, 403, 'Akses invoice tidak diizinkan.');
        }

        $invoice = $salesOrder->invoice;
        $details = $salesOrder->details ?? [];
        $items = $salesOrder->items ?? [];

        $cache = storage_path('framework/cache/pdf');
        File::ensureDirectoryExists($cache);

        $options = new Options([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'Helvetica',
            'tempDir' => $cache,
            'fontCache' => $cache,
            'fontDir' => $cache,
            'chroot' => [base_path(), storage_path()],
        ]);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');

        $html = view('pdf.invoice', [
            'order' => $salesOrder,
            'invoice' => $invoice,
            'details' => $details,
            'items' => $items,
        ])->render();

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $filename = 'Invoice_'.($invoice['invoiceNumber'] ?? $salesOrder->order_number).'.pdf';
        $disposition = $request->query('preview') || $request->query('stream') ? 'inline' : 'attachment';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    private function transition(SalesOrder $order, callable $change): JsonResponse
    {
        $order = InventoryTransaction::run(function () use ($order, $change) {
            $locked = SalesOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $change($locked);

            return $locked;
        });

        return response()->json(['data' => $this->present($order)]);
    }

    private function allow(Request $request, array $roles): void
    {
        abort_unless(in_array(strtoupper((string) $request->user()?->role), $roles, true), 403, 'Peran Anda tidak berhak melakukan tindakan ini.');
    }

    private function record(SalesOrder $order, Request $request, string $stage, string $notes): void
    {
        $order->version++;
        $order->history = [...($order->history ?? []), [
            'id' => $order->version, 'stage' => $stage, 'userId' => $request->user()->id,
            'user' => $request->user()->name, 'role' => strtoupper($request->user()->role),
            'timestamp' => now()->toIso8601String(), 'status' => 'completed', 'notes' => $notes,
        ]];
        $order->save();
    }

    private function present(SalesOrder $order, ?array $stock = null): array
    {
        $stock ??= $this->inventory->summaries(collect([$order]))[$order->id];
        $procurement = $this->procurement[$order->id] ?? app(OrderProcurement::class)->summaries([$order->id])[$order->id];
        $pending = $procurement['pending'];
        foreach ($stock['items'] as &$item) {
            $item['pendingProcurementQty'] = (int) ($pending[$item['sku']] ?? 0);
            $item['unrequestedShortage'] = max(0, $item['shortage'] - $item['pendingProcurementQty']);
        }
        unset($item);
        $stock['ready'] = $stock['status'] === 'Siap Diajukan';

        return [...$order->details,
            'clientId' => $order->customer_id,
            'id' => 'server-'.$order->id, 'serverId' => $order->id, 'serverManaged' => true, 'version' => $order->version,
            'orderNumber' => $order->order_number, 'status' => $order->status, 'items' => $order->items,
            'subtotal' => $order->subtotal, 'tax' => $order->tax, 'grandTotal' => $order->grand_total,
            'createdById' => $order->created_by, 'submittedById' => $order->submitted_by,
            'submittedAt' => $order->submitted_at?->toIso8601String(),
            'approvedById' => $order->approved_by, 'approvalDate' => $order->approved_at?->toIso8601String(),
            'approvedBy' => collect($order->history)->last(fn ($event) => $event['stage'] === 'Disetujui Direktur')['user'] ?? null,
            'stockReadiness' => $stock,
            'procurement' => $procurement['requests'],
            'approvalNotes' => $order->approval_notes, 'timeline' => $order->history, 'invoice' => $order->invoice,
        ];
    }
}
