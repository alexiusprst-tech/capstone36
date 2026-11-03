<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseDiscrepancyReport extends Model
{
    protected $fillable = [
        'order_number',
        'client_name',
        'reason',
        'items',
        'reported_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }
}
