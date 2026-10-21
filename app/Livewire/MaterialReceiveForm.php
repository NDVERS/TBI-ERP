<?php

namespace App\Livewire;

use App\Models\LabConversion;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ProcurementCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class MaterialReceiveForm extends Component
{
    use WithPagination;

    #[Rule('nullable|exists:purchase_orders,id')]
    public ?int $purchase_order_id = null;

    public bool $is_final_unloading = false;

    #[Rule('required|date')]
    public string $receive_date = '';

    #[Rule('required|exists:suppliers,id')]
    public ?int $supplier_id = null;

    #[Rule('required|exists:materials,id')]
    public ?int $material_id = null;

    #[Rule('required|string|max:50')]
    public string $vehicle_no = '';

    #[Rule('required|string|max:50')]
    public string $ri_no = '';

    #[Rule('required|numeric|min:0.01|max:99999')]
    public string|float $density = '';

    public ?float $masterDensity = null;

    public bool $isInternalMaterial = false;

    #[Rule('required|numeric|min:0.01|max:99999')]
    public string|float $volume_m3 = '';

    public string $search = '';

    public string $filterDateRange = 'all';

    public ?string $filterStartDate = null;

    public ?string $filterEndDate = null;

    // State untuk Modal Konversi Lab: Additive (Kg) -> SBTJM2 (Liter)
    public bool $showConversionModal = false;

    public string $conv_date = '';

    public string|float $conv_additive_kg = '';

    public string|float $conv_sbtjm2_liter = '';

    public string $conv_notes = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Penerimaan Material.');
        }

        $this->receive_date = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setDateRange(string $range): void
    {
        $this->filterDateRange = $range;
        $this->resetPage();

        $today = Carbon::today();
        match ($range) {
            'today' => [
                $this->filterStartDate = $today->toDateString(),
                $this->filterEndDate = $today->toDateString(),
            ],
            'yesterday' => [
                $this->filterStartDate = $today->copy()->subDay()->toDateString(),
                $this->filterEndDate = $today->copy()->subDay()->toDateString(),
            ],
            'this_week' => [
                $this->filterStartDate = $today->copy()->startOfWeek()->toDateString(),
                $this->filterEndDate = $today->copy()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                $this->filterStartDate = $today->copy()->startOfMonth()->toDateString(),
                $this->filterEndDate = $today->copy()->endOfMonth()->toDateString(),
            ],
            default => [
                $this->filterStartDate = null,
                $this->filterEndDate = null,
            ],
        };
    }

    public function updatedPurchaseOrderId(mixed $value): void
    {
        if (! empty($value)) {
            $po = PurchaseOrder::with(['supplier', 'material'])->find($value);
            if ($po) {
                $this->supplier_id = $po->supplier_id;
                $this->material_id = $po->material_id;
                $this->updatedMaterialId($po->material_id);
            }
        } else {
            $this->is_final_unloading = false;
        }
    }

    public function updatedMaterialId(mixed $value): void
    {
        if (! empty($value)) {
            $material = Material::find($value);
            if ($material) {
                $this->masterDensity = (float) $material->density_kg_m3;
                $this->density = (string) $material->density_kg_m3;

                $matName = strtoupper($material->name);
                if (str_contains($matName, 'SBTJM2')) {
                    $this->isInternalMaterial = true;
                    $this->vehicle_no = 'INTERNAL';

                    $tbiSupplier = Supplier::firstOrCreate(
                        ['name' => 'TBI Plant'],
                        ['material_type' => 'PRODUKSI INTERNAL LAB', 'lead_time_days' => 0, 'notes' => 'Internal Production / Lab']
                    );
                    $this->supplier_id = $tbiSupplier->id;
                } else {
                    $this->isInternalMaterial = false;
                    if ($this->vehicle_no === 'INTERNAL') {
                        $this->vehicle_no = '';
                    }
                }
            } else {
                $this->masterDensity = null;
                $this->density = '';
                $this->isInternalMaterial = false;
            }
        } else {
            $this->masterDensity = null;
            $this->density = '';
            $this->isInternalMaterial = false;
        }
    }

    public function resetDensityToMaster(): void
    {
        if ($this->masterDensity !== null) {
            $this->density = (string) $this->masterDensity;
        }
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'volume_m3' && is_string($this->volume_m3)) {
            $this->volume_m3 = trim(str_replace(',', '.', $this->volume_m3));
        }

        if ($propertyName === 'density' && is_string($this->density)) {
            $this->density = trim(str_replace(',', '.', $this->density));
        }
    }

    #[Computed]
    public function totalWeight(): float
    {
        $vol = (float) str_replace(',', '.', (string) $this->volume_m3);
        $den = (float) str_replace(',', '.', (string) $this->density);

        if ($vol <= 0 || $den <= 0) {
            return 0.0;
        }

        return round($vol * $den, 2);
    }

    public function save(ProcurementCalculatorService $calculator): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Penerimaan Material.');
        }

        if (is_string($this->volume_m3)) {
            $this->volume_m3 = trim(str_replace(',', '.', $this->volume_m3));
        }

        if (is_string($this->density)) {
            $this->density = trim(str_replace(',', '.', $this->density));
        }

        if ($this->isInternalMaterial) {
            if (empty($this->vehicle_no)) {
                $this->vehicle_no = 'INTERNAL';
            }
            if (empty($this->supplier_id)) {
                $tbiSupplier = Supplier::firstOrCreate(
                    ['name' => 'TBI Plant'],
                    ['material_type' => 'PRODUKSI INTERNAL LAB', 'lead_time_days' => 0]
                );
                $this->supplier_id = $tbiSupplier->id;
            }
        }

        $this->validate();

        $material = Material::findOrFail($this->material_id);
        $densityUsed = (float) $this->density;

        // Auto-calculate total weight in Kg using user/custom density
        $totalWeight = $calculator->calculateReceiveWeight($this->volume_m3, $densityUsed);
        $riNumber = strtoupper(trim($this->ri_no));

        MaterialReceive::create([
            'purchase_order_id' => $this->purchase_order_id,
            'receive_date' => $this->receive_date,
            'supplier_id' => $this->supplier_id,
            'material_id' => $material->id,
            'vehicle_no' => strtoupper(trim($this->vehicle_no)),
            'ri_no' => $riNumber,
            'time_in' => null,
            'volume_m3' => (float) $this->volume_m3,
            'density_used' => $densityUsed,
            'total_weight_kg' => $totalWeight,
        ]);

        $poNotice = '';
        if ($this->purchase_order_id) {
            $po = PurchaseOrder::find($this->purchase_order_id);
            if ($po) {
                if ($this->is_final_unloading) {
                    $po->update(['status' => 'Selesai Seluruhnya']);
                    $poNotice = " & Status PO [{$po->po_number}] telah diselesaikan seluruhnya.";
                } elseif ($po->status === 'Sudah Tiba di Pelabuhan') {
                    $po->update(['status' => 'Penerimaan Sebagian']);
                    $poNotice = " & Status PO [{$po->po_number}] diperbarui menjadi Penerimaan Sebagian.";
                }
            }
        }

        // Reset form inputs
        $this->purchase_order_id = null;
        $this->is_final_unloading = false;
        $this->vehicle_no = '';
        $this->ri_no = '';
        $this->volume_m3 = '';
        $this->density = '';
        $this->masterDensity = null;
        $this->isInternalMaterial = false;
        $this->material_id = null;
        $this->supplier_id = null;

        $formattedKg = number_format($totalWeight, 2, ',', '.');
        session()->flash('success', "Penerimaan material (RI: {$riNumber}) berhasil dicatat ({$formattedKg} Kg){$poNotice}");
        $this->dispatch('form-saved');
    }

    public function openConversionModal(): void
    {
        $this->conv_date = Carbon::now()->format('Y-m-d');
        $this->conv_additive_kg = '';
        $this->conv_sbtjm2_liter = '';
        $this->conv_notes = '';
        $this->showConversionModal = true;
    }

    public function closeConversionModal(): void
    {
        $this->showConversionModal = false;
    }

    public function saveConversion(): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput konversi bahan kimia lab.');
        }

        if (is_string($this->conv_additive_kg)) {
            $this->conv_additive_kg = trim(str_replace(',', '.', $this->conv_additive_kg));
        }
        if (is_string($this->conv_sbtjm2_liter)) {
            $this->conv_sbtjm2_liter = trim(str_replace(',', '.', $this->conv_sbtjm2_liter));
        }

        $this->validate([
            'conv_date' => 'required|date',
            'conv_additive_kg' => 'required|numeric|min:0.01|max:999999',
            'conv_sbtjm2_liter' => 'required|numeric|min:0.01|max:999999',
            'conv_notes' => 'nullable|string|max:255',
        ], [
            'conv_date.required' => 'Tanggal olah lab wajib diisi.',
            'conv_additive_kg.required' => 'Jumlah Additive (Kg) digunakan wajib diisi.',
            'conv_additive_kg.min' => 'Jumlah Additive minimal 0.01 Kg.',
            'conv_sbtjm2_liter.required' => 'Hasil jadi SBTJM2 (Liter) wajib diisi.',
            'conv_sbtjm2_liter.min' => 'Hasil jadi SBTJM2 minimal 0.01 Liter.',
        ]);

        $additiveKg = (float) $this->conv_additive_kg;
        $sbtjm2Liter = (float) $this->conv_sbtjm2_liter;

        $tbiSupplier = Supplier::firstOrCreate(
            ['name' => 'TBI Plant'],
            ['material_type' => 'PRODUKSI INTERNAL LAB', 'lead_time_days' => 0, 'notes' => 'Internal Production / Lab']
        );

        $sbtjm2Material = Material::where('name', 'like', '%SBTJM2%')->first();
        if (! $sbtjm2Material) {
            $sbtjm2Material = Material::create([
                'name' => 'SBTJM2',
                'unit' => 'Liter',
                'density_kg_m3' => 1172.00,
                'initial_stock_kg' => 0.0,
            ]);
        }

        $todayCode = Carbon::parse($this->conv_date)->format('Ymd');
        $labCount = LabConversion::whereDate('conversion_date', $this->conv_date)->count() + 1;
        $conversionNo = sprintf('LAB-%s-%03d', $todayCode, $labCount);
        $riNumber = sprintf('RI-LAB-%s-%03d', $todayCode, $labCount);

        // Buat penerimaan internal SBTJM2 (Liter)
        $receive = MaterialReceive::create([
            'purchase_order_id' => null,
            'receive_date' => $this->conv_date,
            'supplier_id' => $tbiSupplier->id,
            'material_id' => $sbtjm2Material->id,
            'vehicle_no' => 'INTERNAL',
            'ri_no' => $riNumber,
            'time_in' => null,
            'volume_m3' => round($sbtjm2Liter / 1000, 3),
            'density_used' => (float) ($sbtjm2Material->density_kg_m3 ?? 1172.0),
            'total_weight_kg' => $sbtjm2Liter,
        ]);

        // Catat entri konversi laboratorium
        LabConversion::create([
            'conversion_no' => $conversionNo,
            'conversion_date' => $this->conv_date,
            'additive_used_kg' => $additiveKg,
            'sbtjm2_produced_liter' => $sbtjm2Liter,
            'notes' => $this->conv_notes,
            'material_receive_id' => $receive->id,
            'created_by' => Auth::id(),
        ]);

        $this->showConversionModal = false;
        $this->conv_additive_kg = '';
        $this->conv_sbtjm2_liter = '';
        $this->conv_notes = '';

        session()->flash('success', "Konversi Lab Berhasil: {$additiveKg} Kg Additive diproses menjadi {$sbtjm2Liter} Liter SBTJM2 (No. RI: {$riNumber}, Batch: {$conversionNo}).");
    }

    public function render(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name'])->unique('name');
        $materials = Material::orderBy('name')->get(['id', 'name', 'unit']);

        $activePOs = PurchaseOrder::query()
            ->with(['supplier', 'material'])
            ->whereIn('status', ['Sudah Tiba di Pelabuhan', 'Penerimaan Sebagian'])
            ->latest('po_date')
            ->latest('id')
            ->get();

        $receives = MaterialReceive::query()
            ->with(['supplier', 'material', 'purchaseOrder'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ri_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('material', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                            $pq->where('po_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->filterStartDate, fn ($q) => $q->whereDate('receive_date', '>=', $this->filterStartDate))
            ->when($this->filterEndDate, fn ($q) => $q->whereDate('receive_date', '<=', $this->filterEndDate))
            ->latest('receive_date')
            ->latest('id')
            ->paginate(10);

        $recentConversions = LabConversion::query()
            ->with(['creator', 'materialReceive'])
            ->latest('conversion_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('livewire.material-receive-form', [
            'suppliers' => $suppliers,
            'materials' => $materials,
            'activePOs' => $activePOs,
            'receives' => $receives,
            'recentConversions' => $recentConversions,
        ]);
    }
}
