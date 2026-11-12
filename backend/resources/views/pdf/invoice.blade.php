<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice['invoiceNumber'] ?? $order->order_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #1e293b;
        }
        .header-table {
            width: 100%;
            border-bottom: 2pt solid #0284c7;
            padding-bottom: 8pt;
            margin-bottom: 12pt;
        }
        .header-table td {
            vertical-align: middle;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #0369a1;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 8pt;
            color: #64748b;
            font-weight: bold;
            margin-top: 1pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-address {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 3pt;
            line-height: 1.25;
        }
        .invoice-badge-title {
            text-align: right;
        }
        .invoice-title {
            font-size: 18pt;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .invoice-number {
            font-family: monospace;
            font-size: 10pt;
            font-weight: bold;
            color: #0284c7;
            margin-top: 2pt;
        }
        .status-pill {
            display: inline-block;
            padding: 2pt 8pt;
            border-radius: 9999px;
            font-size: 8pt;
            font-weight: bold;
            margin-top: 4pt;
        }
        .status-unpaid {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 0.5pt solid #fecaca;
        }
        .status-partial {
            background-color: #fffbeb;
            color: #b45309;
            border: 0.5pt solid #fde68a;
        }
        .status-paid {
            background-color: #ecfdf5;
            color: #047857;
            border: 0.5pt solid #a7f3d0;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 14pt;
            border-collapse: collapse;
        }
        .info-grid td {
            vertical-align: top;
            padding: 0;
        }
        .info-card {
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            border-radius: 4pt;
            padding: 8pt 10pt;
        }
        .info-card-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4pt;
            border-bottom: 0.5pt solid #e2e8f0;
            padding-bottom: 3pt;
        }
        .info-row {
            margin-bottom: 2pt;
        }
        .info-label {
            font-size: 8pt;
            color: #64748b;
            display: inline-block;
            width: 85pt;
        }
        .info-value {
            font-size: 8.5pt;
            color: #0f172a;
            font-weight: 600;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12pt;
        }
        table.items th {
            background-color: #0284c7;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6pt 7pt;
            text-align: left;
            border: 0.5pt solid #0284c7;
        }
        table.items td {
            padding: 6pt 7pt;
            border: 0.5pt solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        table.items tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12pt;
        }
        .summary-container td {
            vertical-align: top;
        }
        .bank-box {
            background-color: #f0fdf4;
            border: 0.5pt solid #bbf7d0;
            border-radius: 4pt;
            padding: 8pt 10pt;
            font-size: 8pt;
        }
        .bank-box-title {
            font-weight: bold;
            color: #166534;
            text-transform: uppercase;
            margin-bottom: 4pt;
            font-size: 8pt;
        }
        .calc-box {
            width: 220pt;
            float: right;
            border-collapse: collapse;
        }
        .calc-box td {
            padding: 3pt 6pt;
            font-size: 8.5pt;
        }
        .calc-total-row td {
            border-top: 1.5pt solid #0284c7;
            font-size: 10pt;
            font-weight: bold;
            color: #0369a1;
            padding-top: 5pt;
        }
        .termin-section {
            margin-bottom: 14pt;
            background-color: #f8fafc;
            border: 0.5pt solid #e2e8f0;
            border-radius: 4pt;
            padding: 8pt 10pt;
        }
        .termin-title {
            font-size: 8pt;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 5pt;
        }
        .termin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .termin-table th {
            background-color: #e2e8f0;
            padding: 3pt 6pt;
            text-align: left;
            font-weight: bold;
        }
        .termin-table td {
            padding: 3pt 6pt;
            border-bottom: 0.5pt solid #cbd5e1;
        }
        .signatures-table {
            width: 100%;
            margin-top: 15pt;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signatures-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 8pt;
        }
        .signature-space {
            height: 45pt;
        }
        .signer-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 9pt;
            color: #0f172a;
        }
        .signer-role {
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $logoData = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
        $invStatus = $invoice['status'] ?? 'Belum Dibayar';
        $statusClass = match($invStatus) {
            'Lunas' => 'status-paid',
            'Sebagian / Termin' => 'status-partial',
            default => 'status-unpaid',
        };
        $customerProfile = $details['customerProfile'] ?? [];
    @endphp

    <!-- HEADER / KOP SURAT -->
    <table class="header-table">
        <tr>
            <td style="width: 18%; text-align: left;">
                @if($logoData)
                    <img src="{{ $logoData }}" style="max-height: 48pt; max-width: 90pt;" alt="Logo Alpharo">
                @endif
            </td>
            <td style="width: 50%; text-align: left;">
                <div class="company-name">CV Alpharo Artha Jaya</div>
                <div class="company-subtitle">General Contractor & IT Solutions</div>
                <div class="company-address">
                    Jl. Dr. KRT Radjiman Widyodiningrat No. 20, Cakung, Jakarta Timur 13930<br>
                    Telp: (021) 8611234 &bull; Email: finance@alpharo.co.id &bull; Web: www.alpharo.co.id
                </div>
            </td>
            <td style="width: 32%; text-align: right;" class="invoice-badge-title">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice['invoiceNumber'] ?? '-' }}</div>
                <div class="status-pill {{ $statusClass }}">{{ strtoupper($invStatus) }}</div>
            </td>
        </tr>
    </table>

    <!-- INFO REKANAN & INFO INVOICE -->
    <table class="info-grid">
        <tr>
            <td style="width: 48.5%;">
                <div class="info-card">
                    <div class="info-card-title">Ditujukan Kepada (Customer):</div>
                    <div style="font-weight: bold; font-size: 9.5pt; color: #0f172a; margin-bottom: 2pt;">
                        {{ $invoice['clientName'] ?? $details['clientName'] ?? '-' }}
                    </div>
                    @if(!empty($customerProfile['address']))
                        <div style="font-size: 8pt; color: #475569; margin-bottom: 3pt;">
                            {{ $customerProfile['address'] }}
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label" style="width: 65pt;">Kontak / PIC:</span>
                        <span class="info-value">{{ $customerProfile['contactPerson'] ?? $details['penanggungJawabNama'] ?? '-' }}</span>
                    </div>
                    @if(!empty($customerProfile['phone']))
                    <div class="info-row">
                        <span class="info-label" style="width: 65pt;">Telepon:</span>
                        <span class="info-value font-mono">{{ $customerProfile['phone'] }}</span>
                    </div>
                    @endif
                </div>
            </td>
            <td style="width: 3%;"></td>
            <td style="width: 48.5%;">
                <div class="info-card">
                    <div class="info-card-title">Rincian Transaksi:</div>
                    <div class="info-row">
                        <span class="info-label">No. Pesanan:</span>
                        <span class="info-value font-mono">{{ $order->order_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ID Project:</span>
                        <span class="info-value font-mono">{{ $details['projectId'] ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama Proyek:</span>
                        <span class="info-value">{{ $details['projectName'] ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal Terbit:</span>
                        <span class="info-value">{{ $invoice['invoiceDate'] ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Jatuh Tempo:</span>
                        <span class="info-value" style="color: #b91c1c;">{{ $invoice['dueDate'] ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Skema Bayar:</span>
                        <span class="info-value">{{ $invoice['paymentType'] ?? 'Penuh' }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABEL BARANG / JASA -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">SKU</th>
                <th style="width: 45%;">Nama Barang & Spesifikasi</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 12%;" class="text-right">Harga Satuan</th>
                <th style="width: 13%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono" style="font-size: 7.5pt;">{{ $item['sku'] ?? '-' }}</td>
                    <td>
                        <strong>{{ $item['name'] ?? '-' }}</strong>
                        @if(!empty($item['specs']))
                            <div style="font-size: 7.5pt; color: #64748b;">{{ $item['specs'] }}</div>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $item['qty'] ?? 1 }} {{ $item['unit'] ?? 'Unit' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($item['price'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="font-weight: bold;">
                        Rp {{ number_format(($item['qty'] ?? 1) * ($item['price'] ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8; padding: 12pt;">
                        Rincian pengadaan pekerjaan proyek {{ $details['projectName'] ?? '' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SUMMARY & BANK TRANSFER INFO -->
    <table class="summary-container">
        <tr>
            <td style="width: 50%;">
                <div class="bank-box">
                    <div class="bank-box-title">Rekening Pembayaran Resmi:</div>
                    <div style="font-size: 8.5pt; line-height: 1.4;">
                        Bank: <strong>Bank BCA Jakarta</strong><br>
                        Nomor Rekening: <strong class="font-mono" style="color: #0369a1; font-size: 9.5pt;">52323628813</strong><br>
                        Atas Nama: <strong>CV ALPHARO ARTHA JAYA</strong><br>
                        <span style="font-size: 7.5pt; color: #475569; display: block; margin-top: 3pt;">
                            * Harap sertakan nomor invoice <strong>{{ $invoice['invoiceNumber'] ?? '' }}</strong> pada berita transfer pembayaran.
                        </span>
                    </div>
                </div>
            </td>
            <td style="width: 50%;">
                <table class="calc-box">
                    <tr>
                        <td style="color: #64748b;">Subtotal:</td>
                        <td class="text-right font-mono">Rp {{ number_format($order->subtotal ?? ($invoice['totalAmount'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                    @if(!empty($order->tax) && $order->tax > 0)
                    <tr>
                        <td style="color: #64748b;">PPN (11%):</td>
                        <td class="text-right font-mono">Rp {{ number_format($order->tax, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="calc-total-row">
                        <td>TOTAL TAGIHAN:</td>
                        <td class="text-right font-mono">Rp {{ number_format($invoice['totalAmount'] ?? $order->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    @if(!empty($invoice['paidAmount']) && $invoice['paidAmount'] > 0)
                    <tr>
                        <td style="color: #047857;">Telah Dibayar:</td>
                        <td class="text-right font-mono" style="color: #047857; font-weight: bold;">
                            - Rp {{ number_format($invoice['paidAmount'], 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr style="font-weight: bold; color: #b91c1c;">
                        <td>SISA PIUTANG:</td>
                        <td class="text-right font-mono">Rp {{ number_format($invoice['remainingAmount'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- TERMIN BREAKDOWN (JIKA TERMIN) -->
    @if(!empty($invoice['termins']) && count($invoice['termins']) > 1)
        <div class="termin-section">
            <div class="termin-title">Jadwal & Penetapan Termin Pembayaran:</div>
            <table class="termin-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Termin</th>
                        <th style="width: 30%;">Jatuh Tempo</th>
                        <th style="width: 25%;" class="text-right">Nominal Tagihan</th>
                        <th style="width: 20%;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice['termins'] as $termin)
                        <tr>
                            <td><strong>Termin Ke-{{ $termin['termNumber'] ?? $loop->iteration }}</strong></td>
                            <td>{{ $termin['dueDate'] ?? $invoice['dueDate'] ?? '-' }}</td>
                            <td class="text-right font-mono font-bold">
                                Rp {{ number_format($termin['amount'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <span style="font-size: 7.5pt; font-weight: bold; color: {{ ($termin['status'] ?? '') === 'Lunas' ? '#047857' : '#b45309' }};">
                                    {{ strtoupper($termin['status'] ?? 'Belum Dibayar') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- TANDA TANGAN & PENGESAHAN -->
    <table class="signatures-table">
        <tr>
            <td>
                <div style="color: #64748b; margin-bottom: 2pt;">Diterima & Disetujui Oleh,</div>
                <div style="font-weight: bold; color: #0f172a;">{{ $invoice['clientName'] ?? $details['clientName'] ?? 'Customer' }}</div>
                <div class="signature-space"></div>
                <div class="signer-name">{{ $details['penanggungJawabNama'] ?? '( ................................... )' }}</div>
                <div class="signer-role">{{ $details['penanggungJawabJabatan'] ?? 'Pejabat Pengadaan / KPA' }}</div>
            </td>
            <td>
                <div style="color: #64748b; margin-bottom: 2pt;">Jakarta, {{ $invoice['invoiceDate'] ?? date('d-m-Y') }}</div>
                <div style="font-weight: bold; color: #0f172a;">CV ALPHARO ARTHA JAYA</div>
                <div class="signature-space"></div>
                <div class="signer-name">Dewi Lestari, S.E.</div>
                <div class="signer-role">Finance & Accounting Department</div>
            </td>
        </tr>
    </table>
</body>
</html>

