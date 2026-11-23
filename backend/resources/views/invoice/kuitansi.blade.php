<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm 20mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.45; color: #000; margin: 0; }
        .page-background { position: fixed; left: -14mm; top: -12mm; width: 210mm; height: 297mm; z-index: -1; }
        .header-bar { width: 100%; border-collapse: collapse; margin: 0 0 12mm 12mm; }
        .header-bar td { vertical-align: middle; padding: 0; }
        .header-logo { width: 14mm; padding-right: 3mm; }
        .header-logo img { width: 13mm; height: 13mm; display: block; }
        .company-name { font-size: 14pt; font-weight: bold; color: #0b70c9; letter-spacing: 0.4pt; line-height: 1.15; }
        .company-sub { font-size: 7.5pt; font-weight: bold; color: #e5a50a; letter-spacing: 0.5pt; margin-top: 1pt; }
        .title { text-align: center; font-size: 19pt; font-weight: bold; letter-spacing: 0.8pt; margin: 0 0 3pt; }
        .doc-number { text-align: center; font-size: 12pt; font-weight: normal; margin: 0 0 12mm; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 14mm; }
        table.info td { padding: 4.5pt 3pt; vertical-align: top; font-size: 12pt; }
        table.info td.label { width: 28%; font-weight: normal; }
        table.info td.colon { width: 4%; text-align: center; }
        table.info td.value { width: 68%; font-style: italic; font-weight: normal; }
        table.closing { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        table.closing td { vertical-align: top; }
        table.closing td.amount-cell { width: 52%; padding-top: 4mm; padding-left: 10mm; }
        table.closing td.sign-cell { width: 48%; padding-left: 16mm; text-align: left; font-size: 12pt; }
        .amount-svg { width: 62mm; height: 13.5mm; display: block; }
        .sign-date { margin: 0 0 2pt; }
        .sign-regards { margin: 0 0 22mm; }
        .sign-name { font-weight: normal; margin: 0; }
        .sign-role { margin: 1pt 0 0; }
        .footer-bar { position: fixed; left: 0; right: 0; bottom: -10mm; font-size: 8pt; color: #1e293b; text-align: center; line-height: 1.35; }
        .footer-contacts { margin-bottom: 2pt; }
        .footer-icon { width: 9pt; height: 9pt; vertical-align: middle; margin-right: 2pt; }
        .footer-sep { display: inline-block; width: 10pt; }
    </style>
</head>
<body>
@if(!empty($assets['background']))
    <img class="page-background" src="{{ $assets['background'] }}" alt="">
@endif

<table class="header-bar">
    <tr>
        @if(!empty($assets['logo']))
            <td class="header-logo"><img src="{{ $assets['logo'] }}" alt="Logo"></td>
        @endif
        <td>
            <div class="company-name">CV ALPHARO ARTHA JAYA</div>
            <div class="company-sub">GENERAL CONTRACTOR &amp; SUPPLIER</div>
        </td>
    </tr>
</table>

<div class="title">KUITANSI</div>
<div class="doc-number">Nomor : {{ $receiptNumber }}</div>

<table class="info">
    <tr>
        <td class="label">Sudah Terima dari</td>
        <td class="colon">:</td>
        <td class="value">{{ $clientName }}</td>
    </tr>
    <tr>
        <td class="label">Banyaknya Uang</td>
        <td class="colon">:</td>
        <td class="value">{{ $amountWords }}</td>
    </tr>
    <tr>
        <td class="label">Pembayaran</td>
        <td class="colon">:</td>
        <td class="value">{{ $paymentDescription }}</td>
    </tr>
</table>

<table class="closing">
    <tr>
        <td class="amount-cell">
            <img class="amount-svg" src="{{ $amountBoxSvg }}" alt="{{ $amountFormatted }}">
        </td>
        <td class="sign-cell">
            <p class="sign-date">Jakarta, {{ $printedDate }}</p>
            <p class="sign-regards">Hormat Kami,</p>
            <p class="sign-name">Franklin Janwar</p>
            <p class="sign-role">Direktur</p>
        </td>
    </tr>
</table>

<div class="footer-bar">
    <div class="footer-contacts">
        @if(!empty($assets['icons'][0]))<img class="footer-icon" src="{{ $assets['icons'][0] }}" alt="">@endif
        0821 9455 5127
        <span class="footer-sep"></span>
        @if(!empty($assets['icons'][1]))<img class="footer-icon" src="{{ $assets['icons'][1] }}" alt="">@endif
        alpharoarthajaya@gmail.com
    </div>
    <div>
        @if(!empty($assets['icons'][2]))<img class="footer-icon" src="{{ $assets['icons'][2] }}" alt="">@endif
        Ruko Cibubur Indah Blok B No. 6 Jl. Lapangan Tembak Cibubur - Jakarta Timur, Kode Pos : 13720
    </div>
    <div>NIB : 1712220003498, NPWP : 62.137.251.5-009.000</div>
</div>
</body>
</html>
