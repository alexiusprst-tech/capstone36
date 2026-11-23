@php
    $money = static function ($value) {
        if (!is_numeric($value)) {
            return 'Belum tersedia';
        }
        $amount = round((float) $value, 2);
        return 'Rp '.number_format($amount, floor($amount) == $amount ? 0 : 2, ',', '.');
    };
    $date = static fn ($value) => $value
        ? \Illuminate\Support\Carbon::parse($value)->locale('id')->isoFormat('D MMMM YYYY')
        : 'Belum tersedia';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $invoice['invoiceNumber'] }} | CV Alpharo Artha Jaya</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8f9fb; color: #1c2130; font: 1rem/1.5 Inter, "Plus Jakarta Sans", system-ui, sans-serif; }
        main { max-width: 48rem; margin: clamp(1rem, 5vw, 4rem) auto; padding: 0 1rem; overflow-wrap: anywhere; }
        article { background: #fff; border: 1px solid #ced1db; border-top: .25rem solid #2563eb; border-radius: .75rem; padding: clamp(1rem, 4vw, 2rem); }
        header, section { padding-bottom: 1.25rem; }
        section { border-top: 1px solid #e5e7ed; padding-top: 1.25rem; }
        section:last-child { padding-bottom: 0; }
        h1, h2, p, dl, dd { margin: 0; }
        h1 { margin-top: .75rem; font-size: 1.75rem; }
        h2 { font-size: 1.125rem; margin-bottom: .75rem; }
        .brand { color: #1d4ed8; font-weight: 650; }
        .number { font-family: ui-monospace, monospace; }
        dl { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem 1.25rem; }
        dt, .quantity { color: #515868; font-size: .875rem; }
        dd { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { vertical-align: top; text-align: left; padding: .55rem .25rem; }
        th { border-bottom: 1px solid #ced1db; color: #515868; font-size: .875rem; }
        tbody tr + tr { border-top: 1px solid #e5e7ed; }
        th:last-child, td:last-child { text-align: right; }
        .quantity { display: block; }
        .totals { margin: .75rem 0 1.25rem; border-radius: .5rem; background: #f8f9fb; padding: .875rem; }
        .amount { font-variant-numeric: tabular-nums; }
        .instruction { margin-top: .625rem; }
        @media (max-width: 32rem) { dl { grid-template-columns: 1fr; } }
        @media print { body { background: white; } main { max-width: none; margin: 0; } article { border: 0; } }
    </style>
</head>
<body>
<main>
    <article aria-labelledby="invoice-title">
        <header>
            <p class="brand">CV Alpharo Artha Jaya</p>
            <h1 id="invoice-title">Invoice</h1>
            <p class="number">{{ $invoice['invoiceNumber'] }}</p>
        </header>
        <section aria-labelledby="bill-details">
            <h2 id="bill-details">Detail tagihan</h2>
            <dl>
                <div><dt>Ditagihkan kepada</dt><dd>{{ $invoice['clientName'] ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Nomor pesanan</dt><dd class="number">{{ $invoice['orderNumber'] }}</dd></div>
                <div><dt>Tanggal invoice</dt><dd>{{ $date($invoice['invoiceDate']) }}</dd></div>
                <div><dt>Jatuh tempo</dt><dd>{{ $date($invoice['dueDate']) }}</dd></div>
            </dl>
        </section>
        @if (count($invoice['items']))
            <section aria-labelledby="item-details">
                <h2 id="item-details">Rincian barang</h2>
                <table aria-labelledby="item-details">
                    <thead><tr><th scope="col">Barang</th><th scope="col">Subtotal</th></tr></thead>
                    <tbody>
                    @foreach ($invoice['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] ?: 'Nama barang belum tersedia' }}
                                <span class="quantity">
                                    @if ($item['qty'] !== null && $item['price'] !== null)
                                        {{ $item['qty'] }} {{ $item['unit'] }} × {{ $money($item['price']) }}
                                    @else
                                        Jumlah {{ $item['qty'] ?? '—' }} {{ $item['unit'] }} · Harga satuan {{ $money($item['price']) }}
                                    @endif
                                </span>
                            </td>
                            <td class="amount">{{ $money($item['total']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @endif
        <section aria-labelledby="payment-details">
            <h2 id="payment-details">Pembayaran</h2>
            <dl class="totals">
                <div><dt>Total tagihan</dt><dd class="amount">{{ $money($invoice['totalAmount']) }}</dd></div>
                <div><dt>Sisa tagihan</dt><dd class="amount">{{ $money($invoice['remainingAmount']) }}</dd></div>
            </dl>
            <dl>
                <div><dt>Status pembayaran</dt><dd>{{ $invoice['status'] }}</dd></div>
                @if (count($invoice['paymentMethods']))
                    <div><dt>Metode pembayaran</dt><dd>{{ implode(' / ', $invoice['paymentMethods']) }}</dd></div>
                @endif
            </dl>
            @if ($invoice['remainingAmount'] > 0 || ($invoice['remainingAmount'] === null && $invoice['status'] !== 'Lunas'))
                <p class="instruction">{{ $invoice['paymentInstruction'] ?: 'Instruksi pembayaran belum tersedia.' }}</p>
            @endif
        </section>
    </article>
</main>
</body>
</html>
