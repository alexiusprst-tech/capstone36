<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit',
        'description',
        'stock',
        'min_stock',
        'last_buy_price',
        'hpp',
        'sell_price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock' => 'integer',
            'last_buy_price' => 'float',
            'hpp' => 'float',
            'sell_price' => 'float',
        ];
    }
}
