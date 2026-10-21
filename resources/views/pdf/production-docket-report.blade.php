<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Produksi Harian {{ $monthName }} {{ $year }} — PT TB Beton Indonesia</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 15mm 12mm 15mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #18181b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 12px;
            border-bottom: 2px solid #18181b;
            padding-bottom: 8px;
        }
        .header-logo {
            width: 130px;
            vertical-align: middle;
        }
        .header-company {
            text-align: right;
            vertical-align: middle;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #18181b;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 8px;
            color: #71717a;
        }
        .title-box {
            text-align: center;
            margin: 10px 0 12px 0;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            color: #18181b;
        }
        .report-period {
            font-size: 11px;
            color: #52525b;
            margin-top: 2px;
        }
        .data-table {
            margin-top: 8px;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #18181b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #18181b;
            text-align: left;
        }
        .data-table td {
            padding: 5px 4px;
            border: 1px solid #e4e4e7;
            font-size: 9px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fbfbfa;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .bg-summary {
            background-color: #f4f4f5 !important;
            font-weight: bold;
        }
        .sign-table {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 30%;
            text-align: center;
            vertical-align: top;
        }
        .sign-space {
            height: 50px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 10px;
        }
        .sign-role {
            font-size: 8.5px;
            color: #71717a;
        }
    </style>
</head>
<body>

    {{-- Kop Surat Resmi TBI --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if (!empty($logo))
                    <img src="{{ $logo }}" style="max-height: 48px; width: auto;" alt="Logo TBI">
                @else
                    <span style="font-weight: bold; font-size: 16px; color: #18181b;">PT TB BETON INDONESIA</span>
                @endif
            </td>
            <td class="header-company">
                <div class="company-name">PT TB BETON INDONESIA</div>
                <div class="company-sub">BATCHING PLANT READY-MIX CONCRETE & DISPATCH OPERASIONAL</div>
                <div class="company-sub">Sistem Operasional Terintegrasi — Divisi Produksi & Quality Control</div>
            </td>
        </tr>
    </table>

    {{-- Judul Laporan --}}
    <div class="title-box">
        <h1 class="report-title">LAPORAN REKAPITULASI PRODUKSI HARIAN & DISPATCH READY MIX</h1>
        <div class="report-period">
            Periode: <strong>{{ $monthName }} {{ $year }}</strong> | Dicetak oleh: <strong>{{ $printedBy }}</strong> pada {{ $printedAt }}
        </div>
    </div>

    {{-- Tabel Data Produksi --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">NO</th>
                <th style="width: 75px;">NO. DOCKET</th>
                <th style="width: 70px;">TANGGAL</th>
                <th style="width: 55px;">JAM</th>
                <th style="width: 75px;">REF. SO</th>
                <th>CUSTOMER & PROYEK</th>
                <th style="width: 75px;">MUTU BETON</th>
                <th style="width: 55px;">NO. TM</th>
                <th class="text-right" style="width: 65px;">VOLUME</th>
                <th class="text-right" style="width: 75px;">KUMULATIF</th>
                <th style="width: 70px;">OPERATOR</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totVol = 0;
            @endphp
            @forelse ($dockets as $index => $d)
                @php
                    $v = (float) $d->volume_m3;
                    $totVol += $v;
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono text-bold">{{ $d->docket_no }}</td>
                    <td class="font-mono">{{ $d->production_date ? \Carbon\Carbon::parse($d->production_date)->format('d/m/Y') : '-' }}</td>
                    <td class="font-mono">{{ substr($d->time_load, 0, 5) }}</td>
                    <td class="font-mono">{{ $d->salesOrder->so_number ?? '-' }}</td>
                    <td>
                        <div class="text-bold">{{ $d->customer_name }}</div>
                        <div style="font-size: 8px; color: #71717a;">{{ $d->address ?: '-' }}</div>
                    </td>
                    <td class="font-mono text-bold">{{ $d->mutu_code }}</td>
                    <td class="font-mono">{{ $d->truck_no }}</td>
                    <td class="text-right font-mono text-bold">{{ number_format($v, 2, ',', '.') }} m³</td>
                    <td class="text-right font-mono">{{ number_format((float) $d->daily_cumulative_m3, 2, ',', '.') }} m³</td>
                    <td class="font-mono" style="font-size: 8.5px;">{{ $d->creator?->name ?? 'Operator' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #71717a;">
                        Tidak ada riwayat produksi tiket docket pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($dockets->isNotEmpty())
            <tfoot>
                <tr class="bg-summary">
                    <td colspan="8" class="text-right text-bold">TOTAL VOLUME BETON READY MIX:</td>
                    <td class="text-right font-mono text-bold">{{ number_format($totVol, 2, ',', '.') }} m³</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Lembar Tanda Tangan Resmi --}}
    <table class="sign-table">
        <tr>
            <td class="sign-box">
                <div>Dibuat Oleh,</div>
                <div class="sign-role">Operator Batching Plant</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $printedBy }}</div>
                <div class="sign-role">TBI Dispatch Operasional</div>
            </td>
            <td style="width: 40%;"></td>
            <td class="sign-box">
                <div>Mengetahui,</div>
                <div class="sign-role">Plant Manager / General Superintendent</div>
                <div class="sign-space"></div>
                <div class="sign-name">Plant Manager Operasional</div>
                <div class="sign-role">PT TB BETON INDONESIA</div>
            </td>
        </tr>
    </table>

</body>
</html>
