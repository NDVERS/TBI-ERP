<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\ProductionDocket;
use App\Models\SalesOrder;
use App\Services\DocumentNumberService;
use App\Services\StockReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ProductionDocketForm extends Component
{
    use WithPagination;

    #[Rule('required|string|max:50')]
    public string $docket_no = '';

    #[Rule('required|date')]
    public string $production_date = '';

    #[Rule('required')]
    public string $time_load = '';

    #[Rule('nullable|exists:sales_orders,id')]
    public ?int $sales_order_id = null;

    #[Rule('nullable|exists:customers,id')]
    public ?int $customer_id = null;

    #[Rule('required|string|max:255')]
    public string $customer_name = '';

    #[Rule('nullable|string')]
    public string $address = '';

    #[Rule('required|string|max:50')]
    public string $mutu_code = '';

    #[Rule('required|string|max:50')]
    public string $truck_no = '';

    #[Rule('required|numeric|min:0.1|max:100')]
    public string|float $volume_m3 = '';

    // Komposisi Batching Aktual
    #[Rule('required|numeric|min:0')]
    public string|float $silo1_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $pasir_palu_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $pasir_lokal_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $split_1_2_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $split_2_3_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $water_act = '';

    #[Rule('required|numeric|min:0')]
    public string|float $admixture_act = '';

    public string $search = '';

    public const ALLOWED_MUTU_CODES = [
        // Seri K (K-Series)
        'K100 / C', 'K100 / R',
        'K125 / C', 'K125 / R',
        'K150 / C', 'K150 / R',
        'K175 / C', 'K175 / R',
        'K200 / C', 'K200 / R',
        'K225 / C', 'K225 / R',
        'K250 / C', 'K250 / R',
        'K275 / C', 'K275 / R',
        'K300 / C', 'K300 / R',
        'K325 / C', 'K325 / R',
        'K350 / C', 'K350 / R',
        'K375 / C', 'K375 / R',
        'K400 / C', 'K400 / R',
        'K425 / C', 'K425 / R',
        'K450 / C', 'K450 / R',
        'K475 / C', 'K475 / R',
        'K500 / C', 'K500 / R',
        // Seri FC & FS (Megapascal & Flexural)
        'FC10', 'FC15', 'FC20', 'FC25', 'FC30', 'FS45',
    ];

    /**
     * @var array<int, string>
     */
    public array $mutuList = self::ALLOWED_MUTU_CODES;

    public function mount(DocumentNumberService $docService): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'operator_produksi', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Produksi Harian.');
        }

        $nowWita = Carbon::now('Asia/Makassar');
        $this->production_date = $nowWita->format('Y-m-d');
        $this->time_load = $nowWita->format('H:i');
        $this->docket_no = $docService->generateDocketNumber();
    }

    public function updatedSalesOrderId(mixed $value): void
    {
        $val = (int) $value;
        if ($val > 0) {
            $so = SalesOrder::with(['customer', 'jobMix'])->find($val);
            if ($so) {
                $this->sales_order_id = $so->id;
                $this->customer_id = $so->customer_id;
                $this->customer_name = strtoupper($so->customer->name ?? '');
                $this->address = strtoupper($so->location ?: ($so->customer->address ?? ''));
                if ($so->jobMix && ! empty($so->jobMix->mutu_code)) {
                    $this->mutu_code = strtoupper($so->jobMix->mutu_code);
                }

                return;
            }
        }

        $this->sales_order_id = null;
    }

    public function getSelectedSalesOrderProperty(): ?SalesOrder
    {
        if ($this->sales_order_id) {
            return SalesOrder::with(['customer', 'jobMix'])->find($this->sales_order_id);
        }

        return null;
    }

    public function updatedCustomerId(mixed $value): void
    {
        $val = (int) $value;
        if ($val > 0) {
            $customer = Customer::find($val);
            if ($customer) {
                $this->customer_id = $customer->id;
                $this->customer_name = strtoupper($customer->name);
                $this->address = ! empty($customer->address) ? strtoupper($customer->address) : '';

                return;
            }
        }

        $this->customer_id = null;
        $this->customer_name = '';
        $this->address = '';
    }

    public function updatedCustomerName(string $value): void
    {
        $cleaned = trim($value);
        if (! empty($cleaned)) {
            $customer = Customer::where('name', $cleaned)
                ->orWhereRaw('UPPER(name) = ?', [strtoupper($cleaned)])
                ->first();

            if ($customer) {
                $this->customer_id = $customer->id;
                $this->customer_name = strtoupper($customer->name);
                $this->address = ! empty($customer->address) ? strtoupper($customer->address) : '';
            } else {
                $this->customer_name = strtoupper($cleaned);
            }
        } else {
            $this->customer_id = null;
            $this->customer_name = '';
            $this->address = '';
        }
    }

    public function updatedMutuCode(string $value): void
    {
        $this->mutu_code = strtoupper($value);
    }

    public function formatTruckNo(?string $val): string
    {
        if (empty($val)) {
            return '';
        }

        $cleaned = trim($val);
        $num = preg_replace('/^TM-?/i', '', $cleaned);
        $num = trim($num);

        return 'TM-'.strtoupper($num);
    }

    public function updated(string $propertyName): void
    {
        $numericFields = [
            'volume_m3',
            'silo1_act',
            'pasir_palu_act',
            'pasir_lokal_act',
            'split_1_2_act',
            'split_2_3_act',
            'water_act',
            'admixture_act',
        ];

        if (in_array($propertyName, $numericFields) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = trim(str_replace(',', '.', $this->{$propertyName}));
        }

        if (in_array($propertyName, ['address', 'docket_no']) && is_string($this->{$propertyName})) {
            $this->{$propertyName} = strtoupper($this->{$propertyName});
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function normalizeNumericInputs(): void
    {
        $numericFields = [
            'volume_m3',
            'silo1_act',
            'pasir_palu_act',
            'pasir_lokal_act',
            'split_1_2_act',
            'split_2_3_act',
            'water_act',
            'admixture_act',
        ];

        foreach ($numericFields as $field) {
            if (is_string($this->{$field})) {
                $this->{$field} = trim(str_replace(',', '.', $this->{$field}));
            }
        }
    }

    public function save(DocumentNumberService $docService): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'operator_produksi', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Produksi Harian.');
        }

        $this->normalizeNumericInputs();

        // Sinkronisasi dwiarah customer sebelum validasi
        if (! empty($this->customer_id)) {
            $cust = Customer::find($this->customer_id);
            if ($cust) {
                $this->customer_name = strtoupper($cust->name);
                if (empty($this->address)) {
                    $this->address = ! empty($cust->address) ? strtoupper($cust->address) : '';
                }
            }
        } elseif (! empty($this->customer_name)) {
            $cust = Customer::where('name', $this->customer_name)
                ->orWhereRaw('UPPER(name) = ?', [strtoupper(trim($this->customer_name))])
                ->first();
            if ($cust) {
                $this->customer_id = $cust->id;
                $this->customer_name = strtoupper($cust->name);
                if (empty($this->address)) {
                    $this->address = ! empty($cust->address) ? strtoupper($cust->address) : '';
                }
            }
        }

        $this->validate();

        // Format Uppercase & TM Prefix
        $upperDocket = strtoupper(trim($this->docket_no));
        $formattedTruckNo = $this->formatTruckNo($this->truck_no);
        $upperCustomer = strtoupper(trim($this->customer_name));
        $upperAddress = ! empty($this->address) ? strtoupper(trim($this->address)) : null;
        $upperMutu = strtoupper(trim($this->mutu_code));

        // Hitung volume kumulatif harian
        $prevCumulative = (float) ProductionDocket::where('production_date', $this->production_date)->sum('volume_m3');
        $currentVolume = (float) $this->volume_m3;
        $dailyCumulative = round($prevCumulative + $currentVolume, 2);

        // Hitung selisih waktu loading dengan docket sebelumnya pada hari yang sama
        $lastDocket = ProductionDocket::where('production_date', $this->production_date)
            ->orderByDesc('id')
            ->first();

        $loadTimeDiff = null;
        if ($lastDocket && $lastDocket->time_load) {
            try {
                $prevTime = Carbon::createFromFormat('H:i:s', strlen($lastDocket->time_load) === 5 ? $lastDocket->time_load.':00' : $lastDocket->time_load);
                $currTime = Carbon::createFromFormat('H:i:s', strlen($this->time_load) === 5 ? $this->time_load.':00' : $this->time_load);
                $diffMinutes = $currTime->diffInMinutes($prevTime);
                $loadTimeDiff = "{$diffMinutes} mnt";
            } catch (\Exception) {
                $loadTimeDiff = null;
            }
        }

        $docket = ProductionDocket::create([
            'docket_no' => $upperDocket,
            'production_date' => $this->production_date,
            'sales_order_id' => $this->sales_order_id,
            'truck_no' => $formattedTruckNo,
            'customer_name' => $upperCustomer,
            'address' => $upperAddress,
            'mutu_code' => $upperMutu,
            'time_load' => $this->time_load,
            'volume_m3' => $currentVolume,
            'daily_cumulative_m3' => $dailyCumulative,
            'load_time_diff' => $loadTimeDiff,
            'silo1_tar' => 0,
            'silo1_act' => (float) $this->silo1_act,
            'silo2_tar' => 0,
            'silo2_act' => 0,
            'pasir_palu_tar' => 0,
            'pasir_palu_act' => (float) $this->pasir_palu_act,
            'pasir_lokal_tar' => 0,
            'pasir_lokal_act' => (float) $this->pasir_lokal_act,
            'split_1_2_tar' => 0,
            'split_1_2_act' => (float) $this->split_1_2_act,
            'split_2_3_tar' => 0,
            'split_2_3_act' => (float) $this->split_2_3_act,
            'water_tar' => 0,
            'water_act' => (float) $this->water_act,
            'admixture_tar' => 0,
            'admixture_act' => (float) $this->admixture_act,
            'created_by' => Auth::id(),
        ]);

        // Akumulasi volume terkirim dan pemotongan kuota Sales Order
        if ($this->sales_order_id) {
            $totalDelivered = (float) ProductionDocket::where('sales_order_id', $this->sales_order_id)->sum('volume_m3');
            $so = SalesOrder::find($this->sales_order_id);
            if ($so) {
                $so->delivered_volume_m3 = $totalDelivered;
                if ($totalDelivered >= (float) $so->volume_m3) {
                    $so->status = 'selesai';
                } elseif ($totalDelivered > 0) {
                    $so->status = 'parsial';
                }
                $so->save();
            }
        }

        ActivityLog::log(
            'CREATE_PRODUCTION_DOCKET',
            "Input Surat Jalan / Docket Produksi {$docket->docket_no} - Mutu: {$docket->mutu_code}, Vol: {$docket->volume_m3} m³ untuk Customer: {$docket->customer_name}".($this->sales_order_id ? " (SO Terhubung: ID {$this->sales_order_id})" : ''),
            $docket
        );

        $successMsg = "Surat Jalan {$docket->docket_no} berhasil disimpan dan pemakaian material telah otomatis mengurangi stok teoritis.";
        if ($this->sales_order_id) {
            $successMsg .= ' Kuota volume Sales Order berhasil diperbarui secara otomatis.';
        }
        session()->flash('success', $successMsg);

        // Reset form & generate next docket
        $this->docket_no = $docService->generateDocketNumber();
        $this->sales_order_id = null;
        $this->customer_id = null;
        $this->customer_name = '';
        $this->address = '';
        $this->truck_no = '';
        $this->volume_m3 = '';
        $this->silo1_act = '';
        $this->pasir_palu_act = '';
        $this->pasir_lokal_act = '';
        $this->split_1_2_act = '';
        $this->split_2_3_act = '';
        $this->water_act = '';
        $this->admixture_act = '';

        // Update jam ke waktu terkini
        $this->time_load = Carbon::now('Asia/Makassar')->format('H:i');
    }

    public function render(StockReconciliationService $reconService): View
    {
        $customers = Customer::orderBy('name')->get();
        $stockSummaries = $reconService->reconcileAllMaterials();
        $activeSalesOrders = SalesOrder::with(['customer', 'jobMix'])
            ->whereIn('status', ['aktif', 'parsial'])
            ->latest('order_date')
            ->latest('id')
            ->get();

        $dockets = ProductionDocket::query()
            ->with(['salesOrder'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('docket_no', 'like', "%{$this->search}%")
                        ->orWhere('customer_name', 'like', "%{$this->search}%")
                        ->orWhere('truck_no', 'like', "%{$this->search}%")
                        ->orWhere('mutu_code', 'like', "%{$this->search}%")
                        ->orWhereHas('salesOrder', function ($sq) {
                            $sq->where('so_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(10);

        $kSeriesMutuList = array_values(array_filter($this->mutuList, fn ($m) => str_starts_with($m, 'K')));
        $fcFsMutuList = array_values(array_filter($this->mutuList, fn ($m) => ! str_starts_with($m, 'K')));

        return view('livewire.production-docket-form', [
            'customers' => $customers,
            'dockets' => $dockets,
            'stockSummaries' => $stockSummaries,
            'activeSalesOrders' => $activeSalesOrders,
            'kSeriesMutuList' => $kSeriesMutuList,
            'fcFsMutuList' => $fcFsMutuList,
        ]);
    }
}
