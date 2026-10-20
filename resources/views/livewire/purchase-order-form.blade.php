<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    PENGADAAN & LOGISTIK
                </span>
                <span class="text-xs text-zinc-500 font-mono">Inbound Procurement</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Purchase Order (PO)
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Penerbitan surat pesanan pembelian bahan baku material, jadwal pengapalan tongkang, dan monitoring status logistik.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Logistics-Ready
            </x-ui.badge>
        </div>
    </div>

    {{-- Form Input Purchase Order --}}
    <x-ui.card
        title="Form Terbitkan Purchase Order Baru"
        description="Lengkapi informasi pemesanan material, pelabuhan jetty muat/bongkar, serta estimasi pelayaran kapal."
    >
        <form wire:submit.prevent="save" class="space-y-4">
            {{-- Row 1: Identitas PO & Supplier --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    wire:model="po_number"
                    label="Nomor PO"
                    name="po_number"
                    placeholder="PO-PCR/01/TBI/26/10/xxxx"
                    mono
                    required
                />

                <x-ui.input
                    wire:model.live="po_date"
                    label="Tanggal PO"
                    name="po_date"
                    type="date"
                    required
                />

                <x-ui.select
                    wire:model="supplier_id"
                    label="Nama Supplier / Vendor"
                    name="supplier_id"
                    placeholder="-- Pilih Supplier --"
                    required
                >
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            {{-- Row 2: Jenis Material, Spesifikasi & Kuantitas --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-ui.select
                        wire:model.live="material_type"
                        label="Jenis Material"
                        name="material_type"
                        placeholder="-- Pilih Jenis Material --"
                        required
                    >
                        @foreach ($materialOptions as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </x-ui.select>
                    @if (str_contains($material_type, 'Singa Merah') || str_contains($material_type, 'Conch'))
                        <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                            *Dialokasikan ke akun stok induk: <strong>Semen</strong> (Satuan: Ton).
                        </p>
                    @elseif (str_contains($material_type, 'Lariang') || str_contains($material_type, 'Tibo'))
                        <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                            *Dialokasikan ke akun stok induk: <strong>Pasir Palu</strong> (Satuan: m³).
                        </p>
                    @elseif (str_contains($material_type, 'Mahakam'))
                        <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                            *Dialokasikan ke akun stok induk: <strong>Pasir Lokal</strong> (Satuan: m³).
                        </p>
                    @endif
                </div>

                <x-ui.input
                    wire:model="specification"
                    label="Spesifikasi Material"
                    name="specification"
                    placeholder="Contoh: River Pasir Mix, M-Pasir, Batu Split Bersih"
                />

                <div>
                    <x-ui.input
                        wire:model.live.debounce.300ms="qty_ordered"
                        label="Jumlah Pesanan"
                        name="qty_ordered"
                        type="text"
                        placeholder="0.00"
                        :suffix="$unit"
                        mono
                        required
                    />
                    <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                        Satuan otomatis: <strong>{{ $unit }}</strong> (mendukung koma/titik).
                    </p>
                </div>
            </div>

            {{-- Row 3: Kolom Finansial Read-Only (Rp0) --}}
            <div class="p-3.5 rounded-lg border border-zinc-200 bg-zinc-50/70 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-600 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Rincian Nilai Finansial (Mode Read-Only)
                    </span>
                    <span class="text-[11px] font-mono text-zinc-400">Non-Editable Stage</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <x-ui.input
                        label="Harga Satuan"
                        value="Rp 0"
                        disabled
                        mono
                    />

                    <x-ui.input
                        label="Sub Total"
                        value="Rp 0"
                        disabled
                        mono
                    />

                    <x-ui.input
                        label="PPN (11%)"
                        value="Rp 0"
                        disabled
                        mono
                    />

                    <x-ui.input
                        label="Grand Total"
                        value="Rp 0"
                        disabled
                        mono
                    />
                </div>
                <p class="text-[11px] text-zinc-500 italic">
                    *Kolom finansial pada tahap ini dikunci ke Rp 0 sesuai ketentuan operasional logistik.
                </p>
            </div>

            {{-- Row 4: Pengapalan & Logistik --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    wire:model="vessel_name"
                    label="Nama Kapal / Tongkang"
                    name="vessel_name"
                    placeholder="Contoh: TB. MARCOPOLO 128 / BG. MARINE 300"
                    mono
                />

                <x-ui.input
                    wire:model="loading_jetty"
                    label="Jetty Muat (Pelabuhan Asal)"
                    name="loading_jetty"
                    placeholder="Contoh: Jetty Palu, Jetty Lariang"
                />

                <x-ui.input
                    wire:model="unloading_jetty"
                    label="Jetty Bongkar (Pelabuhan Tujuan)"
                    name="unloading_jetty"
                    placeholder="Contoh: BPM Samarinda, Rudina Jaya"
                />
            </div>

            {{-- Row 5: Jadwal Berlayar & Status PO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    wire:model="est_departure_date"
                    label="Est. Tanggal Berangkat"
                    name="est_departure_date"
                    type="date"
                />

                <x-ui.input
                    wire:model="est_arrival_date"
                    label="Est. Tanggal Tiba"
                    name="est_arrival_date"
                    type="date"
                />

                <x-ui.select
                    wire:model="status"
                    label="Status Purchase Order"
                    name="status"
                    required
                >
                    @foreach ($statusOptions as $st)
                        <option value="{{ $st }}">{{ $st }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            {{-- Form Submit Button --}}
            <div class="flex items-center justify-end pt-3 border-t border-zinc-100">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto"
                >
                    <span wire:loading.remove wire:target="save">Simpan Purchase Order</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan PO...
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tabel Riwayat Purchase Order --}}
    <x-ui.card
        title="Riwayat Data Purchase Order (PO)"
        description="Daftar surat pesanan pembelian bahan baku material beserta status logistik pengapalan."
    >
        {{-- Search Bar --}}
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                placeholder="Cari No. PO, supplier, material, kapal, jetty..."
                prefix="🔍"
                :clearable="true"
                :has-value="!empty($search)"
                loading-target="search"
            />
        </div>

        @php
            $headers = [
                'NO. PO',
                'TANGGAL PO',
                'NAMA SUPPLIER',
                'JENIS MATERIAL',
                'SPESIFIKASI',
                'JUMLAH PESANAN',
                'HARGA SATUAN',
                'SUB TOTAL',
                'PPN',
                'GRAND TOTAL',
                'NAMA KAPAL',
                'JETTY MUAT',
                'JETTY BONGKAR',
                'TGL. BERANGKAT',
                'TGL. TIBA',
                'STATUS',
                'AKSI',
            ];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($purchaseOrders as $po)
                @php
                    $statusVariant = match ($po->status) {
                        'Selesai Seluruhnya' => 'emerald',
                        'Sudah Tiba di Pelabuhan' => 'indigo',
                        'Dalam Perjalanan' => 'blue',
                        'Sudah Dimuat', 'Penerimaan Sebagian' => 'amber',
                        default => 'zinc',
                    };
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- 1. NO. PO --}}
                    <td class="px-4 py-3 first:pl-5 font-mono font-semibold text-zinc-900 whitespace-nowrap">
                        {{ $po->po_number }}
                    </td>

                    {{-- 2. TANGGAL PO --}}
                    <td class="px-4 py-3 font-mono text-zinc-700 whitespace-nowrap">
                        {{ $po->po_date ? $po->po_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- 3. NAMA SUPPLIER --}}
                    <td class="px-4 py-3 font-medium text-zinc-900 whitespace-nowrap">
                        {{ $po->supplier->name ?? '-' }}
                    </td>

                    {{-- 4. JENIS MATERIAL --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge variant="zinc">
                            {{ $po->material_type_input ?: ($po->material->name ?? '-') }}
                        </x-ui.badge>
                    </td>

                    {{-- 5. SPESIFIKASI MATERIAL --}}
                    <td class="px-4 py-3 text-xs text-zinc-600 whitespace-nowrap">
                        {{ $po->specification ?: '-' }}
                    </td>

                    {{-- 6. JUMLAH PESANAN --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-zinc-900 whitespace-nowrap">
                        {{ number_format((float) $po->qty_ordered, 2, ',', '.') }} {{ $po->unit }}
                    </td>

                    {{-- 7. HARGA SATUAN --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-500 whitespace-nowrap">
                        Rp {{ number_format((float) $po->unit_price, 0, ',', '.') }}
                    </td>

                    {{-- 8. SUB TOTAL --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-500 whitespace-nowrap">
                        Rp {{ number_format((float) $po->subtotal, 0, ',', '.') }}
                    </td>

                    {{-- 9. PPN --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-500 whitespace-nowrap">
                        Rp {{ number_format((float) $po->ppn_amount, 0, ',', '.') }}
                    </td>

                    {{-- 10. GRAND TOTAL --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-semibold text-zinc-700 whitespace-nowrap">
                        Rp {{ number_format((float) $po->grand_total, 0, ',', '.') }}
                    </td>

                    {{-- 11. NAMA KAPAL --}}
                    <td class="px-4 py-3 font-mono text-zinc-700 whitespace-nowrap">
                        {{ $po->vessel_name ?: '-' }}
                    </td>

                    {{-- 12. JETTY MUAT --}}
                    <td class="px-4 py-3 text-xs text-zinc-600 whitespace-nowrap">
                        {{ $po->loading_jetty ?: '-' }}
                    </td>

                    {{-- 13. JETTY BONGKAR --}}
                    <td class="px-4 py-3 text-xs text-zinc-600 whitespace-nowrap">
                        {{ $po->unloading_jetty ?: '-' }}
                    </td>

                    {{-- 14. TGL. BERANGKAT (Est vs Aktual) --}}
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                        @if ($po->actual_departure_date)
                            <div class="font-bold text-zinc-900">{{ $po->actual_departure_date->format('d/m/Y') }}</div>
                            <span class="inline-block text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1 py-0.2 rounded leading-tight mt-0.5">Aktual</span>
                        @elseif ($po->est_departure_date)
                            <div class="text-zinc-600">{{ $po->est_departure_date->format('d/m/Y') }}</div>
                            <span class="inline-block text-[10px] text-zinc-400">Est.</span>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
                    </td>

                    {{-- 15. TGL. TIBA (Est vs Aktual) --}}
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                        @if ($po->actual_arrival_date)
                            <div class="font-bold text-zinc-900">{{ $po->actual_arrival_date->format('d/m/Y') }}</div>
                            <span class="inline-block text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-1 py-0.2 rounded leading-tight mt-0.5">Aktual</span>
                        @elseif ($po->est_arrival_date)
                            <div class="text-zinc-600">{{ $po->est_arrival_date->format('d/m/Y') }}</div>
                            <span class="inline-block text-[10px] text-zinc-400">Est.</span>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
                    </td>

                    {{-- 16. STATUS --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge :variant="$statusVariant" size="sm">
                            {{ $po->status }}
                        </x-ui.badge>
                    </td>

                    {{-- 17. AKSI --}}
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap text-right">
                        <button
                            type="button"
                            wire:click="openEditModal({{ $po->id }})"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-700 shadow-2xs transition-colors"
                            title="Update Status & Logistik"
                        >
                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Update</span>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada catatan Purchase Order (PO).
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($purchaseOrders->hasPages())
            <div class="mt-4">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Modal Update Status & Logistik Pengapalan --}}
    @if ($show_edit_modal)
        <div
            @keydown.escape.window="$wire.closeEditModal()"
            wire:click.self="closeEditModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-zinc-950/50 flex items-center justify-center p-4 cursor-pointer"
        >
            <div class="bg-white rounded-lg border border-zinc-200 shadow-xl max-w-lg w-full p-6 space-y-4 cursor-default">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">
                            Update Status & Logistik Pengapalan
                        </h3>
                        <p class="text-xs font-mono text-zinc-500 mt-0.5">
                            {{ $editing_po_number }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeEditModal"
                        class="text-zinc-400 hover:text-zinc-600 p-1 rounded-md"
                    >
                        ✕
                    </button>
                </div>

                {{-- Status Saat Ini Info --}}
                <div class="flex items-center justify-between p-3 rounded-md bg-zinc-50 border border-zinc-200 text-xs">
                    <span class="text-zinc-600">Status Saat Ini:</span>
                    <span class="font-bold text-zinc-900">{{ $editing_current_status }}</span>
                </div>

                <form wire:submit.prevent="updateStatusAndLogistics" class="space-y-4">
                    {{-- 1. Status PO (Workflow Searah) --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Status Logistik Baru <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model.live="editing_status"
                            class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 bg-white px-3 py-2"
                        >
                            @foreach ($this->getAllowedStatusOptions() as $stOption)
                                <option value="{{ $stOption }}">{{ $stOption }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-zinc-500 mt-1">
                            Sesuai aturan alur kerja, status tidak dapat diubah mundur ke tahapan sebelumnya.
                        </p>
                        @error('editing_status')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 2. Nama Kapal / Tongkang --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Nama Kapal / Tongkang
                        </label>
                        <input
                            type="text"
                            wire:model="editing_vessel_name"
                            placeholder="Contoh: TB. KAIROS / BG. 300 FT"
                            class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 uppercase font-mono px-3 py-2"
                        />
                    </div>

                    {{-- 3. Jetty Muat & Bongkar --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">
                                Jetty Muat (Asal)
                            </label>
                            <input
                                type="text"
                                wire:model="editing_loading_jetty"
                                placeholder="Contoh: Jetty Palu"
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 px-3 py-2"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">
                                Jetty Bongkar (Tujuan)
                            </label>
                            <input
                                type="text"
                                wire:model="editing_unloading_jetty"
                                placeholder="Contoh: BPM Samarinda"
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 px-3 py-2"
                            />
                        </div>
                    </div>

                    @php
                        $newStatusIdx = array_search($editing_status, \App\Livewire\PurchaseOrderForm::STATUS_STEPS);
                        $isDepartureLocked = ($newStatusIdx !== false && $newStatusIdx >= 3);
                        $isInTransitOrBeyond = ($newStatusIdx !== false && $newStatusIdx >= 2);
                        $isArrivedOrBeyond = ($newStatusIdx !== false && $newStatusIdx >= 3);
                    @endphp

                    {{-- 4. Tanggal Berangkat (Est vs Aktual) --}}
                    <div class="p-3 rounded-md border {{ $isInTransitOrBeyond ? 'bg-emerald-50/50 border-emerald-200' : 'bg-zinc-50 border-zinc-200' }}">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-medium {{ $isInTransitOrBeyond ? 'text-emerald-900 font-bold' : 'text-zinc-700' }}">
                                {{ $isInTransitOrBeyond ? 'Tanggal Berangkat (Aktual)' : 'Est. Tanggal Berangkat' }}
                            </label>
                            @if ($isDepartureLocked)
                                <span class="text-[10px] font-semibold text-zinc-500 bg-zinc-200/80 px-1.5 py-0.5 rounded">Terkunci (Tongkang Tiba)</span>
                            @elseif ($isInTransitOrBeyond)
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">Realisasi Berangkat</span>
                            @endif
                        </div>
                        @if ($isInTransitOrBeyond)
                            <input
                                type="date"
                                wire:model="editing_actual_departure_date"
                                @if ($isDepartureLocked) disabled readonly @endif
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono px-3 py-2 {{ $isDepartureLocked ? 'bg-zinc-100 text-zinc-500 cursor-not-allowed' : 'bg-white' }}"
                            />
                        @else
                            <input
                                type="date"
                                wire:model="editing_est_departure_date"
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono bg-white px-3 py-2"
                            />
                        @endif
                    </div>

                    {{-- 5. Tanggal Tiba (Est vs Aktual) --}}
                    <div class="p-3 rounded-md border {{ $isArrivedOrBeyond ? 'bg-blue-50/50 border-blue-200' : 'bg-zinc-50 border-zinc-200' }}">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-medium {{ $isArrivedOrBeyond ? 'text-blue-900 font-bold' : 'text-zinc-700' }}">
                                {{ $isArrivedOrBeyond ? 'Tanggal Tiba di Pelabuhan (Aktual)' : 'Est. Tanggal Tiba' }}
                            </label>
                            @if ($isArrivedOrBeyond)
                                <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded">Realisasi Kedatangan</span>
                            @endif
                        </div>
                        @if ($isArrivedOrBeyond)
                            <input
                                type="date"
                                wire:model="editing_actual_arrival_date"
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono bg-white px-3 py-2"
                            />
                        @else
                            <input
                                type="date"
                                wire:model="editing_est_arrival_date"
                                class="w-full text-sm rounded-md border border-zinc-300 shadow-2xs focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono bg-white px-3 py-2"
                            />
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100">
                        <button
                            type="button"
                            wire:click="closeEditModal"
                            class="px-3.5 py-2 text-xs font-semibold text-zinc-700 hover:text-zinc-900 bg-white border border-zinc-200 rounded-md hover:bg-zinc-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-semibold text-white bg-zinc-900 hover:bg-zinc-800 rounded-md transition-colors shadow-2xs"
                        >
                            <span wire:loading.remove wire:target="updateStatusAndLogistics">Simpan Perubahan</span>
                            <span wire:loading wire:target="updateStatusAndLogistics">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
