<div class="space-y-6">
    {{-- Header Section --}}
    {{-- Header Section --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    LOGISTIK & GUDANG
                </span>
                <span class="text-xs text-zinc-500 font-mono">Inbound Truck Scale</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Penerimaan Material Truk
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan kedatangan armada truk material mentah dan konversi otomatis volume ke berat kilogram di server.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button
                type="button"
                @click="$dispatch('open-additive-modal')"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-800 transition-colors shadow-2xs whitespace-nowrap"
                title="Lihat rincian sisa serbuk kimia (SNF, Glukonate, Maltodextrine)"
            >
                <span>🔍 Sisa Serbuk Additive</span>
            </button>
            <button
                type="button"
                wire:click="openConversionModal"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-md bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-2xs whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <span>🧪 Olah Lab: Konversi Additive &rarr; SBTJM2</span>
            </button>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-md text-xs font-mono font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Auto-Converted</span>
            </span>
        </div>
    </div>

    {{-- Form Penerimaan Truk: Layout 2-Kolom --}}
    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            {{-- Panel Kiri: Dokumen, Truk & Jenis Material (7 Kolom) --}}
            <div class="lg:col-span-7 xl:col-span-8">
                <x-ui.card
                    title="Form Catat Penerimaan Material Truk"
                    description="Lengkapi detail surat jalan / nomor RI, identitas armada, dan jenis material masuk."
                >
                    <div class="space-y-4">
                        {{-- Referensi No. PO (Opsional) --}}
                        <div>
                            <x-ui.select
                                wire:model.live="purchase_order_id"
                                label="Referensi No. PO (Opsional / Dari Pembongkaran Tongkang PO)"
                                name="purchase_order_id"
                                placeholder="-- Bukan dari PO / Armada Lokal Mandiri --"
                            >
                                @foreach ($activePOs as $activePo)
                                    <option value="{{ $activePo->id }}">
                                        [{{ $activePo->po_number }}] - {{ $activePo->supplier->name ?? 'Supplier' }} ({{ $activePo->material_type_input ?: ($activePo->material->name ?? 'Material') }} - {{ number_format((float) $activePo->qty_ordered, 2, ',', '.') }} {{ $activePo->unit }}) — Status: {{ $activePo->status }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            @if ($purchase_order_id)
                                <div class="mt-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-lg bg-zinc-50 border border-zinc-200">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="font-bold text-zinc-900">PO Terhubung:</span>
                                        <span class="text-zinc-600">Supplier dan Material otomatis terisi dari PO terpilih.</span>
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-900 select-none bg-white px-2.5 py-1.5 rounded border border-zinc-200 shadow-2xs hover:bg-zinc-100 transition-colors">
                                        <input
                                            type="checkbox"
                                            wire:model="is_final_unloading"
                                            class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 h-4 w-4"
                                        />
                                        <span>Tandai Muatan Terakhir (Final Unloading)</span>
                                    </label>
                                </div>
                            @endif
                        </div>

                        {{-- Baris 2: Tanggal & Nomor RI --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.input
                                wire:model="receive_date"
                                label="Tanggal Penerimaan"
                                name="receive_date"
                                type="date"
                                required
                            />

                            <x-ui.input
                                wire:model="ri_no"
                                label="Nomor RI / Tiket"
                                name="ri_no"
                                placeholder="Contoh: RI-2610-001"
                                mono
                                required
                            />
                        </div>

                        {{-- Baris 3: No. Polisi Truk & Supplier --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-ui.input
                                    wire:model="vehicle_no"
                                    label="No. Polisi Truk"
                                    name="vehicle_no"
                                    placeholder="{{ $isInternalMaterial ? 'INTERNAL' : 'Contoh: DN 8888 BB' }}"
                                    mono
                                    :disabled="$isInternalMaterial"
                                    required
                                />
                                @if ($isInternalMaterial)
                                    <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                                        *Produksi internal lab TBI (Non-Ekspedisi: INTERNAL).
                                    </p>
                                @endif
                            </div>

                            <div>
                                <x-ui.select
                                    wire:model="supplier_id"
                                    label="Supplier / Vendor"
                                    name="supplier_id"
                                    placeholder="-- Pilih Supplier --"
                                    :disabled="$isInternalMaterial"
                                    required
                                >
                                    @foreach ($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </x-ui.select>
                                @if ($isInternalMaterial)
                                    <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                                        *Otomatis terkunci ke TBI Plant (Produksi Internal Lab).
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Baris 4: Material Diterima --}}
                        <div>
                            <x-ui.select
                                wire:model.live="material_id"
                                label="Material Diterima"
                                name="material_id"
                                placeholder="-- Pilih Material --"
                                required
                            >
                                @foreach ($materials as $mat)
                                    <option value="{{ $mat->id }}">
                                        {{ $mat->name === 'Semen' ? 'Semen (Ton)' : $mat->name . ' (' . $mat->unit . ')' }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            @if ($isSemen)
                                <div class="mt-2 p-2.5 rounded-md bg-zinc-50 border border-zinc-200 text-xs text-zinc-600 flex items-center gap-2">
                                    <span class="font-mono font-bold px-1.5 py-0.5 rounded bg-zinc-900 text-white text-[10px]">SEMEN TON</span>
                                    <span>Pecahan Semen dicatat dalam <strong>Tonase (Ton)</strong> dengan faktor konversi baku <strong>1 Ton = 1.000 Kg</strong>.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- Panel Kanan: Penimbangan & Kalkulator Muatan (5 Kolom) --}}
            <div class="lg:col-span-5 xl:col-span-4 sticky top-4 space-y-4">
                <x-ui.card
                    title="Penimbangan & Kalkulasi"
                    description="Hitung otomatis tonase/kubikasi ke berat aktual."
                >
                    <div class="space-y-4">
                        {{-- Volume m3 / Tonase Semen --}}
                        <div>
                            <x-ui.input
                                wire:model.live.debounce.300ms="volume_m3"
                                label="{{ $isSemen ? 'Tonase Semen Masuk (Ton)' : 'Volume Masuk (m³)' }}"
                                name="volume_m3"
                                type="text"
                                placeholder="{{ $isSemen ? 'Contoh: 25.5' : '0.00' }}"
                                suffix="{{ $isSemen ? 'Ton' : 'm³' }}"
                                mono
                                required
                            />
                            <p class="mt-1 text-[11px] text-zinc-500">
                                {{ $isSemen ? 'Masukkan berat muatan truk dalam Ton.' : 'Mendukung pemisah desimal koma (,) atau titik (.).' }}
                            </p>
                        </div>

                        {{-- Density / Konversi Satuan --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                    {{ $isSemen ? 'Konversi Satuan (Kg/Ton)' : 'Density (kg/m³)' }} <span class="text-rose-500">*</span>
                                </label>
                                @if ($masterDensity !== null)
                                    <div class="flex items-center gap-1.5 text-[11px]">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-zinc-100 text-zinc-700 border border-zinc-200">
                                            Master: {{ number_format($masterDensity, 2, ',', '.') }}
                                        </span>
                                        @if ((float) str_replace(',', '.', (string) $density) !== (float) $masterDensity)
                                            <button
                                                type="button"
                                                wire:click="resetDensityToMaster"
                                                class="text-zinc-500 hover:text-zinc-900 underline text-[10px]"
                                                title="Kembalikan ke nilai master data"
                                            >
                                                Reset
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <x-ui.input
                                wire:model.live.debounce.300ms="density"
                                name="density"
                                type="text"
                                placeholder="{{ $isSemen ? '1000' : 'Contoh: 1658' }}"
                                suffix="{{ $isSemen ? 'kg/Ton' : 'kg/m³' }}"
                                mono
                                required
                            />
                            <p class="mt-1 text-[11px] text-zinc-500">
                                @if ($isSemen)
                                    Standar density semen: 1.000 kg/Ton (1 m³ = 1 Ton).
                                @else
                                    Otomatis terisi dari master, dapat diedit manual jika bervariasi.
                                @endif
                            </p>
                        </div>

                        {{-- Real-Time Calculation Preview Card --}}
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-3.5 space-y-2">
                            <div class="flex items-center justify-between text-xs text-zinc-500 pb-2 border-b border-zinc-200/80">
                                <span class="font-semibold text-zinc-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    Hasil Berat Aktual
                                </span>
                                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-zinc-200/60 text-zinc-700">Live Preview</span>
                            </div>

                            <div class="font-mono text-xs text-zinc-500 flex items-center gap-1.5">
                                @if ($isSemen)
                                    <span>{{ $volume_m3 ? number_format((float) str_replace(',', '.', (string) $volume_m3), 2, ',', '.') : '0,00' }} Ton</span>
                                    <span class="text-zinc-400">×</span>
                                    <span>1.000 kg/Ton</span>
                                    <span class="text-zinc-400">=</span>
                                @else
                                    <span>{{ $volume_m3 ? number_format((float) str_replace(',', '.', (string) $volume_m3), 2, ',', '.') : '0,00' }} m³</span>
                                    <span class="text-zinc-400">×</span>
                                    <span>{{ $density ? number_format((float) str_replace(',', '.', (string) $density), 2, ',', '.') : '0,00' }} kg/m³</span>
                                    <span class="text-zinc-400">=</span>
                                @endif
                            </div>

                            <div class="pt-1">
                                @if ($isSemen)
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="font-mono tabular-nums text-2xl xl:text-3xl font-extrabold text-zinc-900">
                                            {{ number_format($this->totalWeight / 1000, 2, ',', '.') }}
                                        </span>
                                        <span class="text-sm font-bold text-zinc-700">Ton</span>
                                    </div>
                                    <div class="text-xs font-mono text-zinc-500 mt-0.5">
                                        = {{ number_format($this->totalWeight, 2, ',', '.') }} Kg (Netto)
                                    </div>
                                @else
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="font-mono tabular-nums text-2xl xl:text-3xl font-extrabold text-zinc-900">
                                            {{ number_format($this->totalWeight, 2, ',', '.') }}
                                        </span>
                                        <span class="text-sm font-bold text-zinc-700">Kg</span>
                                    </div>
                                    <div class="text-xs font-mono text-zinc-500 mt-0.5">
                                        = {{ number_format($this->totalWeight / 1000, 2, ',', '.') }} Ton
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Tombol Simpan Full-Width di Panel Kanan --}}
                        <div class="pt-2 space-y-2">
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                                class="w-full justify-center py-2.5 font-semibold text-xs shadow-xs"
                            >
                                <span wire:loading.remove wire:target="save">Simpan Penerimaan Material</span>
                                <span wire:loading wire:target="save" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Menghitung & Menyimpan...
                                </span>
                            </x-ui.button>

                            <div class="text-center">
                                <x-ui.unsaved-changes-guard />
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>

    {{-- Daftar Penerimaan Truk --}}
    <x-ui.card
        title="Riwayat Penerimaan Material Truk"
        description="Data logistik material masuk yang telah terekam dan terkonversi ke berat kilogram."
    >
        {{-- Search Bar & Filter Presets --}}
        <div class="mb-4 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                {{-- Quick Date Range Presets --}}
                <div class="inline-flex rounded-md border border-zinc-200 bg-zinc-100 p-0.5 text-xs font-medium">
                    <button
                        type="button"
                        wire:click="setDateRange('all')"
                        class="px-2.5 py-1.5 rounded transition-all {{ $filterDateRange === 'all' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('today')"
                        class="px-2.5 py-1.5 rounded transition-all {{ $filterDateRange === 'today' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('yesterday')"
                        class="px-2.5 py-1.5 rounded transition-all {{ $filterDateRange === 'yesterday' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Kemarin
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('this_week')"
                        class="px-2.5 py-1.5 rounded transition-all {{ $filterDateRange === 'this_week' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Minggu Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('this_month')"
                        class="px-2.5 py-1.5 rounded transition-all {{ $filterDateRange === 'this_month' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Bulan Ini
                    </button>
                </div>

                <div class="w-full sm:w-64">
                    <x-ui.input
                        wire:model.live.debounce.250ms="search"
                        placeholder="Cari No. RI, No. Truk, supplier..."
                        prefix="🔍"
                        :clearable="true"
                        :has-value="!empty($search)"
                        loading-target="search"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.table-density-toggle />

                <a
                    href="{{ route('material-receives.report-excel', ['month' => now()->month, 'year' => now()->year]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Export Laporan Rekapitulasi Bulan Berjalan ke Excel (.xlsx / .xls)"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Excel</span>
                </a>

                <a
                    href="{{ route('material-receives.report-pdf', ['month' => now()->month, 'year' => now()->year]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Cetak Laporan Rekapitulasi Bulan Berjalan (PDF)"
                >
                    <svg class="w-4 h-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Rekap (PDF)</span>
                </a>
            </div>
        </div>

        @php
            $headers = ['Tanggal', 'No. RI', 'Ref. PO', 'No. Polisi', 'Supplier', 'Material', 'Volume / Tonase', 'Density', 'Total Berat', 'Aksi'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($receives as $rec)
                @php
                    $isRecSemen = $rec->material && strcasecmp($rec->material->name, 'Semen') === 0;
                    $isRecAdditive = $rec->material && strcasecmp($rec->material->name, 'Additive') === 0;
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Tanggal --}}
                    <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                        <div>{{ $rec->receive_date ? $rec->receive_date->format('d/m/Y') : '-' }}</div>
                        <div class="text-[11px] text-zinc-400 font-mono">{{ $rec->created_at ? $rec->created_at->format('H:i') : '' }}</div>
                    </td>

                    {{-- No. RI --}}
                    <td class="px-4 py-3 font-mono font-semibold text-zinc-900 whitespace-nowrap">
                        {{ $rec->ri_no }}
                    </td>

                    {{-- Ref. PO --}}
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                        @if ($rec->purchaseOrder)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-800 border border-zinc-300">
                                {{ $rec->purchaseOrder->po_number }}
                            </span>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
                    </td>

                    {{-- No. Truk --}}
                    <td class="px-4 py-3 font-mono text-zinc-700 whitespace-nowrap">
                        {{ $rec->vehicle_no }}
                    </td>

                    {{-- Supplier --}}
                    <td class="px-4 py-3 font-medium text-zinc-900">
                        {{ $rec->supplier->name ?? '-' }}
                    </td>

                    {{-- Material --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($isRecAdditive)
                            <button
                                type="button"
                                @click="$dispatch('open-additive-modal')"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-zinc-100 hover:bg-zinc-200 text-zinc-900 border border-zinc-200 transition-colors shadow-2xs"
                                title="Klik untuk melihat detail 4 serbuk kimia Additive"
                            >
                                <span>Additive</span>
                                <span class="text-[10px] text-zinc-500">🔍</span>
                            </button>
                        @else
                            <x-ui.badge variant="zinc">
                                {{ $rec->material->name ?? '-' }}
                            </x-ui.badge>
                        @endif
                    </td>

                    {{-- Volume / Tonase --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-900 whitespace-nowrap">
                        @if ($isRecSemen)
                            {{ number_format((float) $rec->volume_m3, 2, ',', '.') }} Ton
                        @else
                            {{ number_format((float) $rec->volume_m3, 2, ',', '.') }} m³
                        @endif
                    </td>

                    {{-- Density Digunakan --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 whitespace-nowrap">
                        @if ($isRecSemen)
                            {{ number_format((float) $rec->density_used, 2, ',', '.') }} kg/Ton
                        @else
                            {{ number_format((float) $rec->density_used, 2, ',', '.') }} kg/m³
                        @endif
                    </td>

                    {{-- Total Berat --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-zinc-900 whitespace-nowrap">
                        @if ($isRecSemen)
                            <div>{{ number_format((float) $rec->total_weight_kg / 1000, 2, ',', '.') }} Ton</div>
                            <div class="text-[10px] font-normal text-zinc-400">({{ number_format((float) $rec->total_weight_kg, 2, ',', '.') }} Kg)</div>
                        @else
                            <div>{{ number_format((float) $rec->total_weight_kg, 2, ',', '.') }} Kg</div>
                            <div class="text-[10px] font-normal text-zinc-400">({{ number_format((float) $rec->total_weight_kg / 1000, 2, ',', '.') }} Ton)</div>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="px-4 py-3 last:pr-5 text-right whitespace-nowrap">
                        @php
                            $receiveWaText = "*BUKTI PENERIMAAN MATERIAL TRUK - TBI ERP*\n"
                                . "No. RI: {$rec->ri_no}\n"
                                . "Tanggal: " . ($rec->receive_date ? $rec->receive_date->format('d/m/Y') : '-') . "\n"
                                . ($rec->purchaseOrder ? "Ref PO: {$rec->purchaseOrder->po_number}\n" : "")
                                . "No. Polisi: {$rec->vehicle_no}\n"
                                . "Supplier: " . ($rec->supplier->name ?? '-') . "\n"
                                . "Material: " . ($rec->material->name ?? '-') . "\n"
                                . "Volume: " . number_format((float) $rec->volume_m3, 2, ',', '.') . ($isRecSemen ? " Ton\n" : " m³\n")
                                . "Total Berat: " . ($isRecSemen
                                    ? number_format((float) $rec->total_weight_kg / 1000, 2, ',', '.') . " Ton (" . number_format((float) $rec->total_weight_kg, 2, ',', '.') . " Kg)"
                                    : number_format((float) $rec->total_weight_kg, 2, ',', '.') . " Kg (" . number_format((float) $rec->total_weight_kg / 1000, 2, ',', '.') . " Ton)");
                        @endphp
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            <x-ui.copy-button :text="$rec->ri_no" title="Salin No. RI" />
                            <x-ui.whatsapp-button :message="$receiveWaText" label="WA" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada catatan penerimaan material truk.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($receives->hasPages() || isset($this->perPage) || $receives->total() > 0)
            <div class="mt-4 pt-3 border-t border-zinc-100">
                {{ $receives->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Riwayat Konversi Lab: Additive -> SBTJM2 --}}
    @if ($recentConversions->isNotEmpty())
        <x-ui.card
            title="Riwayat Konversi Lab: Serbuk Additive → SBTJM2 (Liter)"
            description="Log formulasi pengolahan 4 serbuk kimia internal laboratorium batching plant TBI."
        >
            @php
                $convHeaders = ['Batch Lab', 'Tanggal Olah', 'Total Serbuk (-)', 'Rincian 4 Serbuk Kimia', 'Hasil SBTJM2 (+)', 'No. RI', 'Petugas', 'Catatan'];
            @endphp

            <x-ui.table :headers="$convHeaders">
                @foreach ($recentConversions as $conv)
                    <tr class="hover:bg-zinc-50/80 transition-colors">
                        <td class="px-4 py-3 first:pl-5 font-mono font-semibold text-xs text-zinc-900 whitespace-nowrap">
                            {{ $conv->conversion_no }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 whitespace-nowrap">
                            {{ $conv->conversion_date ? $conv->conversion_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-red-600 font-semibold tabular-nums whitespace-nowrap">
                            -{{ number_format((float) $conv->additive_used_kg, 2, ',', '.') }} Kg
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-zinc-600 whitespace-nowrap">
                            @if ((float) $conv->snf1_kg > 0 || (float) $conv->snf2_kg > 0)
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-700 font-medium">SNF1: {{ number_format((float) $conv->snf1_kg, 0, ',', '.') }}k</span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-700 font-medium">SNF2: {{ number_format((float) $conv->snf2_kg, 0, ',', '.') }}k</span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-700 font-medium">Glu: {{ number_format((float) $conv->sodium_glukonate_kg, 0, ',', '.') }}k</span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-700 font-medium">Mal: {{ number_format((float) $conv->maltodextrine_kg, 0, ',', '.') }}k</span>
                                </div>
                            @else
                                <span class="text-zinc-400 italic">Data batch lama</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-emerald-700 font-bold tabular-nums whitespace-nowrap">
                            +{{ number_format((float) $conv->sbtjm2_produced_liter, 2, ',', '.') }} Liter
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-800 whitespace-nowrap">
                            {{ $conv->materialReceive->ri_no ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 whitespace-nowrap">
                            {{ $conv->creator->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3 last:pr-5 text-xs text-zinc-500 max-w-xs truncate">
                            {{ $conv->notes ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    @endif

    {{-- Modal Konversi Lab (4 Serbuk Kimia Additive -> SBTJM2 Liter) --}}
    @if ($showConversionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="closeConversionModal">
            <!-- Backdrop Zinc Anti-Slop -->
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-xs transition-opacity" wire:click="closeConversionModal"></div>

            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl border border-zinc-200 overflow-hidden p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-md bg-zinc-100 text-zinc-900 font-mono text-sm">🧪</span>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900">Produksi Internal Lab: Konversi Serbuk Additive</h3>
                                <p class="text-xs text-zinc-500">Formulasi 4 serbuk kimia aktif + air menjadi cairan siap pakai SBTJM2.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeConversionModal"
                            class="text-zinc-400 hover:text-zinc-600 p-1 rounded-md hover:bg-zinc-100 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveConversion" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Tanggal Mixing / Olah --}}
                            <div>
                                <x-ui.input
                                    wire:model="conv_date"
                                    label="Tanggal Mixing / Olah Lab"
                                    name="conv_date"
                                    type="date"
                                    required
                                />
                            </div>

                            {{-- Target Hasil Jadi SBTJM2 Diproduksi --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                                        Target SBTJM2 (Liter) <span class="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        wire:click="calculateAutoRecipe"
                                        class="text-xs font-medium text-zinc-600 hover:text-zinc-900 underline flex items-center gap-1"
                                        title="Hitung proporsi serbuk otomatis berdasarkan standar resep"
                                    >
                                        <span>🔄 Hitung Resep</span>
                                    </button>
                                </div>
                                <x-ui.input
                                    wire:model.live.debounce.300ms="conv_sbtjm2_liter"
                                    name="conv_sbtjm2_liter"
                                    suffix="Liter"
                                    placeholder="Contoh: 5000"
                                    type="number"
                                    step="0.01"
                                    required
                                />
                            </div>
                        </div>

                        {{-- Section Bahan Serbuk Kimia (Powders Breakdown) --}}
                        <div class="p-4 rounded-lg bg-zinc-50/80 border border-zinc-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-800">
                                        Takaran 4 Serbuk Kimia Additive
                                    </span>
                                    <span class="text-[11px] font-mono text-zinc-500">
                                        (Total: {{ number_format($this->totalAdditivePowderUsed, 2, ',', '.') }} Kg)
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="$dispatch('open-additive-modal')"
                                    class="text-xs text-zinc-600 hover:text-zinc-900 underline font-medium"
                                >
                                    🔍 Cek Sisa Stok Powder
                                </button>
                            </div>

                            @php
                                $powderMap = $powders->keyBy('code');
                                $snf1Stock = (float) ($powderMap->get('SNF_001')->stock_kg ?? 0);
                                $snf2Stock = (float) ($powderMap->get('SNF_002')->stock_kg ?? 0);
                                $gluStock = (float) ($powderMap->get('SODIUM_GLUKONATE')->stock_kg ?? 0);
                                $malStock = (float) ($powderMap->get('MALTODEXTRINE')->stock_kg ?? 0);
                            @endphp

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                {{-- Powder 1: SNF 001 --}}
                                <div class="p-2.5 rounded bg-white border border-zinc-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-zinc-800">1. SNF-C 001 (15%)</span>
                                        <span class="text-[10px] font-mono text-zinc-500">Tersisa: {{ number_format($snf1Stock, 1, ',', '.') }} Kg</span>
                                    </div>
                                    <x-ui.input
                                        wire:model.live.debounce.300ms="conv_snf1_kg"
                                        name="conv_snf1_kg"
                                        suffix="Kg"
                                        type="number"
                                        step="0.01"
                                        required
                                    />
                                </div>

                                {{-- Powder 2: SNF 002 --}}
                                <div class="p-2.5 rounded bg-white border border-zinc-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-zinc-800">2. SNF-C 002 (15%)</span>
                                        <span class="text-[10px] font-mono text-zinc-500">Tersisa: {{ number_format($snf2Stock, 1, ',', '.') }} Kg</span>
                                    </div>
                                    <x-ui.input
                                        wire:model.live.debounce.300ms="conv_snf2_kg"
                                        name="conv_snf2_kg"
                                        suffix="Kg"
                                        type="number"
                                        step="0.01"
                                        required
                                    />
                                </div>

                                {{-- Powder 3: Sodium Glukonate --}}
                                <div class="p-2.5 rounded bg-white border border-zinc-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-zinc-800">3. Sodium Glukonate (6%)</span>
                                        <span class="text-[10px] font-mono text-zinc-500">Tersisa: {{ number_format($gluStock, 1, ',', '.') }} Kg</span>
                                    </div>
                                    <x-ui.input
                                        wire:model.live.debounce.300ms="conv_sodium_glukonate_kg"
                                        name="conv_sodium_glukonate_kg"
                                        suffix="Kg"
                                        type="number"
                                        step="0.01"
                                        required
                                    />
                                </div>

                                {{-- Powder 4: Maltodextrine --}}
                                <div class="p-2.5 rounded bg-white border border-zinc-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-semibold text-zinc-800">4. Maltodextrine (1%)</span>
                                        <span class="text-[10px] font-mono text-zinc-500">Tersisa: {{ number_format($malStock, 1, ',', '.') }} Kg</span>
                                    </div>
                                    <x-ui.input
                                        wire:model.live.debounce.300ms="conv_maltodextrine_kg"
                                        name="conv_maltodextrine_kg"
                                        suffix="Kg"
                                        type="number"
                                        step="0.01"
                                        required
                                    />
                                </div>
                            </div>

                            {{-- Air Pelarut --}}
                            <div class="pt-2 border-t border-zinc-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-zinc-800 block text-xs">Air Pelarut (Plant Batching):</span>
                                    <span class="text-[11px] text-zinc-500">Pelarut murni tanpa batas dari plant TBI (standar ~63.5% = 3.175 L).</span>
                                </div>
                                <div class="w-full sm:w-48">
                                    <x-ui.input
                                        wire:model.live.debounce.300ms="conv_water_kg"
                                        name="conv_water_kg"
                                        suffix="Liter"
                                        placeholder="3175"
                                        type="number"
                                        step="0.01"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- Keterangan / Batch Racikan Lab --}}
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">
                                Keterangan / Catatan Formulasi Lab
                            </label>
                            <textarea
                                wire:model="conv_notes"
                                rows="2"
                                class="w-full rounded-md border border-zinc-300 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-zinc-900"
                                placeholder="Contoh: Formulasi standar batch SNF-C 5.000 L, pengujian slump tinggi..."
                            ></textarea>
                        </div>

                        {{-- Box Transparansi Audit Mutasi --}}
                        <div class="p-3 rounded-md bg-zinc-50 border border-zinc-200 text-xs text-zinc-600 space-y-1">
                            <div class="font-semibold text-zinc-800 flex items-center gap-1.5">
                                <span>⚙️</span> Mutasi Otomatis yang Terjadi:
                            </div>
                            <div>• Memotong stok 4 serbuk kimia (<strong class="text-zinc-900">SNF-1, SNF-2, Glukonate, Maltodextrine</strong>) sebesar total <strong class="text-zinc-900">{{ number_format($this->totalAdditivePowderUsed, 2, ',', '.') }} Kg</strong>.</div>
                            <div>• Mengurangi stok teoritis master <strong class="text-zinc-900">Additive</strong> secara otomatis di tabel rekonsiliasi.</div>
                            <div>• Menambah stok teoritis <strong class="text-zinc-900">SBTJM2</strong> sebesar <strong class="text-zinc-900">{{ number_format((float) str_replace(',', '.', (string) $conv_sbtjm2_liter), 2, ',', '.') }} Liter</strong> (supplier: <strong class="text-zinc-900">TBI Plant</strong>, armada: <strong class="text-zinc-900">INTERNAL</strong>).</div>
                        </div>

                        {{-- Modal Action Buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200">
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                wire:click="closeConversionModal"
                            >
                                Batal
                            </x-ui.button>
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                size="sm"
                            >
                                Simpan & Eksekusi Konversi
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
