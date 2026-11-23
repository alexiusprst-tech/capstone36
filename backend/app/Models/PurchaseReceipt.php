<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id')->select(['id', 'name']);
    }

    public function toArray(): array
    {
        $this->loadMissing('receiver');

        return [...parent::toArray(), 'receiver_name' => $this->receiver?->name];
    }
}
