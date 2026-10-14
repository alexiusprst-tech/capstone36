<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'total_est_cost' => 'decimal:2',
    ];
}
