<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $po->po_number }} — PT TB Beton Indonesia</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
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
            margin-bottom: 15px;
            border-bottom: 2px solid #18181b;
            padding-bottom: 12px;
        }
        .header-logo {
            width: 140px;
            vertical-align: middle;
        }
        .header-company {
            text-align: right;
            vertical-align: middle;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #18181b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 9px;
            color: #71717a;
            margin-top: 2px;
        }
        .doc-title-box {
            text-align: center;
            margin: 15px 0 20px 0;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #18181b;
            margin: 0;
        }
        .doc-number {
            font-size: 12px;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #27272a;
            margin-top: 4px;
        }
        .meta-table {
            margin-bottom: 15px;
        }
        .meta-box {
            border: 1px solid #e4e4e7;
            background-color: #fafafa;
            padding: 10px;
            vertical-align: top;
            width: 48%;
            border-radius: 4px;
        }
        .meta-box-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #71717a;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .meta-row {
            margin-bottom: 4px;
        }
        .meta-label {
            width: 100px;
            color: #71717a;
            display: inline-block;
        }
        .meta-val {
            font-weight: 600;
            color: #18181b;
        }
        .items-table {
            margin-top: 15px;
            margin-bottom: 20px;
            border: 1px solid #d4d4d8;
        }
        .items-table th {
            background-color: #f4f4f5;
            color: #27272a;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border: 1px solid #d4d4d8;
            text-align: left;
        }
        .items-table td {
            padding: 9px 10px;
            border: 1px solid #e4e4e7;
            font-size: 11px;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .logistics-box {
            margin-bottom: 15px;
            border: 1px dashed #d4d4d8;
            padding: 8px 12px;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .terms-box {
            margin-top: 15px;
            border-left: 3px solid #71717a;
            padding-left: 10px;
            font-size: 9px;
            color: #52525b;
            line-height: 1.5;
        }
        .signatures-table {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signature-cell {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-role {
            font-size: 9px;
            color: #71717a;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 60px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 11px;
            color: #18181b;
            border-bottom: 1px solid #18181b;
            display: inline-block;
            min-width: 140px;
            padding-bottom: 2px;
        }
        .signature-title {
            font-size: 9px;
            color: #71717a;
            margin-top: 3px;
        }
        .footer-note {
            margin-top: 25px;
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
                    <img src="{{ $logo }}" style="height: 48px; width: auto;" alt="TB Beton Indonesia">
                @else
                    <div style="font-size: 20px; font-weight: bold; color: #18181b;">TB BETON</div>
                @endif
            </td>
            <td class="header-company">
                <div class="company-name">PT TB BETON INDONESIA</div>
                <div class="company-sub">
                    Divisi Pengadaan & Logistik Batching Plant Ready-Mix<br>
                    Jl. Poros Palu - Donggala, Sulawesi Tengah | Email: logistik@tbi.com
                </div>
            </td>
        </tr>
    </table>

    {{-- Judul Surat --}}
    <div class="doc-title-box">
        <h1 class="doc-title">PURCHASE ORDER (PO)</h1>
        <div class="doc-number">NO: {{ $po->po_number }}</div>
    </div>

    {{-- Metadata 2 Kolom (Informasi PO & Supplier) --}}
    <table class="meta-table">
        <tr>
            <td class="meta-box">
                <div class="meta-box-title">Kepada (Supplier / Vendor):</div>
                <div style="font-size: 13px; font-weight: bold; color: #18181b; margin-bottom: 4px;">
                    {{ $po->supplier?->name ?? 'SUPPLIER UMUM' }}
                </div>
                <div style="color: #52525b; font-size: 10px;">
                    PIC / Kontak: {{ $po->supplier?->contact_person ?? '-' }}<br>
                    Telepon: {{ $po->supplier?->phone ?? '-' }}<br>
                    Alamat: {{ $po->supplier?->address ?? 'Alamat terdaftar di sistem' }}
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="meta-box">
                <div class="meta-box-title">Informasi Pesanan & Tanggal:</div>
                <div class="meta-row">
                    <span class="meta-label">Tanggal PO:</span>
                    <span class="meta-val font-mono">{{ $po->po_date ? $po->po_date->translatedFormat('d F Y') : '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Status PO:</span>
                    <span class="meta-val" style="text-transform: uppercase;">{{ str_replace('_', ' ', $po->status) }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Est. Berangkat:</span>
                    <span class="meta-val font-mono">{{ $po->est_departure_date ? $po->est_departure_date->translatedFormat('d M Y') : '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Est. Tiba:</span>
                    <span class="meta-val font-mono">{{ $po->est_arrival_date ? $po->est_arrival_date->translatedFormat('d M Y') : '-' }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Catatan Logistik Armada (Jika Pengiriman Kapal/Truk) --}}
    @if ($po->vessel_name || $po->loading_jetty || $po->unloading_jetty)
        <div class="logistics-box">
            <span style="font-weight: bold; text-transform: uppercase; font-size: 9px; color: #71717a;">Informasi Logistik & Pengiriman:</span>
            <div style="margin-top: 3px; font-size: 10px; color: #27272a;">
                Armada / Kapal: <strong>{{ $po->vessel_name ?? '-' }}</strong> |
                Pelabuhan/Jetty Muat: <strong>{{ $po->loading_jetty ?? '-' }}</strong> |
                Jetty Bongkar / Tujuan: <strong>{{ $po->unloading_jetty ?? 'Jetty TBI Plant' }}</strong>
            </div>
        </div>
    @endif

    {{-- Tabel Rincian Material Pesanan --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Deskripsi Material</th>
                <th style="width: 140px;">Spesifikasi / Mutu</th>
                <th style="width: 80px;" class="text-right">Kuantitas</th>
                <th style="width: 60px;" class="text-center">Satuan</th>
                @if ($po->grand_total > 0)
                    <th style="width: 90px;" class="text-right">Harga Satuan</th>
                    <th style="width: 100px;" class="text-right">Total (Rp)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center font-mono">1</td>
                <td>
                    <strong style="color: #09090b; font-size: 12px;">{{ $po->material_type_input ?? $po->material?->name ?? 'Material Pesanan' }}</strong>
                    <div style="color: #71717a; font-size: 9px; margin-top: 2px;">
                        Alokasi Master: {{ $po->material?->name ?? 'Material Utama' }}
                    </div>
                </td>
                <td style="color: #3f3f46;">
                    {{ $po->specification ?? 'Standar Batching Plant TBI' }}
                </td>
                <td class="text-right font-mono" style="font-weight: bold; font-size: 12px;">
                    {{ number_format((float) $po->qty_ordered, 2, ',', '.') }}
                </td>
                <td class="text-center font-mono" style="font-weight: bold;">
                    {{ $po->unit ?? 'Kg' }}
                </td>
                @if ($po->grand_total > 0)
                    <td class="text-right font-mono">
                        {{ number_format((float) $po->unit_price, 2, ',', '.') }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: bold;">
                        {{ number_format((float) $po->grand_total, 2, ',', '.') }}
                    </td>
                @endif
            </tr>
        </tbody>
        @if ($po->grand_total > 0)
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right" style="font-weight: bold; background-color: #fafafa;">GRAND TOTAL</td>
                    <td colspan="2" class="text-right font-mono" style="font-weight: bold; font-size: 12px; background-color: #fafafa;">
                        Rp {{ number_format((float) $po->grand_total, 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Syarat & Ketentuan Umum --}}
    <div class="terms-box">
        <strong>Ketentuan Pengadaan & Pengiriman:</strong><br>
        1. Material yang dikirimkan wajib disertai Surat Jalan (DO) asli dan bukti timbangan jembatan resmi.<br>
        2. Kualitas material harus memenuhi spesifikasi teknis operasional PT TB Beton Indonesia.<br>
        3. Pembongkaran material di lokasi pabrik/jetty wajib diverifikasi oleh Petugas Gudang / Quality Control TBI Plant.
    </div>

    {{-- Kolom Tanda Tangan 3 Pihak --}}
    <table class="signatures-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-role">Dibuat Oleh:</div>
                <div class="signature-name">{{ $printedBy }}</div>
                <div class="signature-title">Kasir / Bagian Pengadaan</div>
            </td>
            <td class="signature-cell">
                <div class="signature-role">Disetujui Oleh:</div>
                <div class="signature-name">Pimpinan Operasional</div>
                <div class="signature-title">Plant Manager TBI</div>
            </td>
            <td class="signature-cell">
                <div class="signature-role">Dikonfirmasi & Diterima:</div>
                <div class="signature-name">{{ $po->supplier?->name ?? 'Supplier / Vendor' }}</div>
                <div class="signature-title">Tanda Tangan & Cap Vendor</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen resmi dicetak otomatis melalui TBI-ERP System pada {{ $printedAt }} WITA · Lembar Asli Vendor
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
