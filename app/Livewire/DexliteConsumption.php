<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Fleet;
use App\Models\FleetDailyLog;
use App\Models\FuelReceive;
use App\Models\LubricantTransfer;
use App\Models\Material;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buku Konsumsi Dexlite & Pelumas — TBI ERP')]
class DexliteConsumption extends Component
{
    public string $activeTab = 'matrix'; // matrix, indices, receives, sounding, lubricants

    public string $selectedMonth = '2026-10';

    public float $referenceConcreteVolume = 747.3; // Volume m³ produksi cor referensi dari sheet Dexlite

    // Tank Sounding Calculator inputs
    public float $tankRadiusCm = 70.0; // r dalam cm (misal radius tangki)

    public float $tankLengthCm = 300.0; // panjang/tinggi tangki dalam cm

    public float $liquidLevelCm = 74.3; // tinggi celup cairan

    public string $tankOrientation = 'vertical'; // vertical / horizontal

    // Modal Catat Penerimaan Solar
    public bool $showReceiveModal = false;

    public string $receive_date = '';

    public string $supplier_name = 'PT Pertamina Patra Niaga';

    public string $ri_do_number = '';

    public string $vehicle_no = '';

    public string $volume_liters = '';

    public string $receive_notes = '';

    // Modal Catat Pemakaian Harian
    public bool $showUsageModal = false;

    public string $usage_date = '';

    public ?int $usage_fleet_id = null;

    public string $usage_fuel_liters = '';

    public string $usage_engine_oil = '';

    public string $usage_hydraulic_oil = '';

    public string $usage_notes = '';

    // QoL: Previous fill info & quick fill for Usage Modal
    public ?float $lastUsageFuel = null;

    public ?string $lastUsageDate = null;

    // QoL: WhatsApp Share Modal Properties
    public bool $showShareModal = false;

    public string $shareDate = '';

    public function mount(): void
    {
        $this->receive_date = now('Asia/Makassar')->format('Y-m-d');
        $this->usage_date = now('Asia/Makassar')->format('Y-m-d');
        $this->shareDate = now('Asia/Makassar')->format('Y-m-d');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function initialStock(): float
    {
        // 1.234,85 Liter
        $dexlite = Material::where('name', 'Dexlite')->first();

        return $dexlite ? (float) $dexlite->initial_stock_kg : 1234.85;
    }

    #[Computed]
    public function totalReceives(): float
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        return (float) FuelReceive::whereBetween('receive_date', [$start, $end])->sum('volume_liters');
    }

    #[Computed]
    public function totalUsage(): float
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        return (float) FleetDailyLog::whereBetween('log_date', [$start, $end])->sum('fuel_liters');
    }

    #[Computed]
    public function tmUsage(): float
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        return (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('type', 'truck_mixer'))
            ->whereBetween('log_date', [$start, $end])
            ->sum('fuel_liters');
    }

    #[Computed]
    public function loaderUsage(): float
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        return (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('unit_code', 'Wheel Loader'))
            ->whereBetween('log_date', [$start, $end])
            ->sum('fuel_liters');
    }

    #[Computed]
    public function othersUsage(): float
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        return (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('type', 'others'))
            ->whereBetween('log_date', [$start, $end])
            ->sum('fuel_liters');
    }

    #[Computed]
    public function endingCalculatedStock(): float
    {
        return round($this->initialStock + $this->totalReceives - $this->totalUsage, 2);
    }

    #[Computed]
    public function physicalSoundingStock(): float
    {
        // Dari data rekonsiliasi opname fisik Dexlite tangki
        $dexlite = Material::where('name', 'Dexlite')->first();
        if ($dexlite) {
            $latest = StockOpname::where('material_id', $dexlite->id)->latest('opname_date')->first();
            if ($latest) {
                return (float) ($latest->volume_liter ?? 1144.73);
            }
        }

        return 1144.73;
    }

    #[Computed]
    public function reconciliationDiscrepancy(): float
    {
        return round($this->endingCalculatedStock - $this->physicalSoundingStock, 2);
    }

    #[Computed]
    public function averageDailyBurnRate(): float
    {
        $activeDays = 0;
        foreach ($this->dailyMatrixRows as $row) {
            if ($row['daily_total'] > 0) {
                $activeDays++;
            }
        }

        return $activeDays > 0 ? round($this->totalUsage / $activeDays, 1) : 533.2;
    }

    #[Computed]
    public function daysRemaining(): float
    {
        $rate = max(1.0, $this->averageDailyBurnRate);

        return round($this->physicalSoundingStock / $rate, 1);
    }

    #[Computed]
    public function isLowStock(): bool
    {
        return $this->physicalSoundingStock < 2000.0;
    }

    #[Computed]
    public function isCriticalStock(): bool
    {
        return $this->physicalSoundingStock < 1000.0;
    }

    #[Computed]
    public function anomalousFills(): array
    {
        $anomalies = [];
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        $logs = FleetDailyLog::with('fleet')
            ->whereBetween('log_date', [$start, $end])
            ->where('fuel_liters', '>', 0)
            ->get();

        foreach ($logs as $log) {
            $vol = (float) $log->fuel_liters;
            $code = $log->fleet->unit_code;
            $isTm = str_starts_with($code, 'TM ') && $code !== 'TM Rental';

            if ($isTm && $vol > 200.0) {
                $anomalies[] = [
                    'date' => $log->log_date->format('d M Y'),
                    'raw_date' => $log->log_date->toDateString(),
                    'unit' => $code,
                    'volume' => $vol,
                    'reason' => "Pengisian tinggi ({$vol} L) melebihi kapasitas standar harian tangki TM",
                    'severity' => 'warning',
                ];
            } elseif ($code === 'Wheel Loader' && $vol > 300.0) {
                $anomalies[] = [
                    'date' => $log->log_date->format('d M Y'),
                    'raw_date' => $log->log_date->toDateString(),
                    'unit' => $code,
                    'volume' => $vol,
                    'reason' => "Pengisian Wheel Loader tinggi ({$vol} L)",
                    'severity' => 'warning',
                ];
            }
        }

        return $anomalies;
    }

    #[Computed]
    public function calculatedSoundingLiters(): float
    {
        // V = pi * r^2 * t (dalam cm^3 / 1000 = Liter)
        $r = max(0.1, $this->tankRadiusCm);
        $h = max(0, $this->liquidLevelCm);

        $cm3 = pi() * pow($r, 2) * $h;

        return round($cm3 / 1000.0, 2);
    }

    #[Computed]
    public function matrixColumns(): array
    {
        return [
            'TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06',
            'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12',
            'Wheel Loader', 'TM Rental', 'Excavator', 'Concrete Pump', 'Hilux', 'Lain-lain',
        ];
    }

    #[Computed]
    public function dailyMatrixRows(): array
    {
        $startDate = Carbon::parse($this->selectedMonth.'-01')->startOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $startStr = $startDate->toDateString();
        $endStr = $startDate->copy()->endOfMonth()->toDateString();

        // Ambil data penerimaan
        $receivesByDate = FuelReceive::whereBetween('receive_date', [$startStr, $endStr])
            ->get()
            ->groupBy(fn ($item) => $item->receive_date->toDateString());

        // Ambil data log BBM per unit
        $logs = FleetDailyLog::with('fleet')
            ->whereBetween('log_date', [$startStr, $endStr])
            ->get();

        $logsByDate = [];
        foreach ($logs as $log) {
            $d = $log->log_date->toDateString();
            $code = $log->fleet->unit_code;
            $logsByDate[$d][$code] = (float) $log->fuel_liters;
        }

        $rows = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startDate->copy()->day($day)->toDateString();
            $receiveVol = isset($receivesByDate[$date]) ? (float) $receivesByDate[$date]->sum('volume_liters') : 0.0;

            $units = [];
            $tmSubtotal = 0.0;
            $dailyTotal = 0.0;

            foreach ($this->matrixColumns as $col) {
                $val = $logsByDate[$date][$col] ?? 0.0;
                $units[$col] = $val;

                if (str_starts_with($col, 'TM ') && $col !== 'TM Rental') {
                    $tmSubtotal += $val;
                }
                $dailyTotal += $val;
            }

            $rows[] = [
                'day' => sprintf('%02d', $day),
                'date' => $date,
                'receive_vol' => $receiveVol,
                'units' => $units,
                'tm_subtotal' => $tmSubtotal,
                'daily_total' => $dailyTotal,
            ];
        }

        return $rows;
    }

    #[Computed]
    public function matrixTotals(): array
    {
        $totals = [
            'receive_vol' => 0.0,
            'tm_subtotal' => 0.0,
            'daily_total' => 0.0,
            'units' => [],
        ];

        foreach ($this->matrixColumns as $col) {
            $totals['units'][$col] = 0.0;
        }

        foreach ($this->dailyMatrixRows as $row) {
            $totals['receive_vol'] += $row['receive_vol'];
            $totals['tm_subtotal'] += $row['tm_subtotal'];
            $totals['daily_total'] += $row['daily_total'];

            foreach ($this->matrixColumns as $col) {
                $totals['units'][$col] += $row['units'][$col];
            }
        }

        return $totals;
    }

    #[Computed]
    public function indices(): array
    {
        $vol = max(1.0, $this->referenceConcreteVolume);

        $loaderVal = round($this->loaderUsage / $vol, 2);
        $gensetVal = round($this->othersUsage / $vol, 2);
        $tmVal = round($this->tmUsage / $vol, 2);
        $allVal = round($this->totalUsage / $vol, 2);

        return [
            [
                'unit' => '1. Loader',
                'fuel_used' => $this->loaderUsage,
                'real_index' => $loaderVal,
                'normal_index' => 0.50,
                'status' => $loaderVal <= 0.50 ? 'Pertahankan' : 'Perbaiki',
                'status_color' => $loaderVal <= 0.50 ? 'emerald' : 'red',
            ],
            [
                'unit' => '2. Genset / Operasional Lapangan',
                'fuel_used' => $this->othersUsage,
                'real_index' => $gensetVal,
                'normal_index' => 1.00,
                'status' => $gensetVal <= 1.00 ? 'Pertahankan' : 'Perbaiki',
                'status_color' => $gensetVal <= 1.00 ? 'emerald' : 'red',
            ],
            [
                'unit' => '3. Truck Mixer (11 Unit)',
                'fuel_used' => $this->tmUsage,
                'real_index' => $tmVal,
                'normal_index' => 3.00,
                'status' => $tmVal <= 3.00 ? 'Pertahankan' : 'Perbaiki',
                'status_color' => $tmVal <= 3.00 ? 'emerald' : 'red',
            ],
            [
                'unit' => '5. Total Keseluruhan (All)',
                'fuel_used' => $this->totalUsage,
                'real_index' => $allVal,
                'normal_index' => 5.00,
                'status' => $allVal <= 5.00 ? 'Pertahankan' : 'Perbaiki',
                'status_color' => $allVal <= 5.00 ? 'emerald' : 'red',
            ],
        ];
    }

    #[Computed]
    public function allFleets()
    {
        return Fleet::orderBy('unit_code')->get();
    }

    public function openReceiveModal(): void
    {
        $this->receive_date = now('Asia/Makassar')->format('Y-m-d');
        $this->supplier_name = 'PT Pertamina Patra Niaga';
        $this->ri_do_number = 'DO-PRT/'.date('y/m/').rand(100, 999);
        $this->vehicle_no = 'KT 8'.rand(100, 999).' AB';
        $this->volume_liters = '';
        $this->receive_notes = '';
        $this->showReceiveModal = true;
    }

    public function closeReceiveModal(): void
    {
        $this->showReceiveModal = false;
    }

    public function saveFuelReceive(): void
    {
        $this->validate([
            'receive_date' => 'required|date',
            'supplier_name' => 'required|string|max:150',
            'volume_liters' => 'required|numeric|min:1',
            'ri_do_number' => 'nullable|string|max:100',
        ]);

        $vol = (float) str_replace(',', '.', (string) $this->volume_liters);

        $receive = FuelReceive::create([
            'receive_date' => $this->receive_date,
            'supplier_name' => $this->supplier_name,
            'ri_do_number' => $this->ri_do_number,
            'vehicle_no' => $this->vehicle_no ?: null,
            'volume_liters' => $vol,
            'notes' => $this->receive_notes ?: null,
            'created_by' => Auth::id() ?? 1,
        ]);

        ActivityLog::log(
            'create_fuel_receive',
            "Catat penerimaan solar Dexlite masuk: +{$vol} L ({$this->supplier_name})",
            $receive
        );

        $this->closeReceiveModal();
        session()->flash('success', "Penerimaan solar Dexlite sebesar {$vol} Liter berhasil disimpan.");
    }

    public function openUsageModal(): void
    {
        $this->usage_date = now('Asia/Makassar')->format('Y-m-d');
        $firstFleet = Fleet::first();
        $this->usage_fleet_id = $firstFleet ? $firstFleet->id : null;
        $this->loadLastUsageInfo($this->usage_fleet_id);
        $this->usage_fuel_liters = '';
        $this->usage_engine_oil = '';
        $this->usage_hydraulic_oil = '';
        $this->usage_notes = '';
        $this->showUsageModal = true;
    }

    public function updatedUsageFleetId($fleetId): void
    {
        $this->loadLastUsageInfo($fleetId ? (int) $fleetId : null);
    }

    public function loadLastUsageInfo(?int $fleetId): void
    {
        if (! $fleetId) {
            $this->lastUsageFuel = null;
            $this->lastUsageDate = null;

            return;
        }

        $lastLog = FleetDailyLog::where('fleet_id', $fleetId)
            ->where('fuel_liters', '>', 0)
            ->orderByDesc('log_date')
            ->first();

        if ($lastLog) {
            $this->lastUsageFuel = (float) $lastLog->fuel_liters;
            $this->lastUsageDate = $lastLog->log_date->format('d M Y');
        } else {
            $this->lastUsageFuel = null;
            $this->lastUsageDate = null;
        }
    }

    public function setQuickFuel(float $liters): void
    {
        $this->usage_fuel_liters = (string) $liters;
    }

    #[Computed]
    public function usageTypoWarning(): ?string
    {
        if (empty($this->usage_fuel_liters)) {
            return null;
        }

        $vol = (float) str_replace(',', '.', (string) $this->usage_fuel_liters);
        if ($vol > 250.0) {
            return "Peringatan: Volume {$vol} Liter melebihi kapasitas standar tangki TM (~200L). Pastikan tidak salah ketik!";
        }
        if ($vol <= 0) {
            return 'Volume BBM harus lebih besar dari 0 Liter.';
        }

        return null;
    }

    public function closeUsageModal(): void
    {
        $this->showUsageModal = false;
    }

    public function openShareModal(): void
    {
        $this->shareDate = now('Asia/Makassar')->format('Y-m-d');
        $this->showShareModal = true;
    }

    public function closeShareModal(): void
    {
        $this->showShareModal = false;
    }

    #[Computed]
    public function whatsappDailyText(): string
    {
        $date = ! empty($this->shareDate) ? $this->shareDate : now('Asia/Makassar')->format('Y-m-d');
        $carbonDate = Carbon::parse($date);
        $formattedDate = $carbonDate->isoFormat('dddd, D MMMM Y');

        $receiveToday = (float) FuelReceive::where('receive_date', $date)->sum('volume_liters');
        $tmToday = (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('type', 'truck_mixer'))
            ->where('log_date', $date)
            ->sum('fuel_liters');
        $loaderToday = (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('unit_code', 'Wheel Loader'))
            ->where('log_date', $date)
            ->sum('fuel_liters');
        $othersToday = (float) FleetDailyLog::whereHas('fleet', fn ($q) => $q->where('type', 'others'))
            ->where('log_date', $date)
            ->sum('fuel_liters');
        $totalToday = $tmToday + $loaderToday + $othersToday;

        $text = "*REKAP HARIAN KONSUMSI DEXLITE / BBM*\n";
        $text .= "🏢 *PT TB BETON INDONESIA — PLANT RINGROAD 3*\n";
        $text .= "📅 *Tanggal:* {$formattedDate}\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= '⛽ *Penerimaan Tangki:* '.($receiveToday > 0 ? '+'.number_format($receiveToday, 1, ',', '.').' L' : 'Nihil (0 L)')."\n";
        $text .= '🚚 *Pemakaian TM (11 Unit):* '.number_format($tmToday, 0, ',', '.')." L\n";
        $text .= '🚜 *Wheel Loader (Stockpile):* '.number_format($loaderToday, 0, ',', '.')." L\n";
        if ($othersToday > 0) {
            $text .= '🛠️ *Lain-lain / Rental / Pump:* '.number_format($othersToday, 0, ',', '.')." L\n";
        }
        $text .= '📊 *Total Pemakaian Hari Ini:* '.number_format($totalToday, 0, ',', '.')." L\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= '🛢️ *Sisa Stok Tangki (Sounding):* '.number_format($this->physicalSoundingStock, 2, ',', '.')." L\n";
        $text .= '⏱️ *Estimasi Ketahanan:* ~'.$this->daysRemaining." Hari Produksi\n";
        $tmIdx = $this->referenceConcreteVolume > 0 ? round($this->tmUsage / $this->referenceConcreteVolume, 2) : 0;
        $text .= '📈 *Indeks TM:* '.number_format($tmIdx, 2, ',', '.')." L/m³ (Target: ≤ 3.0)\n\n";
        $text .= '_Laporan dibuat otomatis via Sistem TBI ERP Plant_';

        return $text;
    }

    public function exportExcel()
    {
        $month = $this->selectedMonth;
        $fileName = "LAPORAN_PEMAKAIAN_SOLAR_DTM_PLANT_TBI_{$month}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $rows = $this->dailyMatrixRows;
        $totals = $this->matrixTotals;
        $initStock = $this->initialStock;
        $totReceives = $this->totalReceives;
        $totUsage = $this->totalUsage;
        $physStock = $this->physicalSoundingStock;

        $callback = function () use ($rows, $totals, $initStock, $totReceives, $totUsage, $physStock, $month) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['PT TB BETON INDONESIA - READY MIX PLANT SAMARINDA'], ';');
            fputcsv($handle, ["LAPORAN PEMAKAIAN SOLAR DEXLITE BULAN: {$month}"], ';');
            fputcsv($handle, [
                'STOK AWAL: '.number_format($initStock, 2, ',', '.').' L',
                'PENERIMAAN: '.number_format($totReceives, 2, ',', '.').' L',
                'PEMAKAIAN: '.number_format($totUsage, 2, ',', '.').' L',
                'SISA TANGKI (SOUNDING): '.number_format($physStock, 2, ',', '.').' L',
            ], ';');
            fputcsv($handle, [], ';');

            // Column Header
            $headerCols = [
                'TANGGAL', 'PENERIMAAN (L)',
                'TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06',
                'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12',
                'TOTAL TM', 'LOADER', 'TM RENTAL', 'EXCAVATOR', 'CP PUMP', 'HILUX', 'TOTAL HARI (L)',
            ];
            fputcsv($handle, $headerCols, ';');

            foreach ($rows as $r) {
                $rowVals = [
                    $r['day'].' '.Carbon::parse($r['date'])->format('M Y'),
                    $r['receive_vol'] > 0 ? number_format($r['receive_vol'], 1, ',', '.') : '-',
                ];
                foreach (['TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06', 'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12'] as $tm) {
                    $v = $r['units'][$tm] ?? 0;
                    $rowVals[] = $v > 0 ? number_format($v, 0, ',', '.') : '-';
                }
                $rowVals[] = $r['tm_subtotal'] > 0 ? number_format($r['tm_subtotal'], 0, ',', '.') : '-';
                $wl = $r['units']['Wheel Loader'] ?? 0;
                $rowVals[] = $wl > 0 ? number_format($wl, 0, ',', '.') : '-';
                foreach (['TM Rental', 'Excavator', 'Concrete Pump', 'Hilux'] as $op) {
                    $opv = $r['units'][$op] ?? 0;
                    $rowVals[] = $opv > 0 ? number_format($opv, 0, ',', '.') : '-';
                }
                $rowVals[] = $r['daily_total'] > 0 ? number_format($r['daily_total'], 0, ',', '.') : '-';

                fputcsv($handle, $rowVals, ';');
            }

            // Footer TOTAL
            $footerVals = [
                'TOTAL',
                number_format($totals['receive_vol'], 1, ',', '.'),
            ];
            foreach (['TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06', 'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12'] as $tm) {
                $footerVals[] = number_format($totals['units'][$tm] ?? 0, 0, ',', '.');
            }
            $footerVals[] = number_format($totals['tm_subtotal'], 0, ',', '.');
            $footerVals[] = number_format($totals['units']['Wheel Loader'] ?? 0, 0, ',', '.');
            foreach (['TM Rental', 'Excavator', 'Concrete Pump', 'Hilux'] as $op) {
                $footerVals[] = number_format($totals['units'][$op] ?? 0, 0, ',', '.');
            }
            $footerVals[] = number_format($totals['daily_total'], 0, ',', '.');

            fputcsv($handle, $footerVals, ';');
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function saveDailyFuelLog(): void
    {
        $this->validate([
            'usage_date' => 'required|date',
            'usage_fleet_id' => 'required|exists:fleets,id',
            'usage_fuel_liters' => 'required|numeric|min:0.1',
        ]);

        $fleet = Fleet::findOrFail($this->usage_fleet_id);
        $fuel = (float) str_replace(',', '.', (string) $this->usage_fuel_liters);
        $engOil = ! empty($this->usage_engine_oil) ? (float) str_replace(',', '.', (string) $this->usage_engine_oil) : 0.0;
        $hydOil = ! empty($this->usage_hydraulic_oil) ? (float) str_replace(',', '.', (string) $this->usage_hydraulic_oil) : 0.0;

        $log = FleetDailyLog::updateOrCreate(
            [
                'fleet_id' => $fleet->id,
                'log_date' => $this->usage_date,
            ],
            [
                'fuel_liters' => $fuel,
                'engine_oil_liters' => $engOil,
                'hydraulic_oil_liters' => $hydOil,
                'notes' => $this->usage_notes ?: null,
                'created_by' => Auth::id() ?? 1,
            ]
        );

        ActivityLog::log(
            'create_fuel_usage',
            "Catat pemakaian BBM unit {$fleet->unit_code}: {$fuel} L",
            $log
        );

        $this->closeUsageModal();
        session()->flash('success', "Pengisian BBM unit {$fleet->unit_code} sebesar {$fuel} Liter berhasil dicatat.");
    }

    public function render()
    {
        $start = Carbon::parse($this->selectedMonth.'-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($this->selectedMonth.'-01')->endOfMonth()->toDateString();

        $receivesList = FuelReceive::whereBetween('receive_date', [$start, $end])
            ->orderBy('receive_date')
            ->get();

        $transfersList = LubricantTransfer::whereBetween('transfer_date', [$start, $end])
            ->orderBy('transfer_date')
            ->get();

        return view('livewire.dexlite-consumption', [
            'receivesList' => $receivesList,
            'transfersList' => $transfersList,
        ]);
    }
}
