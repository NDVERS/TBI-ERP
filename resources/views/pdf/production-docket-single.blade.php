<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan Docket {{ $docket->docket_no }} — PT TB Beton Indonesia</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
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
            border-bottom: 2px solid #18181b;
            padding-bottom: 8px;
            margin-bottom: 12px;
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
        .ticket-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 6px 0 12px 0;
            color: #18181b;
        }
        .info-grid {
            margin-bottom: 12px;
            border: 1px solid #d4d4d8;
            background-color: #fafafa;
        }
        .info-grid td {
            padding: 6px 8px;
            vertical-align: top;
            font-size: 9.5px;
        }
        .info-label {
            color: #71717a;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: bold;
            color: #18181b;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .batch-table {
            margin-bottom: 12px;
        }
        .batch-table th {
            background-color: #18181b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #18181b;
            text-align: left;
        }
        .batch-table td {
            padding: 5px 6px;
            border: 1px solid #e4e4e7;
            font-size: 9px;
        }
        .sign-table {
            margin-top: 15px;
            border: 1px solid #d4d4d8;
        }
        .sign-table th {
            background-color: #f4f4f5;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 5px;
            border: 1px solid #d4d4d8;
            text-align: center;
        }
        .sign-table td {
            height: 60px;
            border: 1px solid #d4d4d8;
            vertical-align: bottom;
            text-align: center;
            padding-bottom: 6px;
            font-size: 9px;
            font-weight: bold;
        }
        .copy-notice {
            margin-top: 8px;
            font-size: 8px;
            color: #a1a1aa;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
        }
        .dashed-divider {
            margin: 18px 0;
            border-top: 1px dashed #a1a1aa;
        }
    </style>
</head>
<body>

    {{-- Header Kop --}}
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if (!empty($logo))
                    <img src="{{ $logo }}" style="max-height: 44px; width: auto;" alt="Logo TBI">
                @else
                    <span style="font-weight: bold; font-size: 15px; color: #18181b;">PT TB BETON INDONESIA</span>
                @endif
            </td>
            <td class="header-company">
                <div class="company-name">PT TB BETON INDONESIA</div>
                <div class="company-sub">BATCHING PLANT READY-MIX CONCRETE & DISPATCH OPERASIONAL</div>
                <div class="company-sub">Surat Jalan / Delivery Ticket Resmi Pengiriman Beton Cor Siap Pakai</div>
            </td>
        </tr>
    </table>

    <div class="ticket-title">
        SURAT JALAN PENGIRIMAN BETON READY MIX
    </div>

    {{-- Grid Identitas --}}
    <table class="info-grid">
        <tr>
            <td style="width: 25%; border-right: 1px solid #e4e4e7; border-bottom: 1px solid #e4e4e7;">
                <div class="info-label">Nomor Surat Jalan / Docket</div>
                <div class="info-value font-mono" style="font-size: 13px;">{{ $docket->docket_no }}</div>
            </td>
            <td style="width: 25%; border-right: 1px solid #e4e4e7; border-bottom: 1px solid #e4e4e7;">
                <div class="info-label">Tanggal & Jam Dispatch</div>
                <div class="info-value font-mono">
                    {{ $docket->production_date ? \Carbon\Carbon::parse($docket->production_date)->format('d F Y') : '-' }}
                    ({{ substr($docket->time_load, 0, 5) }} WITA)
                </div>
            </td>
            <td style="width: 25%; border-right: 1px solid #e4e4e7; border-bottom: 1px solid #e4e4e7;">
                <div class="info-label">Armada Truck Mixer (TM)</div>
                <div class="info-value font-mono" style="font-size: 12px;">{{ $docket->truck_no }}</div>
            </td>
            <td style="width: 25%; border-bottom: 1px solid #e4e4e7;">
                <div class="info-label">Volume Muat</div>
                <div class="info-value font-mono" style="font-size: 13px;">{{ number_format((float) $docket->volume_m3, 2, ',', '.') }} m³</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-right: 1px solid #e4e4e7;">
                <div class="info-label">Customer / Perusahaan Pemesan</div>
                <div class="info-value" style="font-size: 11px;">{{ $docket->customer_name }}</div>
                @if ($docket->salesOrder)
                    <div style="font-size: 8.5px; color: #52525b; margin-top: 2px;">
                        Referensi SO: <strong class="font-mono">{{ $docket->salesOrder->so_number }}</strong>
                    </div>
                @endif
            </td>
            <td colspan="2">
                <div class="info-label">Lokasi Proyek / Tujuan Pengecoran</div>
                <div class="info-value">{{ $docket->address ?: 'Sesuai Arahan Pengawas Lapangan' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-right: 1px solid #e4e4e7; border-top: 1px solid #e4e4e7;">
                <div class="info-label">Mutu Beton Dipesan</div>
                <div class="info-value font-mono" style="font-size: 13px;">{{ $docket->mutu_code }}</div>
            </td>
            <td colspan="2" style="border-top: 1px solid #e4e4e7;">
                <div class="info-label">Kumulatif Harian Proyek</div>
                <div class="info-value font-mono">{{ number_format((float) $docket->daily_cumulative_m3, 2, ',', '.') }} m³</div>
            </td>
        </tr>
    </table>

    {{-- Komposisi Batching Aktual --}}
    <div style="font-size: 9px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">
        Rincian Timbangan Aktual Batching Plant:
    </div>
    <table class="batch-table">
        <thead>
            <tr>
                <th>Semen (Silo 1)</th>
                <th>Pasir Palu</th>
                <th>Pasir Lokal</th>
                <th>Split 1/2</th>
                <th>Split 2/3</th>
                <th>Air (Water)</th>
                <th>Admixture (SBTJM2)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="font-mono" style="text-align: center;">
                <td>{{ number_format((float) $docket->silo1_act, 0, ',', '.') }} kg</td>
                <td>{{ number_format((float) $docket->pasir_palu_act, 0, ',', '.') }} kg</td>
                <td>{{ number_format((float) $docket->pasir_lokal_act, 0, ',', '.') }} kg</td>
                <td>{{ number_format((float) $docket->split_1_2_act, 0, ',', '.') }} kg</td>
                <td>{{ number_format((float) $docket->split_2_3_act, 0, ',', '.') }} kg</td>
                <td>{{ number_format((float) $docket->water_act, 0, ',', '.') }} L</td>
                <td>{{ number_format((float) $docket->admixture_act, 1, ',', '.') }} L</td>
            </tr>
        </tbody>
    </table>

    {{-- Lembar Tanda Tangan 4 Pihak --}}
    <table class="sign-table">
        <thead>
            <tr>
                <th style="width: 25%;">Dibuat Oleh (Plant)</th>
                <th style="width: 25%;">Pengemudi (Driver TM)</th>
                <th style="width: 25%;">Pengawas QC / Lab</th>
                <th style="width: 25%;">Diterima (Proyek)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>( {{ $docket->creator?->name ?? 'Operator Plant' }} )</td>
                <td>( .................................... )</td>
                <td>( .................................... )</td>
                <td>( .................................... )</td>
            </tr>
        </tbody>
    </table>

    <div class="copy-notice">
        Lembar 1: Arsip Plant / Pembukuan | Lembar 2: Driver Truck Mixer | Lembar 3: Penerima Lapangan | Lembar 4: Tagihan / Keuangan
    </div>

</body>
</html>
