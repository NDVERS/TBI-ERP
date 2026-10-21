<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi PO {{ $monthName }} — PT TB Beton Indonesia</title>
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
            font-weight: 600;
        }
        .data-table {
            margin-bottom: 15px;
            border: 1px solid #d4d4d8;
        }
        .data-table th {
            background-color: #f4f4f5;
            color: #27272a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
            padding: 6px 7px;
            border: 1px solid #d4d4d8;
            text-align: left;
        }
        .data-table td {
            padding: 6px 7px;
            border: 1px solid #e4e4e7;
            font-size: 9px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .badge-status {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #d4d4d8;
            background-color: #ffffff;
            color: #3f3f46;
        }
        .status-selesai {
            background-color: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .status-muat, .status-pelayaran, .status-bongkar {
            background-color: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }
        .summary-container {
            margin-top: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .summary-box {
            border: 1px solid #d4d4d8;
            background-color: #fcfcfc;
            padding: 8px 10px;
            border-radius: 4px;
            vertical-align: top;
        }
        .summary-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #71717a;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .signatures-table {
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-role {
            font-size: 9px;
            color: #71717a;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 50px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 11px;
            color: #18181b;
            border-bottom: 1px solid #18181b;
            display: inline-block;
            min-width: 160px;
            padding-bottom: 2px;
        }
        .signature-title {
            font-size: 8px;
            color: #71717a;
            margin-top: 3px;
        }
        .footer-note {
            margin-top: 15px;
            text-align: right;
            font-size: 8px;
            color: #a1a1aa;
            font-family: 'Courier New', Courier, monospace;
        }
    </style>
</head>
<body>

    {{-- Header Perusahaan & Logo --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if ($logo)
                    <img src="{{ $logo }}" style="height: 40px; width: auto;" alt="TB Beton Indonesia">
                @else
                    <div style="font-size: 16px; font-weight: bold; color: #18181b;">TB BETON</div>
                @endif
            </td>
            <td class="header-company">
                <div class="company-name">PT TB BETON INDONESIA</div>
                <div class="company-sub">
                    Sistem ERP Batching Plant · Laporan Audit & Rekapitulasi Pengadaan Material (PO)
                </div>
            </td>
        </tr>
    </table>

    {{-- Judul Laporan --}}
    <div class="title-box">
        <h1 class="report-title">LAPORAN REKAPITULASI PURCHASE ORDER</h1>
        <div class="report-period">Periode: {{ $monthName }}</div>
    </div>

    {{-- Tabel Data Rekapitulasi PO --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 65px;" class="text-center">Tanggal</th>
                <th style="width: 110px;">No. PO</th>
                <th style="width: 140px;">Supplier / Vendor</th>
                <th>Material Dipesan</th>
                <th style="width: 70px;" class="text-right">Qty</th>
                <th style="width: 45px;" class="text-center">Satuan</th>
                <th style="width: 80px;" class="text-center">Status</th>
                <th style="width: 100px;">Armada / Kapal</th>
                <th style="width: 65px;" class="text-center">Est. Tiba</th>
                <th style="width: 65px;" class="text-center">Tiba Aktual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchaseOrders as $index => $item)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="text-center font-mono">{{ $item->po_date ? $item->po_date->format('d/m/Y') : '-' }}</td>
                    <td class="font-mono" style="font-weight: bold; color: #09090b;">{{ $item->po_number }}</td>
                    <td>{{ $item->supplier?->name ?? 'Supplier Umum' }}</td>
                    <td style="font-weight: 600;">{{ $item->material_type_input ?? $item->material?->name ?? '-' }}</td>
                    <td class="text-right font-mono" style="font-weight: bold;">{{ number_format((float) $item->qty_ordered, 2, ',', '.') }}</td>
                    <td class="text-center font-mono">{{ $item->unit ?? 'Kg' }}</td>
                    <td class="text-center">
                        @php
                            $statusClass = match($item->status) {
                                'selesai' => 'status-selesai',
                                'muat_kapal', 'pelayaran', 'bongkar' => 'status-muat',
                                default => 'badge-status',
                            };
                        @endphp
                        <span class="badge-status {{ $statusClass }}">
                            {{ str_replace('_', ' ', $item->status) }}
                        </span>
                    </td>
                    <td>{{ $item->vessel_name ?? '-' }}</td>
                    <td class="text-center font-mono">{{ $item->est_arrival_date ? $item->est_arrival_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-center font-mono">{{ $item->actual_arrival_date ? $item->actual_arrival_date->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; color: #a1a1aa; font-style: italic;">
                        Tidak ada data transaksi Purchase Order yang tercatat pada periode {{ $monthName }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Ringkasan Akumulasi (Summary Box) --}}
    <table class="summary-container">
        <tr>
            {{-- Box 1: Akumulasi per Material --}}
            <td class="summary-box" style="width: 58%;">
                <div class="summary-title">Ringkasan Total Pembelian per Material:</div>
                <table style="width: 100%;">
                    @foreach ($materialSummary as $matName => $data)
                        <tr style="border-bottom: 1px dotted #e4e4e7;">
                            <td style="padding: 3px 0; color: #27272a; font-weight: 500;">
                                • {{ $matName }}:
                            </td>
                            <td style="padding: 3px 0; text-align: right; font-weight: bold; font-family: 'Courier New', monospace;">
                                {{ number_format((float) $data['qty'], 2, ',', '.') }} {{ $data['unit'] }}
                                <span style="font-weight: normal; color: #71717a; font-size: 8px;">({{ $data['count'] }} PO)</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td style="width: 4%;"></td>
            {{-- Box 2: Total Order & Status --}}
            <td class="summary-box" style="width: 38%;">
                <div class="summary-title">Statistik Status Pengadaan:</div>
                <div style="margin-bottom: 4px;">
                    Total Dokumen PO: <strong class="font-mono">{{ $totalOrders }} Pesanan</strong>
                </div>
                <div style="font-size: 9px; color: #52525b; line-height: 1.6;">
                    - Selesai (Tiba di Silo): <strong>{{ $statusSummary['selesai'] }}</strong><br>
                    - Dalam Perjalanan (Logistik): <strong>{{ $statusSummary['muat_kapal'] + $statusSummary['pelayaran'] + $statusSummary['bongkar'] }}</strong><br>
                    - Disetujui / Draft: <strong>{{ $statusSummary['draft'] + $statusSummary['disetujui'] }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tanda Tangan Internal --}}
    <table class="signatures-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-role">Dibuat & Diverifikasi:</div>
                <div class="signature-name">{{ $printedBy }}</div>
                <div class="signature-title">Kasir / Procurement Logistik</div>
            </td>
            <td class="signature-cell">
                <div class="signature-role">Diketahui & Disetujui:</div>
                <div class="signature-name">Pimpinan Operasional</div>
                <div class="signature-title">Plant Manager TBI Plant</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Laporan Rekapitulasi Internal · Dicetak otomatis melalui TBI-ERP System pada {{ $printedAt }} WITA
    </div>

    @if (! empty($isHtmlFallback))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif

</body>
</html>
