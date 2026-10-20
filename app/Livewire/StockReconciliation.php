<?php

namespace App\Livewire;

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

    public function exportCsv(StockReconciliationService $service): StreamedResponse
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Hanya Admin yang dapat mengunduh data rekonsiliasi.');
        }

        $year = $this->selectedYear;
        $month = str_pad((string) $this->selectedMonth, 2, '0', STR_PAD_LEFT);
        $fileName = "Rekonsiliasi_Stok_TBI_{$year}_{$month}.csv";

        $reconciliations = $service->reconcileAllMaterials($this->selectedYear, $this->selectedMonth);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($reconciliations) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'ID Material',
                'Nama Material',
                'Satuan',
                'Stok Awal Bulan (Kg)',
                'Total Penerimaan (Kg)',
                'Total Pemakaian (Kg)',
                'Stok Akhir Teoritis (Kg)',
                'Stok Fisik Opname (Kg)',
                'Deviasi Selisih (Kg)',
                'Deviasi (%)',
                'Status Rekonsiliasi',
            ]);

            // Data rows (Values only, strictly no formula leakage)
            foreach ($reconciliations as $row) {
                fputcsv($handle, [
                    $row['material_id'],
                    $row['material_name'],
                    $row['unit'],
                    number_format((float) $row['stok_awal'], 2, '.', ''),
                    number_format((float) $row['penerimaan'], 2, '.', ''),
                    number_format((float) $row['pemakaian'], 2, '.', ''),
                    number_format((float) $row['stok_teoritis'], 2, '.', ''),
                    number_format((float) $row['stok_fisik'], 2, '.', ''),
                    number_format((float) $row['selisih'], 2, '.', ''),
                    number_format((float) $row['persentase_selisih'], 2, '.', '').'%',
                    $row['status'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render(StockReconciliationService $service): View
    {
        $rawReconciliations = $service->reconcileAllMaterials($this->selectedYear, $this->selectedMonth);

        $sesuaiCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_SESUAI)->count();
        $kelebihanCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_KELEBIHAN)->count();
        $kekuranganCount = $rawReconciliations->where('status', StockReconciliationService::STATUS_KEKURANGAN)->count();

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
            'highDeviationCount' => $highDeviationCount,
            'months' => $months,
        ]);
    }
}
