<?php

namespace App\Http\Controllers;

use App\Models\ProductionDocket;
use App\Services\ExcelReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionDocketPrintController extends Controller
{
    /**
     * Download / Stream Single Delivery Ticket / Surat Jalan Docket Beton (PDF).
     */
    public function downloadSingle(ProductionDocket $docket): Response
    {
        $this->authorizeAccess();

        $docket->load(['salesOrder', 'creator']);

        $logoBase64 = $this->getLogoBase64();
        $safeDocketNo = str_replace(['/', '\\'], '-', $docket->docket_no);
        $filename = "Docket_{$safeDocketNo}.pdf";

        $data = [
            'docket' => $docket,
            'logo' => $logoBase64,
            'printedBy' => Auth::user()?->name ?? 'Operator Batching',
            'printedAt' => Carbon::now()->translatedFormat('d F Y H:i').' WITA',
        ];

        $this->ensureDompdfRegistered();

        try {
            $pdf = Pdf::loadView('pdf.production-docket-single', $data)->setPaper('a4', 'portrait');

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response()->view('pdf.production-docket-single', array_merge($data, ['isHtmlFallback' => true]));
        }
    }

    /**
     * Download / Stream Monthly Production & Dispatch Summary Report (PDF).
     */
    public function downloadReportPdf(Request $request): Response
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $salesOrderId = $request->input('sales_order_id');
        $mutuCode = $request->input('mutu_code');

        $query = ProductionDocket::with(['salesOrder', 'creator'])
            ->whereYear('production_date', $year)
            ->whereMonth('production_date', $month);

        if ($salesOrderId) {
            $query->where('sales_order_id', $salesOrderId);
        }

        if ($mutuCode) {
            $query->where('mutu_code', $mutuCode);
        }

        $dockets = $query->orderBy('production_date', 'asc')->orderBy('time_load', 'asc')->get();

        $logoBase64 = $this->getLogoBase64();
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $filename = "Rekap_Produksi_Docket_TBI_{$year}_{$month}.pdf";

        $data = [
            'dockets' => $dockets,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'logo' => $logoBase64,
            'printedBy' => Auth::user()?->name ?? 'Operator Batching',
            'printedAt' => Carbon::now()->translatedFormat('d F Y H:i').' WITA',
        ];

        $this->ensureDompdfRegistered();

        try {
            $pdf = Pdf::loadView('pdf.production-docket-report', $data)->setPaper('a4', 'landscape');

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response()->view('pdf.production-docket-report', array_merge($data, ['isHtmlFallback' => true]));
        }
    }

    /**
     * Download Monthly Production & Dispatch Summary Report in formatted Excel (.xlsx / .xls).
     */
    public function downloadReportExcel(Request $request, ExcelReportService $excelService): StreamedResponse
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $salesOrderId = $request->input('sales_order_id');
        $mutuCode = $request->input('mutu_code');

        $query = ProductionDocket::with(['salesOrder', 'creator'])
            ->whereYear('production_date', $year)
            ->whereMonth('production_date', $month);

        if ($salesOrderId) {
            $query->where('sales_order_id', $salesOrderId);
        }

        if ($mutuCode) {
            $query->where('mutu_code', $mutuCode);
        }

        $dockets = $query->orderBy('production_date', 'asc')->orderBy('time_load', 'asc')->get();

        return $excelService->exportProductionDocketReport(
            dockets: $dockets,
            year: $year,
            month: $month,
            printedBy: Auth::user()?->name ?? 'Operator Batching'
        );
    }

    /**
     * Enforce RBAC access authorization.
     */
    protected function authorizeAccess(): void
    {
        if (! Auth::check()) {
            abort(401, 'Silakan masuk terlebih dahulu.');
        }

        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'operator_produksi', 'demo'], true)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengunduh dokumen tiket dan laporan produksi.');
        }
    }

    /**
     * Ensure Dompdf service provider is bound dynamically if package auto-discovery is delayed or missing on shared hosting.
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
    protected function getLogoBase64(): string
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

        return (new ExcelReportService)->getLogoBase64() ?? '';
    }
}
