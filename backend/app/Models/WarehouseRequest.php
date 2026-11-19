<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class WarehouseRequest
 *
 * @property int $id
 * @property string $request_code
 * @property string $request_date
 * @property string|null $project_id
 * @property string|null $project_name
 * @property string|null $purpose
 * @property string $requested_by
 * @property string $status
 * @property float $total_est_cost
 * @property array|null $items
 * @property string|null $purchase_code
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WarehouseRequest extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'request_code',
        'request_date',
        'project_id',
        'project_name',
        'purpose',
        'requested_by',
        'status',
        'total_est_cost',
        'items',
        'purchase_code',
        'notes',
        'sales_order_id',
        'requested_by_id',
        'order_items_hash',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'total_est_cost' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public static function itemsHash(array $items): string
    {
        $quantities = collect($items)->groupBy('sku')->map(fn ($rows) => (int) $rows->sum('qty'))->sortKeys()->all();

        return hash('sha256', json_encode($quantities));
    }

    public function toArray(): array
    {
        return [...parent::toArray(), 'order_number' => $this->order?->order_number,
            'requirements_changed' => ! $this->order || $this->order_items_hash !== self::itemsHash($this->order->items)];
    }
}
