<?php

namespace App\Livewire;

use App\Models\AdditivePowder;
use App\Models\LabConversion;
use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ProcurementCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public bool $isSemen = false;

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

    public int $perPage = 10;

    // State untuk Modal Konversi Lab: Additive Powder (Kg) -> SBTJM2 (Liter)
    public bool $showConversionModal = false;

    public string $conv_date = '';

    public string|float $conv_sbtjm2_liter = 5000;

    public string|float $conv_snf1_kg = 750;

    public string|float $conv_snf2_kg = 750;

    public string|float $conv_sodium_glukonate_kg = 275;

    public string|float $conv_maltodextrine_kg = 50;

    public string|float $conv_water_kg = 3175;

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

    public function updatedPerPage(): void
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
                $this->isSemen = (strcasecmp($material->name, 'Semen') === 0);

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
                $this->isSemen = false;
            }
        } else {
            $this->masterDensity = null;
            $this->density = '';
            $this->isInternalMaterial = false;
            $this->isSemen = false;
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

        if ($vol <= 0) {
            return 0.0;
        }

        if ($this->isSemen) {
            // Untuk Semen, pecahan/input dalam Tonase (Ton), 1 Ton = 1.000 Kg
            return round($vol * 1000, 2);
        }

        if ($den <= 0) {
            return 0.0;
        }

        return round($vol * $den, 2);
    }

    #[Computed]
    public function totalAdditivePowderUsed(): float
    {
        $snf1 = (float) str_replace(',', '.', (string) $this->conv_snf1_kg);
        $snf2 = (float) str_replace(',', '.', (string) $this->conv_snf2_kg);
        $glu = (float) str_replace(',', '.', (string) $this->conv_sodium_glukonate_kg);
        $mal = (float) str_replace(',', '.', (string) $this->conv_maltodextrine_kg);

        return round($snf1 + $snf2 + $glu + $mal, 2);
    }

    public function calculateAutoRecipe(): void
    {
        $target = (float) str_replace(',', '.', (string) $this->conv_sbtjm2_liter);
        if ($target > 0) {
            // Rasio batch standar (5.000 L SBTJM2):
            // SNF 001: 750 Kg (15%)
            // SNF 002: 750 Kg (15%)
            // Sodium Glukonate: 275 Kg (5.5% ~ 6%)
            // Maltodextrine: 50 Kg (1%)
            // Air: 3.175 Kg (63.5%)
            $factor = $target / 5000.0;
            $this->conv_snf1_kg = round(750 * $factor, 2);
            $this->conv_snf2_kg = round(750 * $factor, 2);
            $this->conv_sodium_glukonate_kg = round(275 * $factor, 2);
            $this->conv_maltodextrine_kg = round(50 * $factor, 2);
            $this->conv_water_kg = round(3175 * $factor, 2);
        }
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

        if ($this->isSemen) {
            $tonInput = (float) $this->volume_m3;
            $totalWeight = round($tonInput * 1000, 2);
            $volumeM3 = $tonInput;
            $densityUsed = 1000.0;
        } else {
            $volumeM3 = (float) $this->volume_m3;
            $totalWeight = $calculator->calculateReceiveWeight($volumeM3, $densityUsed);
        }

        $riNumber = strtoupper(trim($this->ri_no));

        MaterialReceive::create([
            'purchase_order_id' => $this->purchase_order_id,
            'receive_date' => $this->receive_date,
            'supplier_id' => $this->supplier_id,
            'material_id' => $material->id,
            'vehicle_no' => strtoupper(trim($this->vehicle_no)),
            'ri_no' => $riNumber,
            'time_in' => null,
            'volume_m3' => $volumeM3,
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
        $this->isSemen = false;
        $this->material_id = null;
        $this->supplier_id = null;

        $weightDisplay = $this->isSemen
            ? number_format($totalWeight / 1000, 2, ',', '.').' Ton'
            : number_format($totalWeight, 2, ',', '.').' Kg';

        session()->flash('success', "Penerimaan material (RI: {$riNumber}) berhasil dicatat ({$weightDisplay}){$poNotice}");
        $this->dispatch('form-saved');
    }

    public function openConversionModal(): void
    {
        $this->conv_date = Carbon::now()->format('Y-m-d');
        $this->conv_sbtjm2_liter = 5000;
        $this->calculateAutoRecipe();
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

        if (is_string($this->conv_sbtjm2_liter)) {
            $this->conv_sbtjm2_liter = trim(str_replace(',', '.', $this->conv_sbtjm2_liter));
        }
        if (is_string($this->conv_snf1_kg)) {
            $this->conv_snf1_kg = trim(str_replace(',', '.', $this->conv_snf1_kg));
        }
        if (is_string($this->conv_snf2_kg)) {
            $this->conv_snf2_kg = trim(str_replace(',', '.', $this->conv_snf2_kg));
        }
        if (is_string($this->conv_sodium_glukonate_kg)) {
            $this->conv_sodium_glukonate_kg = trim(str_replace(',', '.', $this->conv_sodium_glukonate_kg));
        }
        if (is_string($this->conv_maltodextrine_kg)) {
            $this->conv_maltodextrine_kg = trim(str_replace(',', '.', $this->conv_maltodextrine_kg));
        }
        if (is_string($this->conv_water_kg)) {
            $this->conv_water_kg = trim(str_replace(',', '.', $this->conv_water_kg));
        }

        $this->validate([
            'conv_date' => 'required|date',
            'conv_sbtjm2_liter' => 'required|numeric|min:0.01|max:999999',
            'conv_snf1_kg' => 'required|numeric|min:0|max:999999',
            'conv_snf2_kg' => 'required|numeric|min:0|max:999999',
            'conv_sodium_glukonate_kg' => 'required|numeric|min:0|max:999999',
            'conv_maltodextrine_kg' => 'required|numeric|min:0|max:999999',
            'conv_water_kg' => 'nullable|numeric|min:0|max:999999',
            'conv_notes' => 'nullable|string|max:255',
        ], [
            'conv_date.required' => 'Tanggal olah lab wajib diisi.',
            'conv_sbtjm2_liter.required' => 'Hasil jadi SBTJM2 (Liter) wajib diisi.',
            'conv_sbtjm2_liter.min' => 'Hasil jadi SBTJM2 minimal 0.01 Liter.',
            'conv_snf1_kg.required' => 'Takaran SNF-C 001 wajib diisi.',
            'conv_snf2_kg.required' => 'Takaran SNF-C 002 wajib diisi.',
            'conv_sodium_glukonate_kg.required' => 'Takaran Sodium Glukonate wajib diisi.',
            'conv_maltodextrine_kg.required' => 'Takaran Maltodextrine wajib diisi.',
        ]);

        $sbtjm2Liter = (float) $this->conv_sbtjm2_liter;
        $snf1Kg = (float) $this->conv_snf1_kg;
        $snf2Kg = (float) $this->conv_snf2_kg;
        $glukonateKg = (float) $this->conv_sodium_glukonate_kg;
        $maltodextrineKg = (float) $this->conv_maltodextrine_kg;
        $waterKg = (float) ($this->conv_water_kg ?: 0);

        $totalPowderUsed = round($snf1Kg + $snf2Kg + $glukonateKg + $maltodextrineKg, 2);
        if ($totalPowderUsed <= 0) {
            $this->addError('conv_snf1_kg', 'Total serbuk kimia yang digunakan tidak boleh 0 Kg.');

            return;
        }

        // Cek kecukupan stok masing-masing powder
        $powders = AdditivePowder::all()->keyBy('code');
        $checks = [
            'SNF_001' => ['qty' => $snf1Kg, 'field' => 'conv_snf1_kg', 'name' => 'Sodium Napthalane (SNF-C) 001'],
            'SNF_002' => ['qty' => $snf2Kg, 'field' => 'conv_snf2_kg', 'name' => 'Sodium Napthalane (SNF-C) 002'],
            'SODIUM_GLUKONATE' => ['qty' => $glukonateKg, 'field' => 'conv_sodium_glukonate_kg', 'name' => 'Sodium Glukonate'],
            'MALTODEXTRINE' => ['qty' => $maltodextrineKg, 'field' => 'conv_maltodextrine_kg', 'name' => 'Maltodextrine'],
        ];

        foreach ($checks as $code => $check) {
            $powder = $powders->get($code);
            if ($powder && (float) $powder->stock_kg < $check['qty']) {
                $this->addError($check['field'], "Stok {$check['name']} tidak mencukupi (Tersisa {$powder->stock_kg} Kg).");

                return;
            }
        }

        $todayCode = Carbon::parse($this->conv_date)->format('Ymd');
        $labCount = LabConversion::whereDate('conversion_date', $this->conv_date)->count() + 1;
        $conversionNo = sprintf('LAB-%s-%03d', $todayCode, $labCount);
        $riNumber = sprintf('RI-LAB-%s-%03d', $todayCode, $labCount);

        DB::transaction(function () use ($sbtjm2Liter, $snf1Kg, $snf2Kg, $glukonateKg, $maltodextrineKg, $waterKg, $totalPowderUsed, $powders, $conversionNo, $riNumber) {
            // Potong stok masing-masing powder
            if ($p1 = $powders->get('SNF_001')) {
                $p1->decrement('stock_kg', $snf1Kg);
            }
            if ($p2 = $powders->get('SNF_002')) {
                $p2->decrement('stock_kg', $snf2Kg);
            }
            if ($p3 = $powders->get('SODIUM_GLUKONATE')) {
                $p3->decrement('stock_kg', $glukonateKg);
            }
            if ($p4 = $powders->get('MALTODEXTRINE')) {
                $p4->decrement('stock_kg', $maltodextrineKg);
            }

            // Potong stok master Additive jika ada di tabel materials
            $masterAdditive = Material::where('name', 'Additive')->first();
            if ($masterAdditive) {
                // Stock awal teoritis berkurang atau tercatat di pemakaian
            }

            $tbiSupplier = Supplier::firstOrCreate(
                ['name' => 'TBI Plant'],
                ['material_type' => 'PRODUKSI INTERNAL LAB', 'lead_time_days' => 0, 'notes' => 'Internal Production / Lab']
            );

            $sbtjm2Material = Material::firstOrCreate(
                ['name' => 'SBTJM2'],
                ['unit' => 'Liter', 'density_kg_m3' => 1172.00, 'initial_stock_kg' => 0.0]
            );

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

            // Catat entri konversi laboratorium dengan breakdown serbuk kimia
            LabConversion::create([
                'conversion_no' => $conversionNo,
                'conversion_date' => $this->conv_date,
                'additive_used_kg' => $totalPowderUsed,
                'snf1_kg' => $snf1Kg,
                'snf2_kg' => $snf2Kg,
                'sodium_glukonate_kg' => $glukonateKg,
                'maltodextrine_kg' => $maltodextrineKg,
                'water_kg' => $waterKg,
                'sbtjm2_produced_liter' => $sbtjm2Liter,
                'notes' => $this->conv_notes,
                'material_receive_id' => $receive->id,
                'created_by' => Auth::id(),
            ]);
        });

        $this->showConversionModal = false;
        $this->dispatch('open-additive-modal');

        session()->flash('success', "Konversi Lab Berhasil: {$totalPowderUsed} Kg Serbuk Additive diproses menjadi {$sbtjm2Liter} Liter SBTJM2 (No. RI: {$riNumber}, Batch: {$conversionNo}).");
    }

    public function render(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name'])->unique('name');
        // SBTJM2 dan Air dikecualikan dari penerimaan truk luar
        $materials = Material::whereNotIn('name', ['SBTJM2', 'Air'])->orderBy('name')->get(['id', 'name', 'unit']);
        $powders = AdditivePowder::orderBy('id')->get();

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
            ->paginate($this->perPage);

        $recentConversions = LabConversion::query()
            ->with(['creator', 'materialReceive'])
            ->latest('conversion_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('livewire.material-receive-form', [
            'suppliers' => $suppliers,
            'materials' => $materials,
            'powders' => $powders,
            'activePOs' => $activePOs,
            'receives' => $receives,
            'recentConversions' => $recentConversions,
        ]);
    }
}
