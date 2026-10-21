<?php

namespace App\Livewire;

use App\Models\Material;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\OpnameGeometryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class StockOpnameForm extends Component
{
    use WithPagination;

    #[Rule('required|date')]
    public string $opname_date = '';

    #[Rule('required|exists:materials,id')]
    public ?int $material_id = null;

    #[Rule('required|in:limas_terpancung,kerucut_terpancung')]
    public string $shape_type = 'limas_terpancung';

    #[Rule('required|numeric|min:0.01|max:999')]
    public string|float $tinggi = '';

    // Dimensi Limas Terpancung: Luas Bidang Atas (La) & Bawah (Lb)
    public string|float $la = '';

    public string|float $lb = '';

    // Dimensi Kerucut Terpancung: Jari-jari Atas (Ra) & Bawah (Rb)
    public string|float $ra = '';

    public string|float $rb = '';

    // Stock Bin tambahan di hopper / batcher
    public string|float $stock_bin = '';

    public ?string $selectedMaterialUnit = 'Kg';

    public bool $isSemen = false;

    public int $perPage = 10;

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'surveyor_opname', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Stock Opname.');
        }

        $this->opname_date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedMaterialId(): void
    {
        $material = $this->material_id ? Material::find($this->material_id) : null;
        if ($material) {
            $this->selectedMaterialUnit = $material->unit;
            $this->isSemen = (strcasecmp($material->name, 'Semen') === 0 || strtoupper($material->unit) === 'TON');
        } else {
            $this->selectedMaterialUnit = 'Kg';
            $this->isSemen = false;
        }
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['tinggi', 'la', 'lb', 'ra', 'rb', 'stock_bin']) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = trim(str_replace(',', '.', $this->{$propertyName}));
        }
    }

    private function normalizeDecimals(): void
    {
        foreach (['tinggi', 'la', 'lb', 'ra', 'rb', 'stock_bin'] as $prop) {
            if (is_string($this->{$prop})) {
                $this->{$prop} = trim(str_replace(',', '.', $this->{$prop}));
            }
        }
    }

    public function updatedShapeType(): void
    {
        $this->resetValidation();
    }

    public function save(OpnameGeometryService $geometryService): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'surveyor_opname', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Stock Opname.');
        }

        $this->normalizeDecimals();

        // Custom dynamic validation based on shape type
        $rules = [
            'opname_date' => 'required|date',
            'material_id' => 'required|exists:materials,id',
            'shape_type' => 'required|in:limas_terpancung,kerucut_terpancung',
            'tinggi' => 'required|numeric|min:0.01|max:999',
            'stock_bin' => 'nullable|numeric|min:0',
        ];

        if ($this->shape_type === 'limas_terpancung') {
            $rules['la'] = 'required|numeric|min:0.01|max:99999';
            $rules['lb'] = 'required|numeric|min:0.01|max:99999';
        } else {
            $rules['ra'] = 'required|numeric|min:0.01|max:999';
            $rules['rb'] = 'required|numeric|min:0.01|max:999';
        }

        $this->validate($rules, [
            'tinggi.required' => 'Tinggi timbunan (t) wajib diisi.',
            'la.required' => 'Luas bidang atas (La) wajib diisi untuk bentuk limas.',
            'lb.required' => 'Luas bidang bawah (Lb) wajib diisi untuk bentuk limas.',
            'ra.required' => 'Jari-jari atas (Ra) wajib diisi untuk bentuk kerucut.',
            'rb.required' => 'Jari-jari bawah (Rb) wajib diisi untuk bentuk kerucut.',
        ]);

        $material = Material::findOrFail($this->material_id);
        $isSemen = (strcasecmp($material->name, 'Semen') === 0 || strtoupper($material->unit) === 'TON');

        // Prepare dimensions payload
        // Jika Semen, stock_bin diinput dalam satuan Ton (1 Ton = 1.000 Kg)
        $stockBinVal = (float) ($this->stock_bin ?: 0);
        $stockBinKg = $isSemen ? ($stockBinVal * 1000.0) : $stockBinVal;

        $dimensions = [
            'shape' => $this->shape_type,
            'tinggi' => (float) $this->tinggi,
            'stock_bin' => $stockBinKg,
            'stock_bin_input' => $stockBinVal,
            'stock_bin_unit' => $material->unit,
        ];

        if ($this->shape_type === 'limas_terpancung') {
            $dimensions['la'] = (float) $this->la;
            $dimensions['lb'] = (float) $this->lb;
        } else {
            $dimensions['ra'] = (float) $this->ra;
            $dimensions['rb'] = (float) $this->rb;
        }

        // Calculate volume and weight secretly on server (Zero Formula Exposure)
        $density = (float) $material->density_kg_m3;
        $calc = $geometryService->calculateOpname($this->shape_type, $dimensions, $density);

        // Determine user creator
        $userId = Auth::id() ?? User::where('role', 'surveyor_opname')->value('id') ?? User::first()?->id ?? 1;

        StockOpname::create([
            'opname_date' => $this->opname_date,
            'material_id' => $material->id,
            'method' => $this->shape_type,
            'dimensions_json' => $dimensions,
            'calculated_volume_m3' => $calc['calculated_volume_m3'],
            'calculated_weight_kg' => $calc['calculated_weight_kg'],
            'created_by' => $userId,
        ]);

        // Reset form inputs except date & shape
        $this->material_id = null;
        $this->tinggi = '';
        $this->la = '';
        $this->lb = '';
        $this->ra = '';
        $this->rb = '';
        $this->stock_bin = '';
        $this->selectedMaterialUnit = 'Kg';
        $this->isSemen = false;

        // Strict: Surveyor only gets simple confirmation, no volume or kg values are returned to UI
        session()->flash('success', 'Data Opname Berhasil Disimpan & Dikirim ke Server');
    }

    public function render(): View
    {
        // Aggregate / sand materials typically surveyed in stock piles
        $materials = Material::orderBy('name')->get(['id', 'name', 'unit']);

        $opnames = StockOpname::query()
            ->with(['material', 'creator'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('method', 'like', "%{$search}%")
                        ->orWhereHas('material', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('opname_date')
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.stock-opname-form', [
            'materials' => $materials,
            'opnames' => $opnames,
        ]);
    }
}
