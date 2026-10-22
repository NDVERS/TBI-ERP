<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Fleet;
use App\Models\FleetDailyLog;
use App\Models\FleetMaintenanceRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class FleetManagement extends Component
{
    use WithPagination;

    public string $activeTab = 'truck_mixer'; // truck_mixer, wheel_loader, others, daily_logs, maintenance

    // Daily Log Form Properties
    #[Rule('required|exists:fleets,id')]
    public ?int $selected_fleet_id = null;

    #[Rule('required|date')]
    public string $log_date = '';

    public string|float $start_hm = '';

    #[Rule('required|numeric|min:0')]
    public string|float $end_hm = '';

    public string|float $start_km = '';

    public string|float $end_km = '';

    public string|float $fuel_liters = '';

    public string|float $engine_oil_liters = '';

    public string|float $hydraulic_oil_liters = '';

    public string $driver_operator_name = '';

    public string $notes = '';

    // Maintenance Service Modal Properties
    public bool $showServiceModal = false;

    public ?int $service_fleet_id = null;

    public string $service_date = '';

    public string $service_type = 'periodic_500h';

    public string|float $hm_at_service = '';

    public string $mechanic_name = '';

    public string $service_description = '';

    public string $parts_replaced = '';

    public string|float $service_cost = '';

    // Search and Filters
    public string $search = '';

    public ?string $filterFleetType = null;

    public int $perPage = 10;

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'operator_produksi', 'petugas_gudang', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Armada & Alat Berat.');
        }

        $this->log_date = now('Asia/Makassar')->format('Y-m-d');
        $this->service_date = now('Asia/Makassar')->format('Y-m-d');

        // Default select first TM
        $firstTm = Fleet::where('type', 'truck_mixer')->orderBy('unit_code')->first();
        if ($firstTm) {
            $this->selectFleet($firstTm->id);
        }
    }

    public function updatedSelectedFleetId(int $fleetId): void
    {
        $this->selectFleet($fleetId);
    }

    public function selectFleet(int $fleetId): void
    {
        $this->selected_fleet_id = $fleetId;
        $fleet = Fleet::find($fleetId);
        if ($fleet) {
            $this->start_hm = (float) $fleet->current_hm;
            $this->end_hm = '';
            $this->start_km = $fleet->current_km !== null ? (float) $fleet->current_km : '';
            $this->end_km = '';
            $this->fuel_liters = '';
            $this->engine_oil_liters = '';
            $this->hydraulic_oil_liters = '';
            $this->resetValidation();
        }
    }

    public function updated(string $propertyName): void
    {
        $decimals = ['end_hm', 'end_km', 'fuel_liters', 'engine_oil_liters', 'hydraulic_oil_liters', 'service_cost'];
        if (in_array($propertyName, $decimals) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = trim(str_replace(',', '.', $this->{$propertyName}));
        }
    }

    #[Computed]
    public function selectedFleet(): ?Fleet
    {
        return $this->selected_fleet_id ? Fleet::find($this->selected_fleet_id) : null;
    }

    #[Computed]
    public function calculatedTotalHm(): float
    {
        $start = (float) $this->start_hm;
        $end = (float) str_replace(',', '.', (string) $this->end_hm);

        return $end > $start ? round($end - $start, 2) : 0.0;
    }

    #[Computed]
    public function calculatedTotalKm(): float
    {
        $start = (float) $this->start_km;
        $end = (float) str_replace(',', '.', (string) $this->end_km);

        return $end > $start ? round($end - $start, 2) : 0.0;
    }

    #[Computed]
    public function estimatedRemainingHm(): float
    {
        if (! $this->selectedFleet) {
            return 0.0;
        }

        $currentRem = $this->selectedFleet->remaining_hm;
        $workDone = $this->calculatedTotalHm;

        return max(0.0, round($currentRem - $workDone, 2));
    }

    #[Computed]
    public function hmTypoWarning(): ?string
    {
        if ($this->end_hm === '' || $this->end_hm === null) {
            return null;
        }
        $end = (float) str_replace(',', '.', (string) $this->end_hm);
        $start = (float) $this->start_hm;
        if ($end < $start) {
            return 'Peringatan: Angka HM baru ('.number_format($end, 2, ',', '.').') lebih kecil dari HM kemarin ('.number_format($start, 2, ',', '.').'). Periksa kemungkinan salah ketik!';
        }
        if (($end - $start) > 24) {
            return 'Peringatan: Jam kerja operasi ('.number_format($end - $start, 1, ',', '.').' jam) melebihi 24 jam dalam 1 hari! Pastikan angka meteran akurat.';
        }

        return null;
    }

    #[Computed]
    public function kmTypoWarning(): ?string
    {
        if ($this->end_km === '' || $this->end_km === null || $this->start_km === '') {
            return null;
        }
        $end = (float) str_replace(',', '.', (string) $this->end_km);
        $start = (float) $this->start_km;
        if ($end < $start) {
            return 'Peringatan: Angka KM baru ('.number_format($end, 1, ',', '.').') lebih kecil dari KM kemarin ('.number_format($start, 1, ',', '.').'). Periksa kemungkinan salah ketik!';
        }
        if (($end - $start) > 600) {
            return 'Peringatan: Jarak tempuh ('.number_format($end - $start, 0, ',', '.').' KM) sangat tinggi untuk 1 hari operasi. Pastikan angka spidometer akurat.';
        }

        return null;
    }

    public function applyQuickHmAddition(float $hours): void
    {
        $start = (float) $this->start_hm;
        $this->end_hm = (string) round($start + $hours, 2);
    }

    public function saveDailyLog(): void
    {
        $this->validate([
            'selected_fleet_id' => 'required|exists:fleets,id',
            'log_date' => 'required|date',
            'end_hm' => 'required|numeric|gte:start_hm',
            'fuel_liters' => 'nullable|numeric|min:0',
            'engine_oil_liters' => 'nullable|numeric|min:0',
            'hydraulic_oil_liters' => 'nullable|numeric|min:0',
        ], [
            'end_hm.gte' => 'Angka HM baru tidak boleh lebih kecil dari HM terakhir ('.number_format((float) $this->start_hm, 2).').',
            'end_hm.required' => 'Angka HM meteran baru wajib diisi.',
        ]);

        $fleet = Fleet::findOrFail($this->selected_fleet_id);

        $endHm = (float) str_replace(',', '.', (string) $this->end_hm);
        $startHm = (float) $this->start_hm;
        $totalHm = round($endHm - $startHm, 2);

        $startKm = $this->start_km !== '' ? (float) $this->start_km : null;
        $endKm = $this->end_km !== '' ? (float) str_replace(',', '.', (string) $this->end_km) : null;
        $totalKm = ($startKm !== null && $endKm !== null && $endKm >= $startKm) ? round($endKm - $startKm, 2) : null;

        $fuel = ! empty($this->fuel_liters) ? (float) str_replace(',', '.', (string) $this->fuel_liters) : 0.0;
        $engOil = ! empty($this->engine_oil_liters) ? (float) str_replace(',', '.', (string) $this->engine_oil_liters) : 0.0;
        $hydOil = ! empty($this->hydraulic_oil_liters) ? (float) str_replace(',', '.', (string) $this->hydraulic_oil_liters) : 0.0;

        // Create Daily Log
        $log = FleetDailyLog::create([
            'fleet_id' => $fleet->id,
            'log_date' => $this->log_date,
            'start_hm' => $startHm,
            'end_hm' => $endHm,
            'total_hm' => $totalHm,
            'start_km' => $startKm,
            'end_km' => $endKm,
            'total_km' => $totalKm,
            'fuel_liters' => $fuel,
            'engine_oil_liters' => $engOil,
            'hydraulic_oil_liters' => $hydOil,
            'driver_operator_name' => $this->driver_operator_name ? trim($this->driver_operator_name) : null,
            'notes' => $this->notes ? trim($this->notes) : null,
            'created_by' => Auth::id() ?? 1,
        ]);

        // Update Fleet's current readings
        $fleet->current_hm = $endHm;
        if ($endKm !== null) {
            $fleet->current_km = $endKm;
        }
        $fleet->save();

        ActivityLog::log(
            'create_fleet_daily_log',
            "Catat log harian {$fleet->unit_code}: +{$totalHm} HM (Total: {$endHm} HM), BBM: {$fuel} L",
            $log
        );

        session()->flash('success', "Log harian unit {$fleet->unit_code} berhasil disimpan. HM terkini: ".number_format($endHm, 2, ',', '.').' HM.');

        // Re-initialize for next entry or same unit
        $this->selectFleet($fleet->id);
    }

    public function openServiceModal(int $fleetId): void
    {
        $fleet = Fleet::findOrFail($fleetId);
        $this->service_fleet_id = $fleet->id;
        $this->service_date = now('Asia/Makassar')->format('Y-m-d');
        $this->hm_at_service = $fleet->current_hm;
        $this->service_type = $fleet->type === 'wheel_loader' ? 'periodic_monthly' : 'periodic_500h';
        $this->mechanic_name = '';
        $this->service_description = 'Periodic Maintenance rutin: Ganti oli mesin, filter oli, filter solar, dan pengecekan umum.';
        $this->parts_replaced = 'Filter Oli, Filter Solar, Oli Mesin';
        $this->service_cost = '';
        $this->showServiceModal = true;
    }

    public function closeServiceModal(): void
    {
        $this->showServiceModal = false;
        $this->service_fleet_id = null;
    }

    public function saveMaintenanceRecord(): void
    {
        $this->validate([
            'service_fleet_id' => 'required|exists:fleets,id',
            'service_date' => 'required|date',
            'hm_at_service' => 'required|numeric|min:0',
            'mechanic_name' => 'required|string|max:100',
            'service_description' => 'required|string|max:500',
        ]);

        $fleet = Fleet::findOrFail($this->service_fleet_id);
        $hmService = (float) str_replace(',', '.', (string) $this->hm_at_service);
        $cost = ! empty($this->service_cost) ? (float) str_replace(',', '.', (string) $this->service_cost) : 0.0;

        $record = FleetMaintenanceRecord::create([
            'fleet_id' => $fleet->id,
            'service_date' => $this->service_date,
            'service_type' => $this->service_type,
            'hm_at_service' => $hmService,
            'mechanic_name' => trim($this->mechanic_name),
            'description' => trim($this->service_description),
            'parts_replaced' => $this->parts_replaced ? trim($this->parts_replaced) : null,
            'cost' => $cost,
            'created_by' => Auth::id() ?? 1,
        ]);

        // Reset maintenance indicators on Fleet
        $fleet->last_service_hm = $hmService;
        $fleet->last_service_date = $this->service_date;
        $fleet->status = 'ready';
        $fleet->save();

        ActivityLog::log(
            'record_fleet_maintenance',
            "Servis berkala unit {$fleet->unit_code} oleh {$this->mechanic_name} pada {$hmService} HM. Indikator servis berhasil di-reset.",
            $record
        );

        $this->showServiceModal = false;
        session()->flash('success', "Servis unit {$fleet->unit_code} berhasil dicatat. Sisa batas HM/hari telah di-reset kembali.");

        if ($this->selected_fleet_id === $fleet->id) {
            $this->selectFleet($fleet->id);
        }
    }

    #[Computed]
    public function fleetSummaryCounts(): array
    {
        $fleets = Fleet::all();

        return [
            'total' => $fleets->count(),
            'tm_count' => $fleets->where('type', 'truck_mixer')->count(),
            'loader_count' => $fleets->where('type', 'wheel_loader')->count(),
            'others_count' => $fleets->where('type', 'others')->count(),
            'ready' => $fleets->filter(fn ($f) => ! $f->is_service_due)->count(),
            'urgent' => $fleets->filter(fn ($f) => $f->is_service_due)->count(),
            'approaching' => $fleets->filter(fn ($f) => ! $f->is_service_due && $f->remaining_hm <= 50)->count(),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render(): View
    {
        $tmFleets = Fleet::where('type', 'truck_mixer')->orderBy('unit_code')->get();
        $loaderFleet = Fleet::where('type', 'wheel_loader')->first();
        $othersFleet = Fleet::where('type', 'others')->first();

        $allFleets = Fleet::orderBy('unit_code')->get();

        $dailyLogs = FleetDailyLog::with(['fleet', 'creator'])
            ->when($this->search, function ($q) {
                $q->whereHas('fleet', fn ($fq) => $fq->where('unit_code', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"))
                    ->orWhere('driver_operator_name', 'like', "%{$this->search}%");
            })
            ->latest('log_date')
            ->latest('id')
            ->paginate($this->perPage, ['*'], 'logsPage');

        $maintenanceRecords = FleetMaintenanceRecord::with(['fleet', 'creator'])
            ->latest('service_date')
            ->latest('id')
            ->paginate($this->perPage, ['*'], 'maintenancePage');

        return view('livewire.fleet-management', [
            'tmFleets' => $tmFleets,
            'loaderFleet' => $loaderFleet,
            'othersFleet' => $othersFleet,
            'allFleets' => $allFleets,
            'dailyLogs' => $dailyLogs,
            'maintenanceRecords' => $maintenanceRecords,
            'summaryCounts' => $this->fleetSummaryCounts,
        ]);
    }
}
