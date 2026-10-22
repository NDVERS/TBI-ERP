<?php

namespace App\Http\Controllers;

use App\Models\MaterialReceive;
use App\Models\ProductionDocket;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\ExcelReportService;
use App\Services\StockReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveReportController extends Controller
{
    /**
     * Download All-in-One Comprehensive Monthly Report in Excel format (.xlsx / .xls fallback).
     */
    public function downloadAllInOneExcel(
        Request $request,
        ExcelReportService $excelService,
        StockReconciliationService $reconService
    ): StreamedResponse {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        $reconciliations = $reconService->reconcileAllMaterials($year, $month);

        $receives = MaterialReceive::with(['supplier', 'material', 'purchaseOrder'])
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month)
            ->orderBy('receive_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $dockets = ProductionDocket::with(['salesOrder', 'creator'])
            ->whereYear('production_date', $year)
            ->whereMonth('production_date', $month)
            ->orderBy('production_date', 'asc')
            ->orderBy('time_load', 'asc')
            ->get();

        $purchaseOrders = PurchaseOrder::with(['supplier', 'material'])
            ->whereYear('po_date', $year)
            ->whereMonth('po_date', $month)
            ->orderBy('po_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $printedBy = Auth::user()?->name ?? 'Eksekutif / Manajemen';

        return $excelService->exportAllInOneMonthlyReport(
            reconciliations: $reconciliations,
            receives: $receives,
            dockets: $dockets,
            purchaseOrders: $purchaseOrders,
            year: $year,
            month: $month,
            printedBy: $printedBy
        );
    }

    /**
     * Download Executive Summary Report in PDF format (A4 Landscape).
     */
    public function downloadExecutivePdf(
        Request $request,
        StockReconciliationService $reconService,
        ExcelReportService $excelService
    ): Response {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i').' WITA';
        $printedBy = Auth::user()?->name ?? 'Eksekutif / Manajemen';
        $logoBase64 = $this->getLogoBase64($excelService);

        // 1. Data Rekonsiliasi Stok Material
        $reconciliations = $reconService->reconcileAllMaterials($year, $month);
        $sesuaiCount = $reconciliations->where('status', StockReconciliationService::STATUS_SESUAI)->count();
        $kelebihanCount = $reconciliations->where('status', StockReconciliationService::STATUS_KELEBIHAN)->count();
        $kekuranganCount = $reconciliations->where('status', StockReconciliationService::STATUS_KEKURANGAN)->count();

        // 2. Data Produksi Cor Ready-Mix
        $dockets = ProductionDocket::query()
            ->whereYear('production_date', $year)
            ->whereMonth('production_date', $month)
            ->get();

        $totalProductionVolume = (float) $dockets->sum('volume_m3');
        $totalDocketCount = $dockets->count();
        $avgVolumePerTrip = $totalDocketCount > 0 ? ($totalProductionVolume / $totalDocketCount) : 0;

        // Distribusi Produksi per Mutu Beton
        $mutuDistribution = $dockets->groupBy('mutu_code')->map(function ($items, $key) use ($totalProductionVolume) {
            $vol = (float) $items->sum('volume_m3');
            $pct = $totalProductionVolume > 0 ? ($vol / $totalProductionVolume * 100) : 0;

            return [
                'mutu' => $key,
                'count' => $items->count(),
                'volume_m3' => $vol,
                'percentage' => $pct,
            ];
        })->sortByDesc('volume_m3')->values();

        // 3. Data Penerimaan Material Masuk (Inbound Truk)
        $receives = MaterialReceive::with('material')
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month)
            ->get();

        $totalReceiveTrips = $receives->count();
        $totalMaterialInKg = (float) $receives->sum('total_weight_kg');

        $receiveByMaterial = $receives->groupBy('material_id')->map(function ($items) {
            $m = $items->first()->material;

            return [
                'name' => $m?->name ?? 'Material',
                'unit' => $m?->unit ?? 'Kg',
                'trips' => $items->count(),
                'total_weight_kg' => (float) $items->sum('total_weight_kg'),
                'volume_m3' => (float) $items->sum('volume_m3'),
            ];
        })->sortByDesc('total_weight_kg')->values();

        // 4. Data Purchase Order & Sales Order
        $purchaseOrders = PurchaseOrder::query()
            ->whereYear('po_date', $year)
            ->whereMonth('po_date', $month)
            ->get();

        $poTotalSpend = (float) $purchaseOrders->sum('grand_total');
        $poCount = $purchaseOrders->count();

        $salesOrders = SalesOrder::query()
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->get();

        $soTotalVolume = (float) $salesOrders->sum('volume_m3');
        $soCount = $salesOrders->count();

        $data = [
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'printedBy' => $printedBy,
            'printedAt' => $printedAt,
            'logo' => $logoBase64,
            'reconciliations' => $reconciliations,
            'sesuaiCount' => $sesuaiCount,
            'kelebihanCount' => $kelebihanCount,
            'kekuranganCount' => $kekuranganCount,
            'totalProductionVolume' => $totalProductionVolume,
            'totalDocketCount' => $totalDocketCount,
            'avgVolumePerTrip' => $avgVolumePerTrip,
            'mutuDistribution' => $mutuDistribution,
            'totalReceiveTrips' => $totalReceiveTrips,
            'totalMaterialInKg' => $totalMaterialInKg,
            'receiveByMaterial' => $receiveByMaterial,
            'poTotalSpend' => $poTotalSpend,
            'poCount' => $poCount,
            'soTotalVolume' => $soTotalVolume,
            'soCount' => $soCount,
        ];

        $filename = "Ringkasan_Eksekutif_TBI_{$year}_".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.pdf';

        $this->ensureDompdfRegistered();

        try {
            $pdf = Pdf::loadView('pdf.executive-monthly-report', $data)->setPaper('a4', 'landscape');

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response()->view('pdf.executive-monthly-report', array_merge($data, ['isHtmlFallback' => true]));
        }
    }

    /**
     * Enforce RBAC access authorization (Admin, Manajemen, Demo).
     */
    protected function authorizeAccess(): void
    {
        if (! Auth::check()) {
            abort(401, 'Silakan masuk terlebih dahulu.');
        }

        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'manajemen', 'demo'], true)) {
            abort(403, 'Akses Ditolak: Laporan eksekutif hanya dapat diakses oleh Admin & Manajemen.');
        }
    }

    /**
     * Ensure Dompdf service provider is bound dynamically if package auto-discovery is delayed.
     */
    protected function ensureDompdfRegistered(): void
    {
        if (! app()->bound('dompdf.wrapper') && class_exists(ServiceProvider::class)) {
            app()->register(ServiceProvider::class);
        }
    }

    /**
     * Resolve Base64 Data URI for DOMPDF and HTML views.
     */
    protected function getLogoBase64(ExcelReportService $excelService): string
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
                $content = (string) file_get_contents($cand);
                if (! empty($content)) {
                    return 'data:image/png;base64,'.base64_encode($content);
                }
            }
        }

        return $excelService->getLogoBase64() ?? '';
    }
}
