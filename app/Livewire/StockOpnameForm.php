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

    // Mode Input Limas: 'sisi' (Panjang & Lebar) atau 'luas' (Luas Langsung)
    public string $limas_input_mode = 'sisi';

    // Dimensi Sisi Limas Terpancung (Meter)
    public string|float $pa = '';

    public string|float $la_side = '';

    public string|float $pb = '';

    public string|float $lb_side = '';

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
        $dimensionProps = ['tinggi', 'la', 'lb', 'ra', 'rb', 'stock_bin', 'pa', 'la_side', 'pb', 'lb_side'];
        if (in_array($propertyName, $dimensionProps) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = trim(str_replace(',', '.', $this->{$propertyName}));
        }

        // Otomatis hitung Luas Atas (La) jika pa dan la_side diisi
        if (in_array($propertyName, ['pa', 'la_side']) && $this->limas_input_mode === 'sisi') {
            $p = (float) str_replace(',', '.', (string) $this->pa);
            $l = (float) str_replace(',', '.', (string) $this->la_side);
            $this->la = ($p > 0 && $l > 0) ? round($p * $l, 2) : '';
        }

        // Otomatis hitung Luas Bawah (Lb) jika pb dan lb_side diisi
        if (in_array($propertyName, ['pb', 'lb_side']) && $this->limas_input_mode === 'sisi') {
            $p = (float) str_replace(',', '.', (string) $this->pb);
            $l = (float) str_replace(',', '.', (string) $this->lb_side);
            $this->lb = ($p > 0 && $l > 0) ? round($p * $l, 2) : '';
        }
    }

    private function normalizeDecimals(): void
    {
        $dimensionProps = ['tinggi', 'la', 'lb', 'ra', 'rb', 'stock_bin', 'pa', 'la_side', 'pb', 'lb_side'];
        foreach ($dimensionProps as $prop) {
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
            if ($this->limas_input_mode === 'sisi') {
                if (! empty($this->la) && ! empty($this->lb) && empty($this->pa) && empty($this->pb)) {
                    $rules['la'] = 'required|numeric|min:0.01|max:99999';
                    $rules['lb'] = 'required|numeric|min:0.01|max:99999';
                } else {
                    $rules['pa'] = 'required|numeric|min:0.01|max:9999';
                    $rules['la_side'] = 'required|numeric|min:0.01|max:9999';
                    $rules['pb'] = 'required|numeric|min:0.01|max:9999';
                    $rules['lb_side'] = 'required|numeric|min:0.01|max:9999';
                }
            } else {
                $rules['la'] = 'required|numeric|min:0.01|max:99999';
                $rules['lb'] = 'required|numeric|min:0.01|max:99999';
            }
        } else {
            $rules['ra'] = 'required|numeric|min:0.01|max:999';
            $rules['rb'] = 'required|numeric|min:0.01|max:999';
        }

        $this->validate($rules, [
            'tinggi.required' => 'Tinggi timbunan (t) wajib diisi.',
            'pa.required' => 'Panjang bidang atas (pa) wajib diisi.',
            'la_side.required' => 'Lebar bidang atas (la) wajib diisi.',
            'pb.required' => 'Panjang bidang bawah (pb) wajib diisi.',
            'lb_side.required' => 'Lebar bidang bawah (lb) wajib diisi.',
            'la.required' => 'Luas bidang atas (La) wajib diisi.',
            'lb.required' => 'Luas bidang bawah (Lb) wajib diisi.',
            'ra.required' => 'Jari-jari atas (Ra) wajib diisi untuk bentuk kerucut.',
            'rb.required' => 'Jari-jari bawah (Rb) wajib diisi untuk bentuk kerucut.',
        ]);

        // Hitung luas atas dan bawah jika mode sisi
        if ($this->shape_type === 'limas_terpancung' && $this->limas_input_mode === 'sisi' && ! empty($this->pa) && ! empty($this->la_side)) {
            $this->la = round((float) $this->pa * (float) $this->la_side, 2);
            $this->lb = round((float) $this->pb * (float) $this->lb_side, 2);
        }

        $material = Material::findOrFail($this->material_id);
        $isSemen = (strcasecmp($material->name, 'Semen') === 0 || strtoupper($material->unit) === 'TON');

        // Prepare dimensions payload
        // Jika Semen, stock_bin diinput dalam satuan Ton (1 Ton = 1.000 Kg)
        $stockBinVal = (float) ($this->stock_bin ?: 0);
        $stockBinKg = $isSemen ? ($stockBinVal * 1000.0) : $stockBinVal;

        $dimensions = [
            'shape' => $this->shape_type,
            'input_mode' => $this->limas_input_mode,
            'tinggi' => (float) $this->tinggi,
            'stock_bin' => $stockBinKg,
            'stock_bin_input' => $stockBinVal,
            'stock_bin_unit' => $material->unit,
        ];

        if ($this->shape_type === 'limas_terpancung') {
            $dimensions['la'] = (float) $this->la;
            $dimensions['lb'] = (float) $this->lb;
            if (! empty($this->pa)) {
                $dimensions['pa'] = (float) $this->pa;
                $dimensions['la_side'] = (float) $this->la_side;
                $dimensions['pb'] = (float) $this->pb;
                $dimensions['lb_side'] = (float) $this->lb_side;
            }
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
        $this->pa = '';
        $this->la_side = '';
        $this->pb = '';
        $this->lb_side = '';
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
