<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\JobMix;
use App\Models\SalesOrder;
use App\Services\DocumentNumberService;
use App\Services\JobMixCalculatorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SalesOrderForm extends Component
{
    use WithPagination;

    #[Rule('required|date')]
    public string $order_date = '';

    #[Rule('required|exists:customers,id')]
    public ?int $customer_id = null;

    #[Rule('required|string|max:255')]
    public string $location = '';

    #[Rule('required|exists:job_mixes,id')]
    public ?int $job_mix_id = null;

    #[Rule('required|numeric|min:0.01|max:999999')]
    public string|float $volume_m3 = '';

    #[Rule('required|numeric|min:0|max:9999')]
    public string|float $distance_km = '';

    public string $search = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Sales Order.');
        }

        $this->order_date = now()->format('Y-m-d');
    }

    public function save(
        DocumentNumberService $documentNumberService,
        JobMixCalculatorService $jobMixCalculator
    ): void {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Sales Order.');
        }

        $this->validate();

        $jobMix = JobMix::findOrFail($this->job_mix_id);

        // 1. Generate unique sequential SO number for the month
        $soNumber = $documentNumberService->generateSoNumber($this->order_date);

        // 2. Server-side secret material and fleet fuel estimation
        $estimates = $jobMixCalculator->calculateAllSoEstimates(
            $jobMix,
            (float) $this->volume_m3,
            (float) $this->distance_km
        );

        // 3. Save Sales Order with encapsulated estimates
        SalesOrder::create([
            'so_number' => $soNumber,
            'order_date' => $this->order_date,
            'customer_id' => $this->customer_id,
            'location' => trim($this->location),
            'job_mix_id' => $jobMix->id,
            'volume_m3' => (float) $this->volume_m3,
            'distance_km' => (float) $this->distance_km,
            'est_semen_kg' => $estimates['est_semen_kg'],
            'est_pasir_palu_kg' => $estimates['est_pasir_palu_kg'],
            'est_pasir_lokal_kg' => $estimates['est_pasir_lokal_kg'],
            'est_split_1_2_kg' => $estimates['est_split_1_2_kg'],
            'est_split_2_3_kg' => $estimates['est_split_2_3_kg'],
            'est_additive_liter' => $estimates['est_additive_liter'],
            'est_solar_liter' => $estimates['est_solar_liter'],
        ]);

        // Reset form inputs except order_date
        $this->customer_id = null;
        $this->location = '';
        $this->job_mix_id = null;
        $this->volume_m3 = '';
        $this->distance_km = '';

        session()->flash('success', "Sales Order {$soNumber} berhasil dibuat dan dihitung di server!");
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $jobMixes = JobMix::orderBy('mutu_code')->get(['id', 'mutu_code', 'description']);

        $salesOrders = SalesOrder::query()
            ->with(['customer', 'jobMix'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('so_number', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('jobMix', function ($jq) use ($search) {
                            $jq->where('mutu_code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('order_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.sales-order-form', [
            'customers' => $customers,
            'jobMixes' => $jobMixes,
            'salesOrders' => $salesOrders,
        ]);
    }
}
