<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class PurchaseTransaction
 *
 * @property int $id
 * @property string $purchase_code
 * @property string $transaction_date
 * @property string|null $invoice_number
 * @property string|null $project_id
 * @property string|null $project_name
 * @property string|null $default_supplier
 * @property int|null $warehouse_request_id
 * @property string|null $warehouse_request_code
 * @property float $subtotal
 * @property float $discount
 * @property float $total_price
 * @property string $payment_method
 * @property string $status
 * @property array|null $items
 * @property string|null $created_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PurchaseTransaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_code',
        'transaction_date',
        'invoice_number',
        'project_id',
        'project_name',
        'default_supplier',
        'warehouse_request_id',
        'warehouse_request_code',
        'subtotal',
        'discount',
        'total_price',
        'payment_method',
        'status',
        'items',
        'created_by',
        'notes',
        'supplier_progress',
        'progress_version',
        'progress_history',
        'supplier_id',
        'pending_followup',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'supplier_progress' => 'array',
        'progress_version' => 'integer',
        'progress_history' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'pending_followup' => 'boolean',
    ];

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class)->with('lines')->orderBy('id');
    }

    public function receiptSummary(): array
    {
        $lines = $this->receipts->flatMap(fn ($receipt) => $receipt->lines);

        return collect($this->items ?? [])->values()->map(function ($item, $index) use ($lines) {
            $received = $lines->where('line_index', $index);
            $accepted = (int) $received->sum('accepted_qty');
            $rejected = (int) $received->sum('rejected_qty');
            $remaining = max(0, (int) $item['qty'] - $accepted);

            return ['line_index' => $index, 'sku' => $item['sku'] ?? null, 'name' => $item['barang'] ?? $item['name'] ?? '',
                'unit' => $item['unit'] ?? 'UNIT', 'expected_qty' => (int) $item['qty'],
                'accepted_qty' => $accepted, 'rejected_qty' => $rejected, 'remaining_qty' => $remaining,
                'accepted' => $accepted, 'rejected' => $rejected, 'remaining' => $remaining];
        })->all();
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $progress = $data['supplier_progress'] ?? [];
        unset($data['supplier_progress']);

        return [...$data, ...$progress,
            'receipts' => $this->receipts->toArray(), 'receipt_summary' => $this->receiptSummary(),
            'supplier_status' => $this->status === 'DITERIMA_GUDANG' ? 'Diterima Gudang (Lengkap & Sesuai)' : ($progress['supplier_status'] ?? 'Menunggu Konfirmasi'),
            'progress_persisted' => $this->supplier_progress !== null || $this->status === 'DITERIMA_GUDANG',
        ];
    }
}
