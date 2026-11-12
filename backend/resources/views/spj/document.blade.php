<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 18mm 17mm; }
        @if($content['school_header'])
        @page { margin: 9mm 14mm 10mm; }
        @endif
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.3; color: #000; }
        .letterhead { text-align: center; padding-bottom: 9pt; margin-bottom: 14pt; border-bottom: 1pt solid #000; }
        .letterhead strong { font-size: 13pt; }
        .letterhead { position: relative; min-height: 15mm; }
        .logo { position: absolute; left: 0; top: 0; width: 15mm; max-height: 20mm; }
        .page-background { position: fixed; left: -17mm; top: -18mm; width: 210mm; height: 297mm; z-index: -1; }
        .brand-space { height: 8mm; }
        .institutional-header { width: 170.039mm; margin: 0 0 0 1.411mm; border: 0; font-family: Arial, Helvetica, sans-serif; }
        .institutional-header td { padding: 1.235mm 1.411mm; text-align: center; vertical-align: top; white-space: normal; line-height: 1.16; }
        .institutional-header .emblem { width: 22.931mm; }
        .institutional-header .identity { width: 147.108mm; }
        .emblem img { max-width: 20mm; max-height: 20mm; }
        .emblem .template-logo { width: 20mm; height: 20mm; }
        .government { font-size: 12pt; font-weight: bold; }
        .department { font-size: 11pt; font-weight: bold; }
        .school-name { font-size: 20pt; font-weight: bold; }
        .school-contact { font-size: 11pt; }
        .school-address { margin-bottom: 8pt; }
        .header-space { height: 12.75pt; }
        .postal-code { text-align: right; font: 9pt/11pt Arial, Helvetica, sans-serif; margin: 0 0 4.5pt; padding: 0; border-bottom: 2.25pt solid #000; }
        h1 { font-size: 13pt; text-align: center; margin: 0 0 14pt; }
        p { margin: 8pt 0; white-space: pre-line; overflow-wrap: break-word; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 10pt; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { padding: 5pt; vertical-align: top; font-size: 9pt; overflow-wrap: break-word; white-space: pre-line; }
        .items th, .items td { border: .5pt solid #555; }
        .items th { background: #f3f3f3; }
        .fields td:first-child { width: 30%; }
        .fields td { padding: 2.5pt 5pt; }
        .totals { width: 65%; margin-left: auto; font-weight: bold; }
        .totals td { padding: 3pt 5pt; }
        .totals td:last-child { width: 40%; text-align: right; }
        .date { text-align: right; margin-top: 18pt; }
        .school-signoff { page-break-inside: avoid; }
        .signatures { page-break-inside: avoid; }
        .signatures td { width: 50%; text-align: center; }
        .signature-space { height: 45pt; }
        .photo { text-align: center; page-break-inside: avoid; }
        .photo img { max-width: 100%; max-height: 130mm; }
    </style>
</head>
<body>
@if($content['school_header'])
    @php($head = $content['institutional_header'])
    <table class="institutional-header" style="width:170.039mm; table-layout:fixed; margin-left:1.411mm"><colgroup><col style="width:13.4855%"><col style="width:86.5145%"></colgroup><tr>
        <td class="emblem" style="width:13.4855%">@if(!empty($letterhead['url']))<img class="{{ !empty($letterhead['template_logo']) ? 'template-logo' : '' }}" src="{{ $letterhead['url'] }}" alt="Logo instansi">@endif</td>
        <td class="identity" style="width:86.5145%">
            <div class="government">{{ $head['government'] ?: "\u{00A0}" }}</div>
            <div class="department">{{ $head['department'] ?: "\u{00A0}" }}</div>
            <div class="school-name">{{ mb_strtoupper($head['name']) }}</div>
            <div class="school-contact school-address">{{ $head['address'] ?: "\u{00A0}" }}</div>
            <div class="school-contact">{{ $head['contacts'] ?: "\u{00A0}" }}</div>
            <div class="school-contact">{{ $head['online'] ?: "\u{00A0}" }}</div>
            <div class="header-space"></div>
        </td>
    </tr></table>
    <p class="postal-code">Kode Pos : {{ $head['postal_code'] }}</p>
@elseif($letterhead && $letterhead['background'])
    <img class="page-background" src="{{ $letterhead['url'] }}" alt="">
    <div class="brand-space"></div>
@else
    <div class="letterhead">@if($letterhead)<img class="logo" src="{{ $letterhead['url'] }}" alt="">@endif<strong>{{ $content['letterhead'] }}</strong>@if($content['address'])<div>{{ $content['address'] }}</div>@endif</div>
@endif
<h1>{{ $content['title'] }}</h1>
@foreach($content['blocks'] as $block)
    @if($block['type'] === 'paragraph')
        <p>{{ $block['text'] }}</p>
    @elseif($block['type'] === 'image')
        <div class="photo">@if(!empty($block['caption']))<p>{{ $block['caption'] }}</p>@endif<img src="data:{{ $block['mime'] }};base64,{{ $block['data'] }}" alt="Dokumentasi pekerjaan"></div>
    @else
        <table class="{{ $block['type'] === 'table' ? 'items' : $block['type'] }}">
            @if($block['type'] === 'table')
                @php($weights = match(count($block['headers'])) { 4 => [7, 65, 14, 14], 6 => [6, 36, 10, 10, 19, 19], 7 => [5, 27, 9, 9, 17, 17, 16] })
                <colgroup>@foreach($weights as $weight)<col style="width: {{ $weight }}%">@endforeach</colgroup>
            @endif
            @if(isset($block['headers']))
                <thead><tr>@foreach($block['headers'] as $index => $header)<th style="width: {{ $weights[$index] }}%">{{ $header }}</th>@endforeach</tr></thead>
            @endif
            <tbody>@foreach($block['rows'] as $row)<tr>@foreach($row as $index => $cell)<td @if($block['type'] === 'table')style="width: {{ $weights[$index] }}%"@endif>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    @endif
@endforeach
@if($content['school_header'])<div class="school-signoff">@endif
<p class="date">{{ trim($content['city'].', '.$content['date'], ', ') }}</p>
<table class="signatures"><tr>
@foreach($content['signatures'] as $signature)
    <td>{{ $signature['label'] }}<div class="signature-space"></div><strong>{{ $signature['name'] }}</strong><br>{{ $signature['title'] }}@if($signature['identity'])<br>NIP. {{ $signature['identity'] }}@endif</td>
@endforeach
</tr></table>
@if($content['school_header'])</div>@endif
</body>
</html>
