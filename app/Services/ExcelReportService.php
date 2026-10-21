<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelReportService
{
    private const FONT_FAMILY = 'Segoe UI';

    private const COLOR_DARK_ZINC = 'FF18181B';

    private const COLOR_SLATE_TITLE = 'FF334155';

    private const COLOR_MUTED = 'FF64748B';

    private const COLOR_BORDER_HEADER = 'FF334155';

    private const COLOR_BORDER_ROW = 'FFE2E8F0';

    private const COLOR_ZEBRA_ROW = 'FFF8FAFC';

    private const COLOR_SUMMARY_BG = 'FFF1F5F9';

    /**
     * Export Stock Reconciliation to a styled Excel spreadsheet.
     * Automatically uses PhpSpreadsheet (.xlsx) if installed, or gracefully falls back
     * to Excel-compatible HTML (.xls) if the vendor package is missing on shared hosting.
     *
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $reconciliations
     */
    public function exportStockReconciliation(
        Collection|array $reconciliations,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        if (class_exists(Spreadsheet::class)) {
            return $this->exportStockReconciliationXlsx($reconciliations, $year, $month, $printedBy);
        }

        return $this->exportStockReconciliationFallback($reconciliations, $year, $month, $printedBy);
    }

    /**
     * Export Purchase Order Monthly Summary Report to a styled Excel spreadsheet.
     *
     * @param  Collection<int, mixed>  $purchaseOrders
     */
    public function exportPurchaseOrderReport(
        Collection $purchaseOrders,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        if (class_exists(Spreadsheet::class)) {
            return $this->exportPurchaseOrderReportXlsx($purchaseOrders, $year, $month, $printedBy);
        }

        return $this->exportPurchaseOrderReportFallback($purchaseOrders, $year, $month, $printedBy);
    }

    /**
     * Full Binary XLSX Export using PhpSpreadsheet for Stock Reconciliation.
     *
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $reconciliations
     */
    protected function exportStockReconciliationXlsx(
        Collection|array $reconciliations,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekonsiliasi Stok');
        $sheet->setShowGridLines(true);

        $spreadsheet->getDefaultStyle()->getFont()->setName(self::FONT_FAMILY)->setSize(10);

        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i').' WITA';
        $user = $printedBy ?: 'Admin Logistik';

        // 1. Table Headers
        $headers = [
            'No',
            'ID Material',
            'Nama Material',
            'Satuan',
            'Stok Awal (Kg)',
            'Penerimaan (+) (Kg)',
            'Pemakaian (-) (Kg)',
            'Stok Akhir Sistem (Kg)',
            'Fisik Opname (Kg)',
            'Deviasi Selisih (Kg)',
            'Deviasi (%)',
            'Status Rekonsiliasi',
        ];

        $lastColLetter = Coordinate::stringFromColumnIndex(count($headers) + 1); // Column M

        // 2. Draw Company Header & Logo
        $this->drawReportHeader(
            sheet: $sheet,
            reportTitle: 'LAPORAN REKONSILIASI STOK MATERIAL',
            metaText: "Periode: {$monthName}   |   Dicetak Oleh: {$user}   |   Waktu: {$printedAt}",
            lastColLetter: $lastColLetter
        );

        $startRow = 6;
        $sheet->getRowDimension($startRow)->setRowHeight(28);

        foreach ($headers as $index => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 2); // Start at Column B
            $sheet->setCellValue("{$colLetter}{$startRow}", $header);
        }

        $headerRange = "B{$startRow}:{$lastColLetter}{$startRow}";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 9.5,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_DARK_ZINC],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_HEADER],
                ],
            ],
        ]);

        // 3. Populate Data Rows
        $currentRow = $startRow + 1;
        $rowIdx = 0;

        foreach ($reconciliations as $row) {
            $rowIdx++;
            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $bgFill = ($rowIdx % 2 === 1) ? 'FFFFFFFF' : self::COLOR_ZEBRA_ROW;
            $hasOpname = ! empty($row['has_opname']);

            $sheet->setCellValue("B{$currentRow}", $rowIdx);
            $sheet->setCellValue("C{$currentRow}", $row['material_id']);
            $sheet->setCellValue("D{$currentRow}", $row['material_name']);
            $sheet->setCellValue("E{$currentRow}", $row['unit']);

            $sheet->setCellValue("F{$currentRow}", (float) $row['stok_awal']);
            $sheet->setCellValue("G{$currentRow}", (float) $row['penerimaan']);
            $sheet->setCellValue("H{$currentRow}", (float) $row['pemakaian']);
            $sheet->setCellValue("I{$currentRow}", (float) $row['stok_teoritis']);

            if ($hasOpname) {
                $sheet->setCellValue("J{$currentRow}", (float) $row['stok_fisik']);
                $sheet->setCellValue("K{$currentRow}", (float) $row['selisih']);
                $sheet->setCellValue("L{$currentRow}", ((float) $row['persentase_selisih']) / 100);
            } else {
                $sheet->setCellValue("J{$currentRow}", 'Belum Diopname');
                $sheet->setCellValue("K{$currentRow}", '-');
                $sheet->setCellValue("L{$currentRow}", '-');
            }

            $sheet->setCellValue("M{$currentRow}", $row['status']);

            // Base cell styles
            $sheet->getStyle("B{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $bgFill],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER_ROW],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Alignment & Number Formats
            $sheet->getStyle("B{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("D{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Numerics
            $sheet->getStyle("F{$currentRow}:I{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("F{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$currentRow}")->getFont()->setBold(true);

            if ($hasOpname) {
                $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("K{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("K{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("L{$currentRow}")->getNumberFormat()->setFormatCode('0.00%');
                $sheet->getStyle("L{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } else {
                $sheet->getStyle("J{$currentRow}:L{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J{$currentRow}")->getFont()->setItalic(true)->getColor()->setARGB(self::COLOR_MUTED);
            }

            // Status Styling
            $sheet->getStyle("M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true)->setSize(9);
            $statusStyle = match ($row['status']) {
                'SESUAI' => ['bg' => 'FFECFDF5', 'fg' => 'FF065F46'],
                'KELEBIHAN' => ['bg' => 'FFFFFBEB', 'fg' => 'FF92400E'],
                'KEKURANGAN' => ['bg' => 'FFFEF2F2', 'fg' => 'FF991B1B'],
                default => ['bg' => 'FFF4F4F5', 'fg' => 'FF71717A'],
            };
            $sheet->getStyle("M{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $statusStyle['bg']],
                ],
                'font' => [
                    'color' => ['argb' => $statusStyle['fg']],
                ],
            ]);

            $currentRow++;
        }

        // 4. Summary Row
        $endDataRow = max($startRow + 1, $currentRow - 1);
        $sheet->getRowDimension($currentRow)->setRowHeight(24);
        $sheet->mergeCells("B{$currentRow}:E{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", 'TOTAL REKAPITULASI');

        if ($rowIdx > 0) {
            $sheet->setCellValue("F{$currentRow}", "=SUM(F7:F{$endDataRow})");
            $sheet->setCellValue("G{$currentRow}", "=SUM(G7:G{$endDataRow})");
            $sheet->setCellValue("H{$currentRow}", "=SUM(H7:H{$endDataRow})");
            $sheet->setCellValue("I{$currentRow}", "=SUM(I7:I{$endDataRow})");
        } else {
            $sheet->setCellValue("F{$currentRow}", 0);
            $sheet->setCellValue("G{$currentRow}", 0);
            $sheet->setCellValue("H{$currentRow}", 0);
            $sheet->setCellValue("I{$currentRow}", 0);
        }

        $sheet->getStyle("B{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['argb' => 'FF0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_SUMMARY_BG],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF94A3B8'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => 'FF0F172A'],
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_ROW],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
        $sheet->getStyle("F{$currentRow}:I{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("F{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 5. Sheet Ergonomics: Auto-width, AutoFilter, Freeze Panes
        $sheet->getColumnDimension('A')->setWidth(3);
        $sheet->getColumnDimension('B')->setWidth(6);
        foreach (range(3, count($headers) + 1) as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $sheet->freezePane('B7');
        $sheet->setAutoFilter("B{$startRow}:{$lastColLetter}{$endDataRow}");

        $fileName = "Rekonsiliasi_Stok_TBI_{$year}_".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.xlsx';

        return $this->streamSpreadsheet($spreadsheet, $fileName);
    }

    /**
     * Full Binary XLSX Export using PhpSpreadsheet for Purchase Orders.
     *
     * @param  Collection<int, mixed>  $purchaseOrders
     */
    protected function exportPurchaseOrderReportXlsx(
        Collection $purchaseOrders,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi PO');
        $sheet->setShowGridLines(true);

        $spreadsheet->getDefaultStyle()->getFont()->setName(self::FONT_FAMILY)->setSize(10);

        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i').' WITA';
        $user = $printedBy ?: 'Kasir Logistik';
        $totalCount = $purchaseOrders->count();

        // 1. Table Headers
        $headers = [
            'No',
            'No. PO',
            'Tanggal PO',
            'Supplier / Vendor',
            'Jenis Material',
            'Spesifikasi',
            'Kuantitas',
            'Satuan',
            'Harga Satuan (Rp)',
            'Subtotal (Rp)',
            'PPN (Rp)',
            'Grand Total (Rp)',
            'Status Logistik',
            'Armada / Kapal',
            'Jetty Muat',
            'Jetty Bongkar',
            'Est. Berangkat',
            'Est. Tiba',
            'Aktual Tiba',
        ];

        $lastColLetter = Coordinate::stringFromColumnIndex(count($headers) + 1); // Column T

        // 2. Draw Company Header & Logo
        $this->drawReportHeader(
            sheet: $sheet,
            reportTitle: 'LAPORAN REKAPITULASI PURCHASE ORDER MATERIAL',
            metaText: "Periode: {$monthName}   |   Total Data: {$totalCount} Dokumen PO   |   Dicetak Oleh: {$user}   |   Waktu: {$printedAt}",
            lastColLetter: $lastColLetter
        );

        $startRow = 6;
        $sheet->getRowDimension($startRow)->setRowHeight(28);

        foreach ($headers as $index => $header) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 2); // Start at Column B
            $sheet->setCellValue("{$colLetter}{$startRow}", $header);
        }

        $headerRange = "B{$startRow}:{$lastColLetter}{$startRow}";

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 9.5,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_DARK_ZINC],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_HEADER],
                ],
            ],
        ]);

        // 3. Populate Rows
        $currentRow = $startRow + 1;
        $rowIdx = 0;

        foreach ($purchaseOrders as $po) {
            $rowIdx++;
            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $bgFill = ($rowIdx % 2 === 1) ? 'FFFFFFFF' : self::COLOR_ZEBRA_ROW;

            $sheet->setCellValue("B{$currentRow}", $rowIdx);
            $sheet->setCellValue("C{$currentRow}", $po->po_number);
            $sheet->setCellValue("D{$currentRow}", $po->po_date ? $po->po_date->format('d/m/Y') : '-');
            $sheet->setCellValue("E{$currentRow}", $po->supplier?->name ?? 'Supplier Umum');
            $sheet->setCellValue("F{$currentRow}", $po->material_type_input ?: ($po->material?->name ?? '-'));
            $sheet->setCellValue("G{$currentRow}", $po->specification ?: '-');

            $sheet->setCellValue("H{$currentRow}", (float) $po->qty_ordered);
            $sheet->setCellValue("I{$currentRow}", $po->unit ?? 'Kg');
            $sheet->setCellValue("J{$currentRow}", (float) ($po->unit_price ?? 0));
            $sheet->setCellValue("K{$currentRow}", (float) ($po->subtotal ?? 0));
            $sheet->setCellValue("L{$currentRow}", (float) ($po->ppn_amount ?? 0));
            $sheet->setCellValue("M{$currentRow}", (float) ($po->grand_total ?? 0));

            $sheet->setCellValue("N{$currentRow}", str_replace('_', ' ', strtoupper((string) $po->status)));
            $sheet->setCellValue("O{$currentRow}", $po->vessel_name ?: '-');
            $sheet->setCellValue("P{$currentRow}", $po->loading_jetty ?: '-');
            $sheet->setCellValue("Q{$currentRow}", $po->unloading_jetty ?: 'Jetty TBI Plant');
            $sheet->setCellValue("R{$currentRow}", $po->est_departure_date ? $po->est_departure_date->format('d/m/Y') : '-');
            $sheet->setCellValue("S{$currentRow}", $po->est_arrival_date ? $po->est_arrival_date->format('d/m/Y') : '-');
            $sheet->setCellValue("T{$currentRow}", $po->actual_arrival_date ? $po->actual_arrival_date->format('d/m/Y') : '-');

            // Cell styling
            $sheet->getStyle("B{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $bgFill],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => self::COLOR_BORDER_ROW],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center Alignments
            $sheet->getStyle("B{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("R{$currentRow}:T{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left Alignments
            $sheet->getStyle("E{$currentRow}:G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->getStyle("E{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("O{$currentRow}:Q{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent(1);

            // Number & Currency Formatting
            $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("J{$currentRow}:M{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("J{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true);

            // Status Badge Formatting
            $sheet->getStyle("N{$currentRow}")->getFont()->setBold(true)->setSize(9);
            $statusStyle = match ($po->status) {
                'selesai' => ['bg' => 'FFECFDF5', 'fg' => 'FF065F46'],
                'disetujui' => ['bg' => 'FFEFF6FF', 'fg' => 'FF1E40AF'],
                'muat_kapal', 'pelayaran', 'bongkar' => ['bg' => 'FFFFFBEB', 'fg' => 'FF92400E'],
                default => ['bg' => 'FFF4F4F5', 'fg' => 'FF52525B'],
            };
            $sheet->getStyle("N{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $statusStyle['bg']],
                ],
                'font' => [
                    'color' => ['argb' => $statusStyle['fg']],
                ],
            ]);

            $currentRow++;
        }

        // 4. Summary Row
        $endDataRow = max($startRow + 1, $currentRow - 1);
        $sheet->getRowDimension($currentRow)->setRowHeight(24);
        $sheet->mergeCells("B{$currentRow}:G{$currentRow}");
        $sheet->setCellValue("B{$currentRow}", 'TOTAL REKAPITULASI');

        if ($rowIdx > 0) {
            $sheet->setCellValue("H{$currentRow}", "=SUM(H7:H{$endDataRow})");
            $sheet->setCellValue("K{$currentRow}", "=SUM(K7:K{$endDataRow})");
            $sheet->setCellValue("L{$currentRow}", "=SUM(L7:L{$endDataRow})");
            $sheet->setCellValue("M{$currentRow}", "=SUM(M7:M{$endDataRow})");
        } else {
            $sheet->setCellValue("H{$currentRow}", 0);
            $sheet->setCellValue("K{$currentRow}", 0);
            $sheet->setCellValue("L{$currentRow}", 0);
            $sheet->setCellValue("M{$currentRow}", 0);
        }

        $sheet->getStyle("B{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['argb' => 'FF0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::COLOR_SUMMARY_BG],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF94A3B8'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => 'FF0F172A'],
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::COLOR_BORDER_ROW],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);

        $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("K{$currentRow}:M{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("K{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 5. Sheet Ergonomics: Auto-width, AutoFilter, Freeze Panes
        $sheet->getColumnDimension('A')->setWidth(3);
        $sheet->getColumnDimension('B')->setWidth(6);
        foreach (range(3, count($headers) + 1) as $colIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $sheet->freezePane('B7');
        $sheet->setAutoFilter("B{$startRow}:{$lastColLetter}{$endDataRow}");

        $fileName = "Rekap_PO_TBI_{$year}_".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.xlsx';

        return $this->streamSpreadsheet($spreadsheet, $fileName);
    }

    /**
     * Fallback Excel (.xls) HTML format for Purchase Order Report.
     * Works with ZERO third-party dependencies (no PhpSpreadsheet required).
     *
     * @param  Collection<int, mixed>  $purchaseOrders
     */
    protected function exportPurchaseOrderReportFallback(
        Collection $purchaseOrders,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i').' WITA';
        $user = $printedBy ?: 'Kasir Logistik';
        $totalCount = $purchaseOrders->count();
        $fileName = "Rekap_PO_TBI_{$year}_".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.xls';

        $logoBase64 = $this->getLogoBase64();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ];

        return response()->streamDownload(function () use ($purchaseOrders, $monthName, $printedAt, $user, $totalCount, $logoBase64) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rekapitulasi PO</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>
                body, table { font-family: "Segoe UI", Calibri, Arial, sans-serif; font-size: 10pt; }
                .title { font-size: 13pt; font-weight: bold; color: #18181B; }
                .subtitle { font-size: 10pt; font-weight: bold; color: #334155; }
                .meta { font-size: 9pt; color: #64748B; }
                th { background-color: #18181B; color: #FFFFFF; font-weight: bold; text-align: center; border: 1px solid #334155; padding: 6px 10px; }
                td { border: 1px solid #E2E8F0; padding: 5px 8px; vertical-align: middle; }
                .tc { text-align: center; }
                .tr { text-align: right; }
                .tl { text-align: left; }
                .bold { font-weight: bold; }
                .bg-zebra { background-color: #F8FAFC; }
                .bg-total { background-color: #F1F5F9; font-weight: bold; border-top: 1px solid #94A3B8; border-bottom: 3px double #0F172A; }
            </style></head><body>';

            echo '<table>';
            echo '<tr>';
            if ($logoBase64) {
                echo '<td colspan="2" style="border:none; vertical-align:middle;"><img src="'.$logoBase64.'" height="46" alt="Logo TBI"></td>';
                echo '<td colspan="17" style="border:none;">';
            } else {
                echo '<td colspan="19" style="border:none;">';
            }
            echo '<div class="title">PT TB BETON INDONESIA</div>';
            echo '<div class="subtitle">LAPORAN REKAPITULASI PURCHASE ORDER MATERIAL</div>';
            echo '<div class="meta">Periode: '.$monthName.' | Total Data: '.$totalCount.' Dokumen PO | Dicetak Oleh: '.$user.' | Waktu: '.$printedAt.'</div>';
            echo '</td></tr>';
            echo '<tr><td colspan="19" style="border:none; height:10px;"></td></tr>';

            // Headers
            echo '<tr>
                <th>No</th>
                <th>No. PO</th>
                <th>Tanggal PO</th>
                <th>Supplier / Vendor</th>
                <th>Jenis Material</th>
                <th>Spesifikasi</th>
                <th>Kuantitas</th>
                <th>Satuan</th>
                <th>Harga Satuan (Rp)</th>
                <th>Subtotal (Rp)</th>
                <th>PPN (Rp)</th>
                <th>Grand Total (Rp)</th>
                <th>Status Logistik</th>
                <th>Armada / Kapal</th>
                <th>Jetty Muat</th>
                <th>Jetty Bongkar</th>
                <th>Est. Berangkat</th>
                <th>Est. Tiba</th>
                <th>Aktual Tiba</th>
            </tr>';

            $totalQty = 0;
            $grandTotalSum = 0;
            $subtotalSum = 0;
            $ppnSum = 0;

            foreach ($purchaseOrders as $idx => $po) {
                $zebra = ($idx % 2 === 1) ? ' class="bg-zebra"' : '';
                $totalQty += (float) $po->qty_ordered;
                $subtotalSum += (float) ($po->subtotal ?? 0);
                $ppnSum += (float) ($po->ppn_amount ?? 0);
                $grandTotalSum += (float) ($po->grand_total ?? 0);

                echo "<tr{$zebra}>";
                echo '<td class="tc">'.($idx + 1).'</td>';
                echo '<td class="tc bold">'.htmlspecialchars((string) $po->po_number).'</td>';
                echo '<td class="tc">'.($po->po_date ? $po->po_date->format('d/m/Y') : '-').'</td>';
                echo '<td class="tl bold">'.htmlspecialchars((string) ($po->supplier?->name ?? 'Supplier Umum')).'</td>';
                echo '<td class="tl">'.htmlspecialchars((string) ($po->material_type_input ?: ($po->material?->name ?? '-'))).'</td>';
                echo '<td class="tl">'.htmlspecialchars((string) ($po->specification ?: '-')).'</td>';
                echo '<td class="tr">'.number_format((float) $po->qty_ordered, 2, '.', ',').'</td>';
                echo '<td class="tc">'.htmlspecialchars((string) ($po->unit ?? 'Kg')).'</td>';
                echo '<td class="tr">'.number_format((float) ($po->unit_price ?? 0), 2, '.', ',').'</td>';
                echo '<td class="tr">'.number_format((float) ($po->subtotal ?? 0), 2, '.', ',').'</td>';
                echo '<td class="tr">'.number_format((float) ($po->ppn_amount ?? 0), 2, '.', ',').'</td>';
                echo '<td class="tr bold">'.number_format((float) ($po->grand_total ?? 0), 2, '.', ',').'</td>';
                echo '<td class="tc bold">'.str_replace('_', ' ', strtoupper((string) $po->status)).'</td>';
                echo '<td class="tl">'.htmlspecialchars((string) ($po->vessel_name ?: '-')).'</td>';
                echo '<td class="tl">'.htmlspecialchars((string) ($po->loading_jetty ?: '-')).'</td>';
                echo '<td class="tl">'.htmlspecialchars((string) ($po->unloading_jetty ?: 'Jetty TBI Plant')).'</td>';
                echo '<td class="tc">'.($po->est_departure_date ? $po->est_departure_date->format('d/m/Y') : '-').'</td>';
                echo '<td class="tc">'.($po->est_arrival_date ? $po->est_arrival_date->format('d/m/Y') : '-').'</td>';
                echo '<td class="tc">'.($po->actual_arrival_date ? $po->actual_arrival_date->format('d/m/Y') : '-').'</td>';
                echo '</tr>';
            }

            // Summary
            echo '<tr class="bg-total">';
            echo '<td colspan="6" class="tr bold">TOTAL REKAPITULASI</td>';
            echo '<td class="tr bold">'.number_format($totalQty, 2, '.', ',').'</td>';
            echo '<td></td><td></td>';
            echo '<td class="tr bold">'.number_format($subtotalSum, 2, '.', ',').'</td>';
            echo '<td class="tr bold">'.number_format($ppnSum, 2, '.', ',').'</td>';
            echo '<td class="tr bold">'.number_format($grandTotalSum, 2, '.', ',').'</td>';
            echo '<td colspan="7"></td>';
            echo '</tr>';

            echo '</table></body></html>';
        }, $fileName, $headers);
    }

    /**
     * Fallback Excel (.xls) HTML format for Stock Reconciliation.
     *
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $reconciliations
     */
    protected function exportStockReconciliationFallback(
        Collection|array $reconciliations,
        int $year,
        int $month,
        ?string $printedBy = null
    ): StreamedResponse {
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i').' WITA';
        $user = $printedBy ?: 'Admin Logistik';
        $fileName = "Rekonsiliasi_Stok_TBI_{$year}_".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.xls';

        $logoBase64 = $this->getLogoBase64();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ];

        return response()->streamDownload(function () use ($reconciliations, $monthName, $printedAt, $user, $logoBase64) {
            echo "\xEF\xBB\xBF";
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rekonsiliasi Stok</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>
                body, table { font-family: "Segoe UI", Calibri, Arial, sans-serif; font-size: 10pt; }
                .title { font-size: 13pt; font-weight: bold; color: #18181B; }
                .subtitle { font-size: 10pt; font-weight: bold; color: #334155; }
                .meta { font-size: 9pt; color: #64748B; }
                th { background-color: #18181B; color: #FFFFFF; font-weight: bold; text-align: center; border: 1px solid #334155; padding: 6px 10px; }
                td { border: 1px solid #E2E8F0; padding: 5px 8px; vertical-align: middle; }
                .tc { text-align: center; }
                .tr { text-align: right; }
                .tl { text-align: left; }
                .bold { font-weight: bold; }
                .bg-zebra { background-color: #F8FAFC; }
                .bg-total { background-color: #F1F5F9; font-weight: bold; border-top: 1px solid #94A3B8; border-bottom: 3px double #0F172A; }
            </style></head><body>';

            echo '<table>';
            echo '<tr>';
            if ($logoBase64) {
                echo '<td colspan="2" style="border:none; vertical-align:middle;"><img src="'.$logoBase64.'" height="46" alt="Logo TBI"></td>';
                echo '<td colspan="10" style="border:none;">';
            } else {
                echo '<td colspan="12" style="border:none;">';
            }
            echo '<div class="title">PT TB BETON INDONESIA</div>';
            echo '<div class="subtitle">LAPORAN REKONSILIASI STOK MATERIAL</div>';
            echo '<div class="meta">Periode: '.$monthName.' | Dicetak Oleh: '.$user.' | Waktu: '.$printedAt.'</div>';
            echo '</td></tr>';
            echo '<tr><td colspan="12" style="border:none; height:10px;"></td></tr>';

            // Headers
            echo '<tr>
                <th>No</th>
                <th>ID Material</th>
                <th>Nama Material</th>
                <th>Satuan</th>
                <th>Stok Awal (Kg)</th>
                <th>Penerimaan (+) (Kg)</th>
                <th>Pemakaian (-) (Kg)</th>
                <th>Stok Akhir Sistem (Kg)</th>
                <th>Fisik Opname (Kg)</th>
                <th>Deviasi Selisih (Kg)</th>
                <th>Deviasi (%)</th>
                <th>Status Rekonsiliasi</th>
            </tr>';

            $totalAwal = 0;
            $totalIn = 0;
            $totalOut = 0;
            $totalTeoritis = 0;

            foreach ($reconciliations as $idx => $row) {
                $zebra = ($idx % 2 === 1) ? ' class="bg-zebra"' : '';
                $hasOpname = ! empty($row['has_opname']);
                $totalAwal += (float) $row['stok_awal'];
                $totalIn += (float) $row['penerimaan'];
                $totalOut += (float) $row['pemakaian'];
                $totalTeoritis += (float) $row['stok_teoritis'];

                $statusBg = match ($row['status']) {
                    'SESUAI' => '#ECFDF5; color:#065F46',
                    'KELEBIHAN' => '#FFFBEB; color:#92400E',
                    'KEKURANGAN' => '#FEF2F2; color:#991B1B',
                    default => '#F4F4F5; color:#71717A',
                };

                echo "<tr{$zebra}>";
                echo '<td class="tc">'.($idx + 1).'</td>';
                echo '<td class="tc">'.htmlspecialchars((string) $row['material_id']).'</td>';
                echo '<td class="tl bold">'.htmlspecialchars((string) $row['material_name']).'</td>';
                echo '<td class="tc">'.htmlspecialchars((string) $row['unit']).'</td>';
                echo '<td class="tr">'.number_format((float) $row['stok_awal'], 2, '.', ',').'</td>';
                echo '<td class="tr">'.number_format((float) $row['penerimaan'], 2, '.', ',').'</td>';
                echo '<td class="tr">'.number_format((float) $row['pemakaian'], 2, '.', ',').'</td>';
                echo '<td class="tr bold">'.number_format((float) $row['stok_teoritis'], 2, '.', ',').'</td>';
                echo '<td class="tr">'.($hasOpname ? number_format((float) $row['stok_fisik'], 2, '.', ',') : '<i style="color:#94A3B8;">Belum Diopname</i>').'</td>';
                echo '<td class="tr">'.($hasOpname ? number_format((float) $row['selisih'], 2, '.', ',') : '-').'</td>';
                echo '<td class="tr">'.($hasOpname ? number_format((float) $row['persentase_selisih'], 2, '.', ',').'%' : '-').'</td>';
                echo '<td class="tc bold" style="background-color:'.$statusBg.';">'.htmlspecialchars((string) $row['status']).'</td>';
                echo '</tr>';
            }

            // Summary
            echo '<tr class="bg-total">';
            echo '<td colspan="4" class="tr bold">TOTAL REKAPITULASI</td>';
            echo '<td class="tr bold">'.number_format($totalAwal, 2, '.', ',').'</td>';
            echo '<td class="tr bold">'.number_format($totalIn, 2, '.', ',').'</td>';
            echo '<td class="tr bold">'.number_format($totalOut, 2, '.', ',').'</td>';
            echo '<td class="tr bold">'.number_format($totalTeoritis, 2, '.', ',').'</td>';
            echo '<td colspan="4"></td>';
            echo '</tr>';

            echo '</table></body></html>';
        }, $fileName, $headers);
    }

    /**
     * Get Base64 encoded string of TBI logo for inline embedding.
     */
    protected function getLogoBase64(): ?string
    {
        return $this->resolveLogoBase64();
    }

    /**
     * Draw professional corporate header with company logo, title, and metadata.
     */
    private function drawReportHeader(Worksheet $sheet, string $reportTitle, string $metaText, string $lastColLetter = 'M'): void
    {
        $logoPath = $this->resolveLogoPath();

        if ($logoPath && file_exists($logoPath)) {
            $drawing = new Drawing;
            $drawing->setName('Logo TBI');
            $drawing->setDescription('Logo PT TB Beton Indonesia');
            $drawing->setPath($logoPath);
            $drawing->setHeight(46);
            $drawing->setCoordinates('B2');
            $drawing->setWorksheet($sheet);
        }

        // Title text in column D merged across to $lastColLetter so Column D does not stretch excessively!
        $sheet->mergeCells("D2:{$lastColLetter}2");
        $sheet->setCellValue('D2', 'PT TB BETON INDONESIA');
        $sheet->getStyle('D2')->getFont()->setSize(13)->setBold(true)->getColor()->setARGB(self::COLOR_DARK_ZINC);

        $sheet->mergeCells("D3:{$lastColLetter}3");
        $sheet->setCellValue('D3', $reportTitle);
        $sheet->getStyle('D3')->getFont()->setSize(10)->setBold(true)->getColor()->setARGB(self::COLOR_SLATE_TITLE);

        $sheet->mergeCells("D4:{$lastColLetter}4");
        $sheet->setCellValue('D4', $metaText);
        $sheet->getStyle('D4')->getFont()->setSize(9)->getColor()->setARGB(self::COLOR_MUTED);

        // Spacer Row Heights
        $sheet->getRowDimension(1)->setRowHeight(8);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(16);
        $sheet->getRowDimension(4)->setRowHeight(16);
        $sheet->getRowDimension(5)->setRowHeight(10);
    }

    /**
     * Stream an in-memory PhpSpreadsheet instance directly as a downloadable .xlsx response.
     */
    public function streamSpreadsheet(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ];

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, $headers);
    }

    /**
     * Embedded fallback Base64 logo.
     */
    private const EMBEDDED_LOGO_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAUwAAACuCAYAAABdlTRiAAAQAElEQVR4Aey995skx3Ulem5WVfue7vEWwMCMHwCDGRAe4MATIAlDEgQ9QVKrlVafdr/94e1/8L739LS7TyK5oqQVjUBKBOFBEARAEiS8m4H3A4yBHe/blss9J7KiO6u62tuZzuo6FZFhbty4mXnyRkRmdhAmn8QCiQUSCyQWGJIFApQ+LA2htJkEiQUSCyQWSCxQYYEewlS6CPPo0aPYtm0bNm3ahCeffBJPPfUUnn766UHxzDPPYKSolO/b3LJlC9ra2qRagsQCiQUSC0y6BXoI08ych7l792786U9/wr/8y7/g7//+7/GDH/wAf/d3fzcgVE74/ve/j4FQLU/1PP6u1I62Ff/5z3+OF198Ee3t7ZNuqESBxAKJBRIL9BCmTGFm6OjowAcffIBXXnnFeZnPP/+8C+Vx9geVEZ577jlUor90X075HpXy77//ftx2223YvHkzuru7paIjdRdJfhILJBZILDDBFigjTLUdBAHMrAcapivdrDdN25VQuWooFouO5Krl+bRKWdo2M8jbffzxx3HffffhhRdewJEjR5SVILFAYoHEApNigaCyVTNDnDQVF8x6CVPb4w0zQ11dHTSn+uijj+L222/Hyy+/jM7OTgzpkxRKLJBYILHAGFugD2FKvllEjopPJkTKan/v3r2Qp3nPPffgtddecyQqz1V5CRILJBZILDBRFqhKmBPV+FDaEWmaGQ4cOOBI89Zbb3WkqdXzhDSHYsGkTGKBxAJjZYEpT5jqaCqVcnOqIk0Nz3/1q1/hjTfecAtUmgdVmclF0npigcQC08ECxwRh+h0hctQtRro/9O6773Yr+ZrjVLovk4SJBRILJBYYLwscU4TpjXDw4EF3U71IU/dpJqvn3jJJmFggscB4WmBKE2al52gWLUYpXQtBTzzxhLvlSKR5+PBhd/vSeBprishO1EgskFhgkiwwpQnTLCJIs95Qi0BCoVDAvn373ELQvffe626uP3To0CSZMWk2sUBigelggSlNmNoBZqagD8yiRzn37NnjhucPPvigm9NMhud9TJUkJBZILDBGFuhDmGbVCWqM2htTMWbmbrKXp6kXfzz88MN46aWXoDlODdsFJJ9RWSCpnFggsUCvBfoQZm/WsRHT8FyaytPUze16jFLPnidz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';

    protected function resolveLogoBase64(): string
    {
        $path = $this->resolveLogoPath();
        if ($path && file_exists($path) && is_readable($path)) {
            $content = (string) file_get_contents($path);
            if (! empty($content)) {
                return 'data:image/png;base64,'.base64_encode($content);
            }
        }

        return 'data:image/png;base64,'.self::EMBEDDED_LOGO_BASE64;
    }

    protected function resolveLogoPath(): ?string
    {
        $candidates = [
            public_path('images/logo.png'),
            public_path('logo.png'),
            resource_path('images/logo.png'),
            base_path('public/images/logo.png'),
            base_path('public/logo.png'),
            base_path('htdocs/images/logo.png'),
            base_path('htdocs/logo.png'),
            isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'].'/images/logo.png' : null,
            isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'].'/logo.png' : null,
        ];

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand) && is_readable($cand)) {
                return $cand;
            }
        }

        try {
            $cacheDir = storage_path('app');
            if (! is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $cacheFile = $cacheDir.'/logo_tbi_cached.png';
            if (! file_exists($cacheFile) || filesize($cacheFile) === 0) {
                file_put_contents($cacheFile, base64_decode(self::EMBEDDED_LOGO_BASE64));
            }

            if (file_exists($cacheFile)) {
                return $cacheFile;
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
