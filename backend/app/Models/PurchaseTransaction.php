<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
}
