<?php

namespace App\Services;

use App\Models\FleetTripLog;
use App\Models\LabConversion;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\ProductionDocket;
use App\Models\SalesOrder;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockReconciliationService
{
    public const STATUS_SESUAI = 'SESUAI';

    public const STATUS_KELEBIHAN = 'KELEBIHAN';

    public const STATUS_KEKURANGAN = 'KEKURANGAN';

    public const STATUS_BELUM_DIOPNAME = 'BELUM DIOPNAME';

    /**
     * Calculate reconciliation metrics purely from numeric inputs:
     * Stok Akhir Teoritis = Stok Awal + Penerimaan - Pemakaian
     * Selisih Stok = Stok Akhir Teoritis - Stok Fisik Opname
     * Persentase Selisih = (|Selisih Stok| / Stok Akhir Teoritis) * 100
     * Status:
     *   Teoritis == Fisik => "SESUAI"
     *   Teoritis < Fisik  => "KELEBIHAN"
     *   Teoritis > Fisik  => "KEKURANGAN"
     *
     * @return array{
     *     stok_awal: float,
     *     penerimaan: float,
     *     pemakaian: float,
     *     stok_teoritis: float,
     *     stok_fisik: float,
     *     selisih: float,
     *     persentase_selisih: float,
     *     status: string
     * }
     */
    public function calculateReconciliation(
        float|string $initialStock,
        float|string $totalReceive,
        float|string $totalUsage,
        float|string $physicalStock
    ): array {
        $awal = round((float) $initialStock, 2);
        $terima = round((float) $totalReceive, 2);
        $pakai = round((float) $totalUsage, 2);
        $fisik = round((float) $physicalStock, 2);

        $teoritis = round($awal + $terima - $pakai, 2);
        $selisih = round($teoritis - $fisik, 2);

        // Selisih persentase terhadap stok teoritis
        $persentase = 0.0;
        if (abs($teoritis) > 0.0001) {
            $persentase = round((abs($selisih) / abs($teoritis)) * 100, 4);
        }

        // Penentuan status dengan batas toleransi 0.5% sesuai PRD
        $toleransiPersen = 0.50;
        if ($persentase <= $toleransiPersen || abs($selisih) < 0.01) {
            $status = self::STATUS_SESUAI;
        } elseif ($teoritis < $fisik) {
            $status = self::STATUS_KELEBIHAN;
        } else {
            $status = self::STATUS_KEKURANGAN;
        }

        return [
            'stok_awal' => $awal,
            'penerimaan' => $terima,
            'pemakaian' => $pakai,
            'stok_teoritis' => $teoritis,
            'stok_fisik' => $fisik,
            'selisih' => $selisih,
            'deviasi' => round($fisik - $teoritis, 2),
            'persentase_selisih' => $persentase,
            'status' => $status,
        ];
    }

    /**
     * Get monthly material receive total weight in Kg (or Liter for Dexlite) for given material and period.
     */
    public function getMonthlyReceiveTotal(Material $material, int $year, int $month): float
    {
        $name = strtolower(trim($material->name));
        $query = MaterialReceive::query()
            ->where('material_id', $material->id)
            ->whereYear('receive_date', $year)
            ->whereMonth('receive_date', $month);

        // Untuk Dexlite (satuan Liter), hitung total penerimaan dalam satuan Liter
        if (str_contains($name, 'dexlite') || str_contains($name, 'solar') || strtoupper($material->unit) === 'LITER') {
            $receives = $query->get();
            $totalLiter = 0.0;
            foreach ($receives as $rec) {
                if ((float) $rec->volume_m3 > 0) {
                    $totalLiter += (float) $rec->volume_m3 * 1000.0;
                } elseif ((float) $rec->total_weight_kg > 0) {
                    $density = (float) $rec->density_used ?: ((float) $material->density_kg_m3 ?: 840.0);
                    $totalLiter += ((float) $rec->total_weight_kg / $density) * 1000.0;
                }
            }

            return round($totalLiter, 2);
        }

        return (float) $query->sum('total_weight_kg');
    }

    /**
     * Get monthly production usage for given material and period.
     */
    public function getMonthlyProductionUsage(Material $material, int $year, int $month): float
    {
        $name = strtolower(trim($material->name));

        // Untuk Dexlite, diambil dari total_fuel_liter pada fleet_trip_logs atau sales_orders
        if (str_contains($name, 'dexlite') || str_contains($name, 'solar')) {
            $tripUsage = (float) FleetTripLog::query()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('total_fuel_liter');

            if ($tripUsage > 0) {
                return $tripUsage;
            }

            return (float) SalesOrder::query()
                ->whereYear('order_date', $year)
                ->whereMonth('order_date', $month)
                ->sum('est_solar_liter');
        }

        // Untuk Additive mentah (Kg), pemakaian dihitung dari konversi lab ke SBTJM2
        if (str_contains($name, 'additive')) {
            return (float) LabConversion::query()
                ->whereYear('conversion_date', $year)
                ->whereMonth('conversion_date', $month)
                ->sum('additive_used_kg');
        }

        $docketsQuery = ProductionDocket::query()
            ->whereYear('production_date', $year)
            ->whereMonth('production_date', $month);

        $docketUsage = match (true) {
            str_contains($name, 'semen') => (float) $docketsQuery->sum('silo1_act') + (float) $docketsQuery->sum('silo2_act'),
            str_contains($name, 'palu') => (float) $docketsQuery->sum('pasir_palu_act'),
            str_contains($name, 'lokal') => (float) $docketsQuery->sum('pasir_lokal_act'),
            str_contains($name, '1/2') || str_contains($name, 'kecil') => (float) $docketsQuery->sum('split_1_2_act'),
            str_contains($name, '2/3') || str_contains($name, 'besar') => (float) $docketsQuery->sum('split_2_3_act'),
            str_contains($name, 'air') => (float) $docketsQuery->sum('water_act'),
            str_contains($name, 'sbtjm2') => (float) $docketsQuery->sum('admixture_act'),
            default => 0.0,
        };

        if ($docketUsage > 0) {
            return $docketUsage;
        }

        // Estimasi pemakaian dari Sales Order bulan tersebut jika belum ada docket produksi aktual
        $soQuery = SalesOrder::query()
            ->whereYear('order_date', $year)
            ->whereMonth('order_date', $month);

        return match (true) {
            str_contains($name, 'semen') => (float) $soQuery->sum('est_semen_kg'),
            str_contains($name, 'palu') => (float) $soQuery->sum('est_pasir_palu_kg'),
            str_contains($name, 'lokal') => (float) $soQuery->sum('est_pasir_lokal_kg'),
            str_contains($name, '1/2') || str_contains($name, 'kecil') => (float) $soQuery->sum('est_split_1_2_kg'),
            str_contains($name, '2/3') || str_contains($name, 'besar') => (float) $soQuery->sum('est_split_2_3_kg'),
            str_contains($name, 'sbtjm2') => (float) $soQuery->sum('est_additive_liter'),
            default => 0.0,
        };
    }

    /**
     * Get latest stock opname weight (or liters for Dexlite) for given material and period.
     * Evaluates valid physical stock from drone or manual measurements (COALESCE logic).
     */
    public function getLatestOpnameStock(Material $material, int $year, int $month): ?float
    {
        $latestOpname = StockOpname::query()
            ->where('material_id', $material->id)
            ->whereYear('opname_date', $year)
            ->whereMonth('opname_date', $month)
            ->where(function ($q) {
                $q->where('calculated_weight_kg', '>', 0)
                    ->orWhere('calculated_volume_m3', '>', 0);
            })
            ->orderByDesc('opname_date')
            ->orderByDesc('id')
            ->first();

        if (! $latestOpname) {
            return null;
        }

        $name = strtolower(trim($material->name));
        if (str_contains($name, 'dexlite') || str_contains($name, 'solar') || strtoupper($material->unit) === 'LITER') {
            if ((float) $latestOpname->calculated_volume_m3 > 0) {
                return round((float) $latestOpname->calculated_volume_m3 * 1000.0, 2);
            }

            $density = (float) $material->density_kg_m3 ?: 840.0;

            return round(((float) $latestOpname->calculated_weight_kg / $density) * 1000.0, 2);
        }

        return (float) $latestOpname->calculated_weight_kg;
    }

    /**
     * Reconcile a single material for specified or current month.
     *
     * @return array<string, mixed>
     */
    public function reconcileMaterial(Material $material, ?int $year = null, ?int $month = null): array
    {
        $targetYear = $year ?? Carbon::now()->year;
        $targetMonth = $month ?? Carbon::now()->month;

        $stokAwal = (float) $material->initial_stock_kg;
        $penerimaan = $this->getMonthlyReceiveTotal($material, $targetYear, $targetMonth);
        $pemakaian = $this->getMonthlyProductionUsage($material, $targetYear, $targetMonth);
        // Safe addition and subtraction: (stokAwal + penerimaan - COALESCE(pemakaian, 0))
        $stokTeoritis = round($stokAwal + $penerimaan - ($pemakaian ?: 0.0), 2);

        $latestOpnameWeight = $this->getLatestOpnameStock($material, $targetYear, $targetMonth);
        $hasOpname = $latestOpnameWeight !== null;

        if (! $hasOpname) {
            return [
                'material_id' => $material->id,
                'material_name' => $material->name,
                'unit' => $material->unit,
                'year' => $targetYear,
                'month' => $targetMonth,
                'stok_awal' => $stokAwal,
                'penerimaan' => $penerimaan,
                'pemakaian' => $pemakaian,
                'stok_teoritis' => $stokTeoritis,
                'stok_fisik' => null,
                'selisih' => null,
                'deviasi' => null,
                'persentase_selisih' => null,
                'status' => self::STATUS_BELUM_DIOPNAME,
                'has_opname' => false,
            ];
        }

        $result = $this->calculateReconciliation($stokAwal, $penerimaan, $pemakaian, $latestOpnameWeight);

        return array_merge([
            'material_id' => $material->id,
            'material_name' => $material->name,
            'unit' => $material->unit,
            'year' => $targetYear,
            'month' => $targetMonth,
            'has_opname' => true,
        ], $result);
    }

    /**
     * Reconcile all active materials for executive reporting.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function reconcileAllMaterials(?int $year = null, ?int $month = null): Collection
    {
        $materials = Material::all();

        return $materials->map(function (Material $material) use ($year, $month) {
            return $this->reconcileMaterial($material, $year, $month);
        });
    }
}
