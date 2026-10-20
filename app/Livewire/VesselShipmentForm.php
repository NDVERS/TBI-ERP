<?php

namespace App\Livewire;

use App\Models\PurchaseOrder;
use App\Models\VesselShipment;
use App\Services\ProcurementCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class VesselShipmentForm extends Component
{
    use WithPagination;

    #[Rule('nullable|date')]
    public ?string $departure_date = '';

    #[Rule('nullable|date')]
    public ?string $arrival_date = '';

    #[Rule('nullable|exists:purchase_orders,id')]
    public ?int $purchase_order_id = null;

    #[Rule('required|numeric|min:0.01|max:999999')]
    public string|float $load_volume = '';

    #[Rule('required|numeric|min:0.01|max:999999')]
    public string|float $unload_volume = '';

    #[Rule('nullable|string|max:255')]
    public string $lab_result = 'Lolos Uji Standar (Kadar Lumpur < 3%)';

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Pengiriman Kapal.');
        }

        $this->departure_date = Carbon::now()->subDays(2)->format('Y-m-d');
        $this->arrival_date = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getShrinkagePreviewProperty(): ?array
    {
        if ((float) $this->load_volume > 0 && (float) $this->unload_volume > 0) {
            $calculator = new ProcurementCalculatorService;

            return $calculator->calculateVesselShrinkage($this->load_volume, $this->unload_volume);
        }

        return null;
    }

    public function save(ProcurementCalculatorService $calculator): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'petugas_gudang'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Pengiriman Kapal.');
        }

        $this->validate();

        // Calculate shrinkage metrics via ProcurementCalculatorService
        $shrinkage = $calculator->calculateVesselShrinkage($this->load_volume, $this->unload_volume);

        VesselShipment::create([
            'departure_date' => $this->departure_date ?: null,
            'arrival_date' => $this->arrival_date ?: null,
            'purchase_order_id' => $this->purchase_order_id,
            'load_volume' => (float) $this->load_volume,
            'unload_volume' => (float) $this->unload_volume,
            'volume_diff' => $shrinkage['volume_diff'],
            'shrinkage_percent' => $shrinkage['shrinkage_percent'],
            'lab_result' => trim($this->lab_result),
        ]);

        // Reset form
        $this->load_volume = '';
        $this->unload_volume = '';
        $this->purchase_order_id = null;

        if ($shrinkage['shrinkage_percent'] > 2.0) {
            session()->flash('warning', "PERINGATAN: Pengiriman kapal tersimpan dengan tingkat susut TINGGI ({$shrinkage['shrinkage_percent']}%), melebihi batas toleransi 2.0%.");
        } else {
            session()->flash('success', "Pengiriman kapal berhasil dicatat. Selisih susut: {$shrinkage['volume_diff']} m³ ({$shrinkage['shrinkage_percent']}%).");
        }
    }

    public function render(): View
    {
        $purchaseOrders = PurchaseOrder::latest('po_date')->take(20)->get(['id', 'po_number']);

        $shipments = VesselShipment::query()
            ->with(['purchaseOrder'])
            ->when($this->search, function ($query, $search) {
                $query->where('lab_result', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                        $pq->where('po_number', 'like', "%{$search}%");
                    });
            })
            ->latest('arrival_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.vessel-shipment-form', [
            'purchaseOrders' => $purchaseOrders,
            'shipments' => $shipments,
        ]);
    }
}
