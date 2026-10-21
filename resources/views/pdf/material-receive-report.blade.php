<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penerimaan Material {{ $monthName }} {{ $year }} — PT TB Beton Indonesia</title>
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
                <div class="company-sub">BATCHING PLANT READY-MIX CONCRETE & LOGISTIK BAHAN BAKU</div>
                <div class="company-sub">Sistem Operasional Terintegrasi — Divisi Gudang & Penerimaan Material</div>
            </td>
        </tr>
    </table>

    {{-- Judul Laporan --}}
    <div class="title-box">
        <h1 class="report-title">LAPORAN REKAPITULASI PENERIMAAN MATERIAL TRUK</h1>
        <div class="report-period">
            Periode: <strong>{{ $monthName }} {{ $year }}</strong> | Dicetak oleh: <strong>{{ $printedBy }}</strong> pada {{ $printedAt }}
        </div>
    </div>

    {{-- Tabel Data Penerimaan Material --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">NO</th>
                <th style="width: 70px;">TANGGAL</th>
                <th style="width: 85px;">NO. RI</th>
                <th style="width: 100px;">REF. NO. PO</th>
                <th style="width: 80px;">NO. POLISI</th>
                <th>SUPPLIER / VENDOR</th>
                <th>JENIS MATERIAL</th>
                <th class="text-right" style="width: 75px;">VOL (M³)</th>
                <th class="text-right" style="width: 75px;">DENSITY</th>
                <th class="text-right" style="width: 95px;">TOTAL BERAT (KG)</th>
                <th class="text-right" style="width: 75px;">TONASE</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totVol = 0;
                $totKg = 0;
            @endphp
            @forelse ($receives as $index => $rec)
                @php
                    $v = (float) $rec->volume_m3;
                    $kg = (float) $rec->total_weight_kg;
                    $totVol += $v;
                    $totKg += $kg;
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $rec->receive_date ? $rec->receive_date->format('d/m/Y') : '-' }}</td>
                    <td class="font-mono text-bold">{{ $rec->ri_no }}</td>
                    <td class="font-mono">{{ $rec->purchaseOrder->po_number ?? '-' }}</td>
                    <td class="font-mono">{{ $rec->vehicle_no }}</td>
                    <td>{{ $rec->supplier->name ?? '-' }}</td>
                    <td>{{ $rec->material->name ?? '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($v, 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format((float) $rec->density_used, 1, ',', '.') }}</td>
                    <td class="text-right font-mono text-bold">{{ number_format($kg, 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format($kg / 1000, 2, ',', '.') }} Ton</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #71717a;">
                        Tidak ada catatan penerimaan material pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($receives->isNotEmpty())
            <tfoot>
                <tr class="bg-summary">
                    <td colspan="7" class="text-right text-bold">TOTAL PENERIMAAN KESELURUHAN:</td>
                    <td class="text-right font-mono text-bold">{{ number_format($totVol, 2, ',', '.') }} m³</td>
                    <td></td>
                    <td class="text-right font-mono text-bold">{{ number_format($totKg, 2, ',', '.') }} Kg</td>
                    <td class="text-right font-mono text-bold">{{ number_format($totKg / 1000, 2, ',', '.') }} Ton</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Lembar Tanda Tangan Resmi --}}
    <table class="sign-table">
        <tr>
            <td class="sign-box">
                <div>Dicatat Oleh,</div>
                <div class="sign-role">Petugas Gudang & Timbangan</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $printedBy }}</div>
                <div class="sign-role">TBI Batching Plant</div>
            </td>
            <td style="width: 40%;"></td>
            <td class="sign-box">
                <div>Mengetahui,</div>
                <div class="sign-role">Kepala Logistik & Pengadaan</div>
                <div class="sign-space"></div>
                <div class="sign-name">Plant Manager / Logistik</div>
                <div class="sign-role">PT TB BETON INDONESIA</div>
            </td>
        </tr>
    </table>

</body>
</html>
