<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDelivery extends Model
{
    private const PENDING_LEASE_SECONDS = 120;

    protected $fillable = [
        'sales_order_id', 'customer_id', 'requested_by', 'invoice_number', 'recipient', 'default_recipient', 'channel', 'token_hash', 'expires_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'default_recipient' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'sent')->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function deliveryStatus(): string
    {
        if ($this->status === 'pending' && ($this->revoked_at
            || $this->created_at->lte(now()->subSeconds(self::PENDING_LEASE_SECONDS)))) {
            return 'failed';
        }

        return $this->status;
    }

    public static function invoiceNumberFor(SalesOrder $order): ?string
    {
        $invoice = $order->invoice;
        if (! $invoice || ! $order->customer_id
            || ($invoice['orderNumber'] ?? $order->order_number) !== $order->order_number
            || (int) ($invoice['clientId'] ?? $order->customer_id) !== (int) $order->customer_id) {
            return null;
        }

        // Older issued invoices can rely on the locked order number.
        return $invoice['invoiceNumber'] ?? $order->order_number;
    }
}
