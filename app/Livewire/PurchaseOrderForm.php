<?php

namespace App\Livewire;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class PurchaseOrderForm extends Component
{
    use WithPagination;

    public const STATUS_STEPS = [
        'Menunggu Konfirmasi',
        'Sudah Dimuat',
        'Dalam Perjalanan',
        'Sudah Tiba di Pelabuhan',
        'Penerimaan Sebagian',
        'Selesai Seluruhnya',
    ];

    #[Rule('required|string|max:50')]
    public string $po_number = '';

    #[Rule('required|date')]
    public string $po_date = '';

    #[Rule('required|exists:suppliers,id')]
    public ?int $supplier_id = null;

    #[Rule('required|string')]
    public string $material_type = '';

    public string $specification = '';

    #[Rule('required|numeric|min:0.01|max:99999999')]
    public string|float $qty_ordered = '';

    public string $unit = 'm³';

    public string $vessel_name = '';

    public string $loading_jetty = '';

    public string $unloading_jetty = '';

    public ?string $est_departure_date = null;

    public ?string $est_arrival_date = null;

    #[Rule('required|string')]
    public string $status = 'Menunggu Konfirmasi';

    public string $search = '';

    public string $filterDateRange = 'all';

    public ?string $filterStartDate = null;

    public ?string $filterEndDate = null;

    // Quick Edit Status & Logistics Modal Properties
    public bool $show_edit_modal = false;

    public ?int $editing_po_id = null;

    public string $editing_po_number = '';

    public string $editing_current_status = '';

    public string $editing_status = '';

    public string $editing_vessel_name = '';

    public string $editing_loading_jetty = '';

    public string $editing_unloading_jetty = '';

    public ?string $editing_est_departure_date = null;

    public ?string $editing_est_arrival_date = null;

    public ?string $editing_actual_departure_date = null;

    public ?string $editing_actual_arrival_date = null;

    public function mount(DocumentNumberService $docService): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses modul Purchase Order.');
        }

        $this->po_date = Carbon::now()->format('Y-m-d');
        $this->po_number = $docService->generatePoNumber($this->po_date);
    }

    public function updatedPoDate(mixed $value, DocumentNumberService $docService): void
    {
        $this->po_number = $docService->generatePoNumber((string) $value);
    }

    public function updatedMaterialType(mixed $value): void
    {
        $this->unit = match ($value) {
            'Semen', 'Semen Singa Merah', 'Semen Conch' => 'Ton',
            'Dexlite' => 'Liter',
            'Additive' => 'Kg',
            default => 'm³',
        };
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'qty_ordered') {
            $this->qty_ordered = $this->normalizeDecimal($this->qty_ordered);
        }
    }

    protected function normalizeDecimal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $clean = trim($value);
        if ($clean === '') {
            return $clean;
        }

        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '.', $clean);
        }

        return $clean;
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

    /**
     * Map operational PO material type into consolidated master material account.
     */
    public function resolveBackendMaterial(string $materialType): Material
    {
        $normalized = strtoupper(trim($materialType));

        $targetName = match (true) {
            str_contains($normalized, 'LARIANG') || str_contains($normalized, 'TIBO') || str_contains($normalized, 'PALU') => 'Pasir Palu',
            str_contains($normalized, 'MAHAKAM') || str_contains($normalized, 'LOKAL') => 'Pasir Lokal',
            str_contains($normalized, 'SEMEN') => 'Semen',
            str_contains($normalized, '1/2') => 'Batu Kecil 1/2',
            str_contains($normalized, '2/3') => 'Batu Besar 2/3',
            str_contains($normalized, 'DEXLITE') || str_contains($normalized, 'SOLAR') => 'Dexlite',
            str_contains($normalized, 'ADDITIVE') => 'Additive',
            default => 'Pasir Palu',
        };

        return Material::firstOrCreate(
            ['name' => $targetName],
            ['unit' => $this->unit, 'density_kg_m3' => 1400.00]
        );
    }

    public function save(DocumentNumberService $docService): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (! in_array(Auth::user()->role, ['admin', 'kasir_logistik', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk menginput Purchase Order.');
        }

        $this->qty_ordered = $this->normalizeDecimal($this->qty_ordered);

        $this->validate();

        $backendMaterial = $this->resolveBackendMaterial($this->material_type);

        PurchaseOrder::create([
            'po_number' => strtoupper(trim($this->po_number)),
            'po_date' => $this->po_date,
            'supplier_id' => $this->supplier_id,
            'vessel_name' => $this->vessel_name ? strtoupper(trim($this->vessel_name)) : null,
            'loading_jetty' => $this->loading_jetty ? trim($this->loading_jetty) : null,
            'unloading_jetty' => $this->unloading_jetty ? trim($this->unloading_jetty) : null,
            'est_departure_date' => $this->est_departure_date ?: null,
            'est_arrival_date' => $this->est_arrival_date ?: null,
            'status' => $this->status,
            'material_id' => $backendMaterial->id,
            'material_type_input' => $this->material_type,
            'specification' => $this->specification ? trim($this->specification) : null,
            'qty_ordered' => (float) $this->qty_ordered,
            'unit' => $this->unit,
            'unit_price' => 0.00,
            'subtotal' => 0.00,
            'ppn_amount' => 0.00,
            'grand_total' => 0.00,
        ]);

        $savedNumber = $this->po_number;

        // Reset form
        $this->material_type = '';
        $this->specification = '';
        $this->qty_ordered = '';
        $this->unit = 'm³';
        $this->vessel_name = '';
        $this->loading_jetty = '';
        $this->unloading_jetty = '';
        $this->est_departure_date = null;
        $this->est_arrival_date = null;
        $this->status = 'Menunggu Konfirmasi';
        $this->supplier_id = null;
        $this->po_number = $docService->generatePoNumber($this->po_date);

        session()->flash('success', "Purchase Order ({$savedNumber}) berhasil disimpan.");
        $this->dispatch('form-saved');
    }

    public function openEditModal(int $poId): void
    {
        $this->resetErrorBag();
        $po = PurchaseOrder::findOrFail($poId);

        $this->editing_po_id = $po->id;
        $this->editing_po_number = $po->po_number;
        $this->editing_current_status = $po->status;
        $this->editing_status = $po->status;
        $this->editing_vessel_name = $po->vessel_name ?? '';
        $this->editing_loading_jetty = $po->loading_jetty ?? '';
        $this->editing_unloading_jetty = $po->unloading_jetty ?? '';
        $this->editing_est_departure_date = $po->est_departure_date?->format('Y-m-d');
        $this->editing_est_arrival_date = $po->est_arrival_date?->format('Y-m-d');
        $this->editing_actual_departure_date = $po->actual_departure_date?->format('Y-m-d');
        $this->editing_actual_arrival_date = $po->actual_arrival_date?->format('Y-m-d');

        $currentIndex = array_search($this->editing_current_status, self::STATUS_STEPS);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        // Jika status saat ini sudah "Dalam Perjalanan" dan belum ada actual departure date
        if ($currentIndex >= 2 && empty($this->editing_actual_departure_date)) {
            $this->editing_actual_departure_date = $this->editing_est_departure_date ?: Carbon::now()->toDateString();
        }

        // Jika status saat ini sudah "Sudah Tiba di Pelabuhan" (atau di atasnya) dan belum ada actual arrival date
        if ($currentIndex >= 3 && empty($this->editing_actual_arrival_date)) {
            $this->editing_actual_arrival_date = $this->editing_est_arrival_date ?: Carbon::now()->toDateString();
        }

        $this->show_edit_modal = true;
    }

    public function closeEditModal(): void
    {
        $this->show_edit_modal = false;
        $this->editing_po_id = null;
        $this->resetErrorBag();
    }

    public function updatedEditingStatus(mixed $value): void
    {
        $currentIndex = array_search($this->editing_current_status, self::STATUS_STEPS);
        $newIndex = array_search($value, self::STATUS_STEPS);

        if ($newIndex === false || $currentIndex === false) {
            return;
        }

        if ($newIndex < $currentIndex) {
            $this->addError('editing_status', 'Status tidak boleh diubah mundur ke tahap sebelumnya.');

            return;
        }

        $this->resetErrorBag('editing_status');

        // Saat status menjadi "Dalam Perjalanan" (indeks >= 2)
        if ($newIndex >= 2 && empty($this->editing_actual_departure_date)) {
            $this->editing_actual_departure_date = $this->editing_est_departure_date ?: Carbon::now()->toDateString();
        }

        // Saat status menjadi "Sudah Tiba di Pelabuhan" (indeks >= 3)
        if ($newIndex >= 3) {
            if (empty($this->editing_actual_departure_date)) {
                $this->editing_actual_departure_date = $this->editing_est_departure_date ?: Carbon::now()->toDateString();
            }
            if (empty($this->editing_actual_arrival_date)) {
                $this->editing_actual_arrival_date = Carbon::now()->toDateString();
            }
        }
    }

    public function getAllowedStatusOptions(): array
    {
        $currentIndex = array_search($this->editing_current_status, self::STATUS_STEPS);
        if ($currentIndex === false) {
            return self::STATUS_STEPS;
        }

        return array_values(array_slice(self::STATUS_STEPS, $currentIndex));
    }

    public function updateStatusAndLogistics(): void
    {
        if (! Auth::check() || ! in_array(Auth::user()->role, ['admin', 'kasir_logistik', 'demo'])) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk memperbarui status Purchase Order.');
        }

        $po = PurchaseOrder::findOrFail($this->editing_po_id);

        $currentIndex = array_search($po->status, self::STATUS_STEPS);
        $newIndex = array_search($this->editing_status, self::STATUS_STEPS);

        if ($newIndex === false || $currentIndex === false || $newIndex < $currentIndex) {
            $this->addError('editing_status', 'Status tidak boleh diubah mundur ke tahap sebelumnya.');

            return;
        }

        $rules = [
            'editing_status' => 'required|in:'.implode(',', self::STATUS_STEPS),
            'editing_vessel_name' => 'nullable|string|max:100',
            'editing_loading_jetty' => 'nullable|string|max:100',
            'editing_unloading_jetty' => 'nullable|string|max:100',
            'editing_est_departure_date' => 'nullable|date',
            'editing_est_arrival_date' => 'nullable|date',
            'editing_actual_departure_date' => 'nullable|date',
            'editing_actual_arrival_date' => 'nullable|date',
        ];

        // Jika status "Dalam Perjalanan" (indeks >= 2)
        if ($newIndex >= 2 && empty($this->editing_actual_departure_date)) {
            $this->editing_actual_departure_date = $this->editing_est_departure_date ?: Carbon::now()->toDateString();
        }

        // Jika status "Sudah Tiba di Pelabuhan" atau di atasnya:
        // Tanggal Berangkat otomatis terkunci permanen (readonly / disabled) dan tidak dapat diubah lagi!
        if ($newIndex >= 3) {
            if ($po->actual_departure_date) {
                // Pertahankan tanggal berangkat aktual sebelumnya agar tidak bisa dimanipulasi
                $this->editing_actual_departure_date = $po->actual_departure_date->format('Y-m-d');
            } elseif (empty($this->editing_actual_departure_date)) {
                $this->editing_actual_departure_date = $this->editing_est_departure_date ?: Carbon::now()->toDateString();
            }

            if (empty($this->editing_actual_arrival_date)) {
                $this->editing_actual_arrival_date = Carbon::now()->toDateString();
            }
        }

        $this->validate($rules);

        $po->update([
            'status' => $this->editing_status,
            'vessel_name' => $this->editing_vessel_name ? strtoupper(trim($this->editing_vessel_name)) : null,
            'loading_jetty' => $this->editing_loading_jetty ? trim($this->editing_loading_jetty) : null,
            'unloading_jetty' => $this->editing_unloading_jetty ? trim($this->editing_unloading_jetty) : null,
            'est_departure_date' => $this->editing_est_departure_date ?: null,
            'est_arrival_date' => $this->editing_est_arrival_date ?: null,
            'actual_departure_date' => $this->editing_actual_departure_date ?: null,
            'actual_arrival_date' => $this->editing_actual_arrival_date ?: null,
        ]);

        $poNumber = $po->po_number;
        $savedStatus = $this->editing_status;
        $this->show_edit_modal = false;
        $this->editing_po_id = null;

        session()->flash('success', "Status dan logistik PO ({$poNumber}) berhasil diperbarui menjadi '{$savedStatus}'.");
    }

    public function render(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name'])->unique('name');

        $materialOptions = [
            'Semen Singa Merah' => 'Semen Singa Merah',
            'Semen Conch' => 'Semen Conch',
            'Pasir Palu' => 'Pasir Palu',
            'Pasir Lariang' => 'Pasir Lariang',
            'Pasir Tibo' => 'Pasir Tibo',
            'Pasir Lokal' => 'Pasir Lokal',
            'Pasir Mahakam' => 'Pasir Mahakam',
            'Batu 1/2' => 'Batu 1/2',
            'Batu 2/3' => 'Batu 2/3',
            'Dexlite' => 'Dexlite',
            'Additive' => 'Additive',
        ];

        $statusOptions = [
            'Menunggu Konfirmasi',
            'Sudah Dimuat',
            'Dalam Perjalanan',
            'Sudah Tiba di Pelabuhan',
            'Penerimaan Sebagian',
            'Selesai Seluruhnya',
        ];

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'material'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhere('vessel_name', 'like', "%{$search}%")
                        ->orWhere('material_type_input', 'like', "%{$search}%")
                        ->orWhere('loading_jetty', 'like', "%{$search}%")
                        ->orWhere('unloading_jetty', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('material', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->filterStartDate, fn ($q) => $q->whereDate('po_date', '>=', $this->filterStartDate))
            ->when($this->filterEndDate, fn ($q) => $q->whereDate('po_date', '<=', $this->filterEndDate))
            ->latest('po_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.purchase-order-form', [
            'suppliers' => $suppliers,
            'materialOptions' => $materialOptions,
            'statusOptions' => $statusOptions,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }
}
