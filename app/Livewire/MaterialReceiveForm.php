<?php

namespace App\Livewire;

use App\Models\Material;
use App\Models\MaterialReceive;
use App\Models\Supplier;
use App\Services\ProcurementCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class MaterialReceiveForm extends Component
{
    use WithPagination;

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

    #[Rule('required')]
    public string $time_in = '';

    #[Rule('required|numeric|min:0.01|max:99999')]
    public string|float $volume_m3 = '';

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Penerimaan Material.');
        }

        $this->receive_date = Carbon::now()->format('Y-m-d');
        $this->time_in = Carbon::now()->format('H:i');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function save(ProcurementCalculatorService $calculator): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Penerimaan Material.');
        }

        $this->validate();

        $material = Material::findOrFail($this->material_id);
        $density = (float) $material->density_kg_m3;

        // Auto-calculate total weight in Kg using server density
        $totalWeight = $calculator->calculateReceiveWeight($this->volume_m3, $density);

        MaterialReceive::create([
            'receive_date' => $this->receive_date,
            'supplier_id' => $this->supplier_id,
            'material_id' => $material->id,
            'vehicle_no' => strtoupper(trim($this->vehicle_no)),
            'ri_no' => strtoupper(trim($this->ri_no)),
            'time_in' => $this->time_in,
            'volume_m3' => (float) $this->volume_m3,
            'density_used' => $density,
            'total_weight_kg' => $totalWeight,
        ]);

        // Reset form inputs
        $this->vehicle_no = '';
        $this->ri_no = '';
        $this->volume_m3 = '';
        $this->time_in = Carbon::now()->format('H:i');

        session()->flash('success', "Penerimaan material (RI: {$this->ri_no}) berhasil dicatat ({$totalWeight} Kg).");
    }

    public function render(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $materials = Material::orderBy('name')->get(['id', 'name', 'unit']);

        $receives = MaterialReceive::query()
            ->with(['supplier', 'material'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ri_no', 'like', "%{$search}%")
                        ->orWhere('vehicle_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('material', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('receive_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.material-receive-form', [
            'suppliers' => $suppliers,
            'materials' => $materials,
            'receives' => $receives,
        ]);
    }
}
