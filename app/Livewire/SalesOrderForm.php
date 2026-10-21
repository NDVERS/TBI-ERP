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
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SalesOrderForm extends Component
{
    use WithPagination;

    public const ALLOWED_MUTU_CODES = [
        // Seri K (K-Series)
        'K100 / C',
        'K100 / R',
        'K125 / C',
        'K125 / R',
        'K150 / C',
        'K150 / R',
        'K175 / C',
        'K175 / R',
        'K200 / C',
        'K200 / R',
        'K225 / C',
        'K225 / R',
        'K250 / C',
        'K250 / R',
        'K275 / C',
        'K275 / R',
        'K300 / C',
        'K300 / R',
        'K325 / C',
        'K325 / R',
        'K350 / C',
        'K350 / R',
        'K375 / C',
        'K375 / R',
        'K400 / C',
        'K400 / R',
        'K425 / C',
        'K425 / R',
        'K450 / C',
        'K450 / R',
        'K475 / C',
        'K475 / R',
        'K500 / C',
        'K500 / R',
        // Seri FC & FS (Megapascal & Flexural)
        'FC10',
        'FC15',
        'FC20',
        'FC25',
        'FC30',
        'FS45',
    ];

    public string $order_date = '';

    public ?int $customer_id = null;

    public string $location = '';

    public ?int $job_mix_id = null;

    public string|float $volume_m3 = '';

    public string|float $distance_km = '';

    public string $search = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'order_date' => ['required', 'date'],
            'customer_id' => ['required', 'exists:customers,id'],
            'location' => ['required', 'string', 'max:255'],
            'job_mix_id' => [
                'required',
                'exists:job_mixes,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $jobMix = JobMix::find($value);
                    if (! $jobMix || ! in_array($jobMix->mutu_code, self::ALLOWED_MUTU_CODES, true)) {
                        $fail('Mutu beton yang dipilih tidak valid atau tidak termasuk dalam standar operasional TBI Plant.');
                    }
                },
            ],
            'volume_m3' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'distance_km' => ['required', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'job_mix_id.required' => 'Pilih mutu beton (job mix).',
            'job_mix_id.exists' => 'Mutu beton yang dipilih tidak ditemukan di sistem.',
        ];
    }

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Sales Order.');
        }

        $this->order_date = now()->format('Y-m-d');
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['volume_m3', 'distance_km']) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = trim(str_replace(',', '.', $this->{$propertyName}));
        }
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

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Sales Order.');
        }

        if (is_string($this->volume_m3)) {
            $this->volume_m3 = trim(str_replace(',', '.', $this->volume_m3));
        }
        if (is_string($this->distance_km)) {
            $this->distance_km = trim(str_replace(',', '.', $this->distance_km));
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
        $this->dispatch('form-saved');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $customers = Customer::orderBy('name')->get(['id', 'name', 'address']);

        $allowedMap = array_flip(self::ALLOWED_MUTU_CODES);
        $jobMixes = JobMix::whereIn('mutu_code', self::ALLOWED_MUTU_CODES)
            ->get(['id', 'mutu_code'])
            ->sortBy(fn ($jm) => $allowedMap[$jm->mutu_code] ?? 999)
            ->values();

        $kSeriesJobMixes = $jobMixes->filter(fn ($jm) => str_starts_with($jm->mutu_code, 'K'))->values();
        $fcFsJobMixes = $jobMixes->filter(fn ($jm) => ! str_starts_with($jm->mutu_code, 'K'))->values();

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
            'kSeriesJobMixes' => $kSeriesJobMixes,
            'fcFsJobMixes' => $fcFsJobMixes,
            'salesOrders' => $salesOrders,
        ]);
    }
}
