<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InvoiceDelivery;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class InvoiceDeliveryService
{
    public function __construct(private InvoiceEmailSender $sender) {}

    // The caller holds the order lock until the attempt has been saved.
    public function reserve(SalesOrder $order, int $actorId, string $token): ?InvoiceDelivery
    {
        $invoiceNumber = InvoiceDelivery::invoiceNumberFor($order);
        abort_unless($invoiceNumber, 409, 'Invoice harus sudah diterbitkan untuk pelanggan pesanan ini.');
        $recipient = $this->recipient($order);
        abort_unless(filter_var($recipient, FILTER_VALIDATE_EMAIL), 422, InvoiceEmailSender::usesDefaultRecipient()
            ? 'Periksa alamat email penerima default di konfigurasi.' : 'Lengkapi email yang valid pada data pelanggan.');
        $pending = InvoiceDelivery::where('sales_order_id', $order->id)->where('status', 'pending')
            ->whereNull('revoked_at')->latest('id')->first();
        if ($pending) {
            if ($pending->deliveryStatus() === 'pending' && $pending->invoice_number === $invoiceNumber
                && (int) $pending->customer_id === (int) $order->customer_id && $pending->recipient === $recipient) {
                return null;
            }
            $pending->status = 'failed';
            $pending->revoked_at = now();
            $pending->save();
        }

        return InvoiceDelivery::create([
            'sales_order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'requested_by' => $actorId,
            'invoice_number' => $invoiceNumber,
            'recipient' => $recipient,
            'default_recipient' => InvoiceEmailSender::usesDefaultRecipient(),
            'channel' => InvoiceEmailSender::channelFor($recipient),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(max(1, (int) config('mail.invoice_link_ttl_days'))),
        ]);
    }

    public function deliver(InvoiceDelivery $delivery, string $token): array
    {
        // Contact the mail provider only after the delivery attempt commits.
        $sent = $this->sender->send($delivery, $token);
        $available = DB::transaction(function () use ($delivery, $sent) {
            $order = SalesOrder::whereKey($delivery->sales_order_id)->lockForUpdate()->firstOrFail();
            $delivery->refresh();
            if ($delivery->deliveryStatus() !== 'pending') {
                if ($delivery->status === 'pending') {
                    $delivery->status = 'failed';
                    $delivery->revoked_at ??= now();
                    $delivery->save();
                }

                return false;
            }
            $delivery->status = $sent ? 'sent' : 'failed';
            if ($sent) {
                if ($delivery->invoice_number !== InvoiceDelivery::invoiceNumberFor($order)
                    || (int) $order->customer_id !== (int) $delivery->customer_id
                    || $delivery->default_recipient !== InvoiceEmailSender::usesDefaultRecipient()
                    || $delivery->recipient !== $this->recipient($order)) {
                    $delivery->revoked_at = now();
                } else {
                    // Only a completed send replaces a previously usable link.
                    InvoiceDelivery::where('sales_order_id', $order->id)->where('id', '<', $delivery->id)
                        ->whereNull('revoked_at')->update(['revoked_at' => now()]);
                }
            }
            $delivery->save();

            return $sent && ! $delivery->revoked_at;
        }, 5);

        if ($sent && ! $available) {
            return ['status' => 409, 'message' => 'Tautan sudah tidak aktif. Periksa status pengiriman sebelum mengirim ulang.'];
        }

        return [
            'status' => $sent ? 200 : 503,
            'message' => $sent ? 'Email invoice telah dikirim.' : 'Pengiriman email invoice gagal. Periksa konfigurasi email lalu coba kembali.',
        ];
    }

    private function recipient(SalesOrder $order): ?string
    {
        $email = trim((string) Customer::whereKey($order->customer_id)->value('email'));

        return InvoiceEmailSender::recipientFor($email);
    }

    public function summary(SalesOrder $order): array
    {
        $customerEmail = Customer::whereKey($order->customer_id)->value('email');
        $recipient = InvoiceEmailSender::recipientFor($customerEmail);
        $attempts = InvoiceDelivery::where('sales_order_id', $order->id)
            ->where('customer_id', $order->customer_id)->where('invoice_number', InvoiceDelivery::invoiceNumberFor($order));
        $latest = (clone $attempts)->latest('id')->first();
        $active = $attempts->active()->latest('id')->first();
        if ($active && ! InvoiceEmailSender::recipientMatches($active, $customerEmail)) {
            $active = null;
        }

        return [
            'recipient' => $recipient,
            'defaultRecipient' => InvoiceEmailSender::usesDefaultRecipient(),
            'channel' => $recipient ? InvoiceEmailSender::channelFor($recipient) : null,
            'latest' => $latest ? [
                'status' => $latest->deliveryStatus(),
                'channel' => $latest->channel,
                'recipient' => $latest->recipient,
                'createdAt' => $latest->created_at->toIso8601String(),
            ] : null,
            'active' => $active !== null,
            'expiresAt' => $active?->expires_at->toIso8601String(),
        ];
    }
}
