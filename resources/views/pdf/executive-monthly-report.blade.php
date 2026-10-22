<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Eksekutif Bulanan {{ $monthName }} {{ $year }} — PT TB Beton Indonesia</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 12mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
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
            margin-bottom: 10px;
            border-bottom: 2px solid #18181b;
            padding-bottom: 6px;
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
            font-size: 13px;
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
            margin: 6px 0 10px 0;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            color: #18181b;
        }
        .report-period {
            font-size: 10px;
            color: #52525b;
            margin-top: 2px;
        }

        /* KPI Cards Grid */
        .kpi-table {
            margin-bottom: 12px;
        }
        .kpi-card {
            border: 1px solid #e4e4e7;
            background-color: #f8fafc;
            padding: 8px 10px;
            border-radius: 4px;
            vertical-align: top;
            width: 25%;
        }
        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 3px 0 2px 0;
        }
        .kpi-sub {
            font-size: 8px;
            color: #64748b;
        }

        /* Section Headings */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-left: 3px solid #18181b;
            padding-left: 6px;
            margin: 10px 0 6px 0;
        }

        /* Data Tables */
        .data-table {
            margin-top: 4px;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #18181b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            border: 1px solid #18181b;
            text-align: left;
        }
        .data-table td {
            padding: 4px 4px;
            border: 1px solid #e4e4e7;
            font-size: 8.5px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .data-table .total-row td {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            border-top: 1px solid #94a3b8;
            border-bottom: 2px solid #0f172a;
        }

        /* Helpers */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: bold; }
        
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-green { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-amber { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-red { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-zinc { background-color: #f4f4f5; color: #52525b; border: 1px solid #e4e4e7; }

        /* Signatures */
        .sign-table {
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .sign-cell {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .sign-space {
            height: 45px;
        }
        .sign-name {
            font-weight: bold;
            border-bottom: 1px solid #18181b;
            padding-bottom: 2px;
            display: inline-block;
            min-width: 140px;
        }
        .sign-role {
            font-size: 8px;
            color: #71717a;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    {{-- Header Perusahaan & Logo --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if(!empty($logo))
                    <img src="{{ $logo }}" style="max-height: 42px; width: auto;" alt="Logo TBI">
                @else
                    <span style="font-size: 14px; font-weight: bold; color: #e11d48;">PT TBI</span>
                @endif
            </td>
            <td class="header-company">
                <div class="company-name">PT TB Beton Indonesia</div>
                <div class="company-sub">Ready-Mix Concrete • Stone Quarry • Heavy Equipment Logistics</div>
                <div class="company-sub">Dokumen Resmi Eksekutif & Pengawasan Mutu Batching Plant</div>
            </td>
        </tr>
    </table>

    {{-- Judul Laporan --}}
    <div class="title-box">
        <h1 class="report-title">Ringkasan Eksekutif Operasional & Logistik Bulanan</h1>
        <div class="report-period">
            Periode: <strong>{{ $monthName }} {{ $year }}</strong> &nbsp;|&nbsp; Dicetak Oleh: <strong>{{ $printedBy }}</strong> &nbsp;|&nbsp; Waktu: {{ $printedAt }}
        </div>
    </div>

    {{-- KPI Highlights Grid --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="padding-right: 6px;">
                <div class="kpi-label">Kumulatif Produksi Ready-Mix</div>
                <div class="kpi-value font-mono">{{ number_format($totalProductionVolume, 2, ',', '.') }} <span style="font-size: 10px; font-weight: normal;">m³</span></div>
                <div class="kpi-sub">Total <strong>{{ number_format($totalDocketCount) }}</strong> ritase pengiriman mixer</div>
            </td>
            <td class="kpi-card" style="padding-left: 6px; padding-right: 6px;">
                <div class="kpi-label">Penerimaan Material Inbound</div>
                <div class="kpi-value font-mono">{{ number_format($totalMaterialInKg / 1000, 2, ',', '.') }} <span style="font-size: 10px; font-weight: normal;">Ton</span></div>
                <div class="kpi-sub">Total <strong>{{ number_format($totalReceiveTrips) }}</strong> truk dump masuk plant</div>
            </td>
            <td class="kpi-card" style="padding-left: 6px; padding-right: 6px;">
                <div class="kpi-label">Integritas Stok Rekonsiliasi</div>
                <div class="kpi-value font-mono" style="color: {{ $kekuranganCount > 0 ? '#b91c1c' : '#15803d' }};">
                    {{ $sesuaiCount }} Sesuai
                </div>
                <div class="kpi-sub">
                    <span style="color: #b45309;">{{ $kelebihanCount }} Surplus</span> &bull; 
                    <span style="color: #b91c1c;">{{ $kekuranganCount }} Defisit</span>
                </div>
            </td>
            <td class="kpi-card" style="padding-left: 6px;">
                <div class="kpi-label">Belanja Logistik (PO)</div>
                <div class="kpi-value font-mono">Rp {{ number_format($poTotalSpend, 0, ',', '.') }}</div>
                <div class="kpi-sub">Total <strong>{{ number_format($poCount) }}</strong> pengadaan material</div>
            </td>
        </tr>
    </table>

    {{-- 1. TABEL REKONSILIASI STOK MATERIAL --}}
    <div class="section-title">1. Rekonsiliasi & Integritas Stok Material Plant</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 24px;" class="text-center">No</th>
                <th style="width: 75px;">ID Material</th>
                <th>Nama Material</th>
                <th style="width: 45px;" class="text-center">Satuan</th>
                <th style="width: 70px;" class="text-right">Stok Awal</th>
                <th style="width: 70px;" class="text-right">Penerimaan (+)</th>
                <th style="width: 70px;" class="text-right">Pemakaian (-)</th>
                <th style="width: 75px;" class="text-right">Stok Sistem</th>
                <th style="width: 75px;" class="text-right">Fisik Opname</th>
                <th style="width: 65px;" class="text-right">Selisih</th>
                <th style="width: 55px;" class="text-right">Deviasi</th>
                <th style="width: 75px;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totAwal = 0; $totIn = 0; $totOut = 0; $totTeoritis = 0;
            @endphp
            @forelse($reconciliations as $idx => $r)
                @php
                    $totAwal += (float) $r['stok_awal'];
                    $totIn += (float) $r['penerimaan'];
                    $totOut += (float) $r['pemakaian'];
                    $totTeoritis += (float) $r['stok_teoritis'];
                    $hasOpname = !empty($r['has_opname']);
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono text-center">{{ $r['material_id'] }}</td>
                    <td class="font-bold">{{ $r['material_name'] }}</td>
                    <td class="text-center">{{ $r['unit'] }}</td>
                    <td class="text-right font-mono">{{ number_format((float) $r['stok_awal'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format((float) $r['penerimaan'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">{{ number_format((float) $r['pemakaian'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format((float) $r['stok_teoritis'], 2, ',', '.') }}</td>
                    <td class="text-right font-mono">
                        {{ $hasOpname ? number_format((float) $r['stok_fisik'], 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono">
                        {{ $hasOpname ? number_format((float) $r['selisih'], 2, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono">
                        {{ $hasOpname ? number_format((float) $r['persentase_selisih'], 2, ',', '.') . '%' : '-' }}
                    </td>
                    <td class="text-center">
                        @if($r['status'] === 'SESUAI')
                            <span class="badge badge-green">SESUAI</span>
                        @elseif($r['status'] === 'KELEBIHAN')
                            <span class="badge badge-amber">KELEBIHAN</span>
                        @elseif($r['status'] === 'KEKURANGAN')
                            <span class="badge badge-red">KEKURANGAN</span>
                        @else
                            <span class="badge badge-zinc">{{ $r['status'] }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 10px; color: #a1a1aa;">Tidak ada data rekonsiliasi material pada periode ini.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-right font-bold">TOTAL REKAPITULASI (KG)</td>
                <td class="text-right font-mono">{{ number_format($totAwal, 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ number_format($totIn, 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ number_format($totOut, 2, ',', '.') }}</td>
                <td class="text-right font-mono">{{ number_format($totTeoritis, 2, ',', '.') }}</td>
                <td colspan="4"></td>
            </tr>
        </tbody>
    </table>

    {{-- 2 Sub-Tabel Side-by-Side: Distribusi Mutu & Arus Material --}}
    <table style="width: 100%; margin-top: 6px;">
        <tr>
            {{-- Tabel Distribusi Mutu Beton --}}
            <td style="width: 49%; vertical-align: top; padding-right: 6px;">
                <div class="section-title">2. Distribusi Produksi Mutu Beton Cor</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;" class="text-center">No</th>
                            <th>Kode Mutu</th>
                            <th style="width: 55px;" class="text-center">Ritase</th>
                            <th style="width: 75px;" class="text-right">Volume (m³)</th>
                            <th style="width: 55px;" class="text-right">Porsi (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mutuDistribution as $i => $m)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="font-bold">{{ $m['mutu'] }}</td>
                                <td class="text-center font-mono">{{ $m['count'] }}</td>
                                <td class="text-right font-mono font-bold">{{ number_format($m['volume_m3'], 2, ',', '.') }}</td>
                                <td class="text-right font-mono">{{ number_format($m['percentage'], 1, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center" style="color: #a1a1aa;">Belum ada produksi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            {{-- Tabel Arus Masuk Material Inbound --}}
            <td style="width: 49%; vertical-align: top; padding-left: 6px;">
                <div class="section-title">3. Ringkasan Penerimaan Material Inbound Truk</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;" class="text-center">No</th>
                            <th>Nama Material</th>
                            <th style="width: 55px;" class="text-center">Ritase</th>
                            <th style="width: 80px;" class="text-right">Berat (Kg)</th>
                            <th style="width: 70px;" class="text-right">Tonase (Ton)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receiveByMaterial as $i => $rm)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="font-bold">{{ $rm['name'] }}</td>
                                <td class="text-center font-mono">{{ $rm['trips'] }}</td>
                                <td class="text-right font-mono font-bold">{{ number_format($rm['total_weight_kg'], 2, ',', '.') }}</td>
                                <td class="text-right font-mono">{{ number_format($rm['total_weight_kg'] / 1000, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center" style="color: #a1a1aa;">Belum ada penerimaan truk pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- Lembar Pengesahan / Signatures --}}
    <table class="sign-table">
        <tr>
            <td class="sign-cell">
                <div class="company-sub">Dibuat Oleh:</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $printedBy }}</div>
                <div class="sign-role">Kasir / Admin Logistik Plant</div>
            </td>
            <td class="sign-cell">
                <div class="company-sub">Diperiksa Oleh:</div>
                <div class="sign-space"></div>
                <div class="sign-name">Quality Control & Lab Leader</div>
                <div class="sign-role">Kepala Operasional Batching</div>
            </td>
            <td class="sign-cell">
                <div class="company-sub">Disetujui Oleh:</div>
                <div class="sign-space"></div>
                <div class="sign-name">Direktur Operasional</div>
                <div class="sign-role">Manajemen PT TB Beton Indonesia</div>
            </td>
        </tr>
    </table>

</body>
</html>
