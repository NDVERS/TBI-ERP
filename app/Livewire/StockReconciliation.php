<?php

namespace App\Livewire;

use App\Services\ExcelReportService;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class StockReconciliation extends Component
{
    public int $selectedYear;

    public int $selectedMonth;

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Rekonsiliasi Stok hanya dapat diakses oleh Admin.');
        }

        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
    }

    public function exportExcel(StockReconciliationService $service, ExcelReportService $excelService): StreamedResponse
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat mengunduh data rekonsiliasi.');
        }

        $reconciliations = $service->reconcileAllMaterials($this->selectedYear, $this->selectedMonth);

        return $excelService->exportStockReconciliation(
            reconciliations: $reconciliations,
            year: $this->selectedYear,
            month: $this->selectedMonth,
            printedBy: Auth::user()->name
        );
    }

    /**
     * Alias for exportExcel to maintain backward compatibility with previous callers/tests.
     */
    public function exportCsv(StockReconciliationService $service, ExcelReportService $excelService): StreamedResponse
    {
        return $this->exportExcel($service, $excelService);
    }

    public function render(StockReconciliationService $service): View
    {
        $rawReconciliations = $service->reconcileAllMaterials($this->selectedYear, $this->selectedMonth);

        $sesuaiCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_SESUAI)->count();
        $kelebihanCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_KELEBIHAN)->count();
        $kekuranganCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_KEKURANGAN)->count();
        $belumDiopnameCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_BELUM_DIOPNAME)->count();

        $filteredReconciliations = $rawReconciliations->when($this->search, function (Collection $collection, $search) {
            return $collection->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['material_name']), strtolower($search)) ||
                    str_contains(strtolower($item['status']), strtolower($search));
            });
        });

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $highDeviationCount = $rawReconciliations->where('persentase_selisih', '>', 2.0)->count();

        return view('livewire.stock-reconciliation', [
            'reconciliations' => $filteredReconciliations,
            'sesuaiCount' => $sesuaiCount,
            'kelebihanCount' => $kelebihanCount,
            'kekuranganCount' => $kekuranganCount,
            'belumDiopnameCount' => $belumDiopnameCount,
            'highDeviationCount' => $highDeviationCount,
            'months' => $months,
        ]);
    }
}
