<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    protected $guarded = [];

    protected $hidden = ['payload_hash'];

    protected $casts = ['legacy' => 'boolean'];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReceiptLine::class);
    }
}
