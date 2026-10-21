<?php

namespace App\Http\Controllers;

use App\Models\MaterialReceive;
use App\Services\ExcelReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialReceivePrintController extends Controller
{
    /**
     * Download / Stream Material Receive Inbound Truck Report (PDF).
     */
    public function downloadReportPdf(Request $request): Response
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $supplierId = $request->input('supplier_id');
        $materialId = $request->input('material_id');

        $query = MaterialReceive::with(['supplier', 'material', 'purchaseOrder'])
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        $receives = $query->orderBy('receive_date', 'asc')->orderBy('id', 'asc')->get();

        $logoBase64 = $this->getLogoBase64();
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        $filename = "Rekap_Penerimaan_Material_TBI_{$year}_{$month}.pdf";

        $data = [
            'receives' => $receives,
            'year' => $year,
            'month' => $month,
            'monthName' => $monthName,
            'logo' => $logoBase64,
            'printedBy' => Auth::user()?->name ?? 'Petugas Gudang',
            'printedAt' => Carbon::now()->translatedFormat('d F Y H:i').' WITA',
        ];

        $this->ensureDompdfRegistered();

        try {
            $pdf = Pdf::loadView('pdf.material-receive-report', $data)->setPaper('a4', 'landscape');

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response()->view('pdf.material-receive-report', array_merge($data, ['isHtmlFallback' => true]));
        }
    }

    /**
     * Download Material Receive Report in formatted Excel (.xlsx / .xls).
     */
    public function downloadReportExcel(Request $request, ExcelReportService $excelService): StreamedResponse
    {
        $this->authorizeAccess();

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);
        $supplierId = $request->input('supplier_id');
        $materialId = $request->input('material_id');

        $query = MaterialReceive::with(['supplier', 'material', 'purchaseOrder'])
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        $receives = $query->orderBy('receive_date', 'asc')->orderBy('id', 'asc')->get();

        return $excelService->exportMaterialReceiveReport(
            receives: $receives,
            year: $year,
            month: $month,
            printedBy: Auth::user()?->name ?? 'Petugas Gudang'
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
        if (! in_array($user->role, ['admin', 'petugas_gudang', 'demo'], true)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengunduh laporan penerimaan material.');
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
