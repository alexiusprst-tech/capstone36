<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = ['order_number', 'created_by', 'details', 'items', 'subtotal', 'tax', 'grand_total', 'history'];

    protected function casts(): array
    {
        return [
            'details' => 'array', 'items' => 'array', 'history' => 'array', 'invoice' => 'array',
            'submitted_at' => 'datetime', 'approved_at' => 'datetime',
            'subtotal' => 'integer', 'tax' => 'integer', 'grand_total' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function isApprovedForInvoice(): bool
    {
        return $this->submitted_at !== null
            && ($this->grand_total <= 5_000_000 || $this->approved_at !== null)
            && in_array($this->status, ['Siap Invoice', 'Menunggu Pembayaran', 'Selesai'], true);
    }
}
