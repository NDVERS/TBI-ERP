<?php

namespace App\Livewire;

use App\Models\MaterialReceive;
use App\Models\ProductionDocket;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ExecutiveDashboard extends Component
{
    public int $selectedYear;

    public int $selectedMonth;

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'manajemen', 'demo'])) {
            abort(403, 'Akses Ditolak: Dashboard Eksekutif hanya dapat diakses oleh Admin & Manajemen.');
        }

        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
    }

    public function render(StockReconciliationService $reconciliationService): View
    {
        $year = $this->selectedYear;
        $month = $this->selectedMonth;

        // 1. KPI Kumulatif Produksi Cor Hari Ini (Berdasarkan Production Docket)
        $todayDate = Carbon::today()->toDateString();
        $todayProductionVolume = (float) ProductionDocket::query()
            ->whereDate('production_date', $todayDate)
            ->sum('volume_m3');

        $todayDocketCount = ProductionDocket::query()
            ->whereDate('production_date', $todayDate)
            ->count();

        $totalSalesVolume = (float) SalesOrder::query()
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->sum('volume_m3');

        $totalSalesOrders = SalesOrder::query()
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->count();

        // 2. Widget Logistik: 5 PO Pengapalan Aktif Terdekat
        $nearestPOs = PurchaseOrder::query()
            ->with(['supplier', 'material'])
            ->where('status', '!=', 'Selesai Seluruhnya')
            ->orderByRaw('COALESCE(est_arrival_date, actual_arrival_date, po_date) ASC')
            ->take(5)
            ->get();

        $totalMaterialInKg = (float) MaterialReceive::query()
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month)
            ->sum('total_weight_kg');

        $totalReceiveTrips = MaterialReceive::query()
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month)
            ->count();

        // 3. Status Deviasi Rekonsiliasi Stok
        $reconciliations = $reconciliationService->reconcileAllMaterials($year, $month);

        $sesuaiCount = $reconciliations->where('status', StockReconciliationService::STATUS_SESUAI)->count();
        $kelebihanCount = $reconciliations->where('status', StockReconciliationService::STATUS_KELEBIHAN)->count();
        $kekuranganCount = $reconciliations->where('status', StockReconciliationService::STATUS_KEKURANGAN)->count();
        $belumDiopnameCount = $reconciliations->where('status', StockReconciliationService::STATUS_BELUM_DIOPNAME)->count();

        // 4. Estimasi Pemakaian Solar Armada
        $totalEstSolar = (float) SalesOrder::query()
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->sum('est_solar_liter');

        // 5. 5 Sales Order Rencana Cor Besok (H+1)
        $tomorrowDate = Carbon::tomorrow()->toDateString();
        $tomorrowSalesOrders = SalesOrder::query()
            ->with(['customer', 'jobMix'])
            ->whereDate('order_date', $tomorrowDate)
            ->orderBy('id', 'asc')
            ->take(5)
            ->get();

        // 6. Top Mutu Beton Paling Sering Dipesan
        $topJobMixes = SalesOrder::query()
            ->select('job_mix_id', DB::raw('SUM(volume_m3) as total_volume'), DB::raw('COUNT(*) as total_orders'))
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month)
            ->groupBy('job_mix_id')
            ->orderByDesc('total_volume')
            ->with('jobMix')
            ->take(5)
            ->get();

        // 7. Peringatan Dini Stok Kritis (Ambang batas minimum 50.000 untuk material produksi utama)
        $lowStockThresholdKg = 50000.00;
        $lowStockMaterials = $reconciliations->filter(function ($item) use ($lowStockThresholdKg) {
            return (float) $item['stok_teoritis'] < $lowStockThresholdKg;
        });

        // Seluruh material utama untuk ringkasan cepat stok & deviasi
        $quickStockMaterials = $reconciliations;

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

        return view('livewire.executive-dashboard', [
            'todayProductionVolume' => $todayProductionVolume,
            'todayDocketCount' => $todayDocketCount,
            'nearestPOs' => $nearestPOs,
            'tomorrowSalesOrders' => $tomorrowSalesOrders,
            'latestSalesOrders' => $tomorrowSalesOrders, // Backward compatibility
            'totalSalesVolume' => $totalSalesVolume,
            'totalSalesOrders' => $totalSalesOrders,
            'totalMaterialInKg' => $totalMaterialInKg,
            'totalReceiveTrips' => $totalReceiveTrips,
            'sesuaiCount' => $sesuaiCount,
            'kelebihanCount' => $kelebihanCount,
            'kekuranganCount' => $kekuranganCount,
            'belumDiopnameCount' => $belumDiopnameCount,
            'totalEstSolar' => $totalEstSolar,
            'quickStockMaterials' => $quickStockMaterials,
            'topJobMixes' => $topJobMixes,
            'lowStockMaterials' => $lowStockMaterials,
            'lowStockThresholdKg' => $lowStockThresholdKg,
            'months' => $months,
        ]);
    }
}
