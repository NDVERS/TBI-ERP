<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    OPERASIONAL PLANT
                </span>
                <span class="text-xs text-zinc-500 font-mono">Daily Production & Batching Dispatch</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Produksi Harian
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan surat jalan / docket produksi beton ready mix, konsumsi batching aktual, dan rekonsiliasi stok otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="emerald" size="md" class="font-mono">
                WITA (Asia/Makassar)
            </x-ui.badge>
        </div>
    </div>

    {{-- Main Grid: Form Input (Kiri) & Widget Ringkasan Stok Teoritis (Kanan) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Kolom Kiri: Form Input Docket Produksi (lg:col-span-8) --}}
        <div class="lg:col-span-8 space-y-6">
            <x-ui.card
                title="Form Input Surat Jalan / Docket Produksi"
                description="Lengkapi detail pengiriman dan komposisi batching aktual untuk mencetak surat jalan dan memotong stok teoritis."
            >
                <form wire:submit.prevent="save" class="space-y-6">
                    {{-- Bagian 1: Data Identifikasi & Waktu --}}
                    <div>
                        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
                            1. Identitas Surat Jalan & Waktu Dispatch
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            {{-- Nomor Docket / Surat Jalan --}}
                            <div>
                                <x-ui.input
                                    wire:model="docket_no"
                                    label="Nomor Surat Jalan / Docket"
                                    name="docket_no"
                                    placeholder="SP01_7600"
                                    class="uppercase font-mono"
                                    mono
                                    required
                                />
                                <span class="text-[11px] text-zinc-500 font-mono mt-1 block">
                                    Auto-increment SP01_xxxx (Dimulai dari SP01_7600)
                                </span>
                            </div>

                            {{-- Tanggal Produksi --}}
                            <x-ui.input
                                wire:model="production_date"
                                label="Tanggal Produksi"
                                name="production_date"
                                type="date"
                                required
                            />

                            {{-- Jam Load (WITA) --}}
                            <x-ui.input
                                wire:model="time_load"
                                label="Jam Load (WITA)"
                                name="time_load"
                                type="time"
                                required
                            />
                        </div>
                    </div>

                    {{-- Bagian 2: Data Order & Armada --}}
                    <div class="pt-2 border-t border-zinc-100">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                2. Data Customer, Mutu Beton & Armada
                            </h3>
                            @if ($this->selectedSalesOrder)
                                @php
                                    $targetVol = (float) $this->selectedSalesOrder->volume_m3;
                                    $deliveredVol = (float) $this->selectedSalesOrder->delivered_volume_m3;
                                    $remVol = max(0, $targetVol - $deliveredVol);
                                    $pct = $targetVol > 0 ? min(100, round(($deliveredVol / $targetVol) * 100, 1)) : 0;
                                @endphp
                                <div class="flex items-center gap-2 text-xs font-mono">
                                    <span class="text-zinc-500">Kuota SO:</span>
                                    <span class="font-bold text-zinc-900">{{ number_format($deliveredVol, 2, ',', '.') }} m³ / {{ number_format($targetVol, 2, ',', '.') }} m³</span>
                                    <x-ui.badge :variant="$pct >= 100 ? 'emerald' : ($pct > 0 ? 'amber' : 'zinc')" size="sm">
                                        {{ $pct }}% (Sisa {{ number_format($remVol, 2, ',', '.') }} m³)
                                    </x-ui.badge>
                                </div>
                            @endif
                        </div>

                        {{-- Dropdown Referensi Sales Order (SO) --}}
                        <div class="mb-4">
                            <x-ui.select
                                wire:model.live="sales_order_id"
                                wire:key="select-so-{{ $docket_no }}"
                                label="Referensi No. Sales Order (SO)"
                                name="sales_order_id"
                                placeholder="-- Tanpa SO / Penjualan Langsung / Retail --"
                            >
                                <option value="">-- Tanpa SO / Penjualan Langsung / Retail --</option>
                                @foreach ($activeSalesOrders as $aso)
                                    @php
                                        $target = (float) $aso->volume_m3;
                                        $delivered = (float) $aso->delivered_volume_m3;
                                        $sisa = max(0, $target - $delivered);
                                    @endphp
                                    <option value="{{ $aso->id }}" wire:key="opt-so-{{ $aso->id }}">
                                        [{{ $aso->so_number }}] - {{ strtoupper($aso->customer->name ?? '-') }} | Mutu: {{ $aso->jobMix->mutu_code ?? '-' }} (Sisa: {{ number_format($sisa, 1, ',', '.') }} m³ / {{ number_format($target, 1, ',', '.') }} m³)
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <span class="text-[11px] text-zinc-500 mt-1 block">
                                Memilih No. SO otomatis mengisi Nama Customer, Lokasi Proyek, Mutu Beton, dan memotong sisa kuota volume pemesanan saat docket disimpan.
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {{-- Nama Customer --}}
                            <div>
                                <x-ui.select
                                    wire:model.live="customer_id"
                                    wire:key="select-customer-{{ $docket_no }}"
                                    label="Nama Customer"
                                    name="customer_id"
                                    placeholder="-- Pilih Master Customer --"
                                    class="uppercase"
                                    required
                                >
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}" wire:key="opt-cust-{{ $c->id }}">
                                            {{ strtoupper($c->name) }}
                                        </option>
                                    @endforeach
                                </x-ui.select>
                            </div>

                            {{-- Alamat Proyek (Auto-fill Reactive) --}}
                            <div class="sm:col-span-2">
                                <x-ui.input
                                    wire:model="address"
                                    label="Alamat Proyek / Lokasi Pengecoran"
                                    name="address"
                                    placeholder="OTOMATIS TERISI DARI CUSTOMER, DAPAT DISESUAIKAN..."
                                    class="uppercase"
                                />
                            </div>

                            {{-- Mutu Beton --}}
                            <div>
                                <x-ui.select
                                    wire:model="mutu_code"
                                    wire:key="select-mutu-{{ $docket_no }}"
                                    label="Mutu Beton"
                                    name="mutu_code"
                                    placeholder="-- Pilih Mutu Beton --"
                                    class="uppercase font-mono"
                                    required
                                >
                                    <optgroup label="Seri K (K-Series)">
                                        @foreach ($kSeriesMutuList as $mutu)
                                            <option value="{{ $mutu }}" wire:key="opt-mutu-{{ \Illuminate\Support\Str::slug($mutu) }}">
                                                {{ strtoupper($mutu) }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Seri FC & FS (Megapascal & Flexural)">
                                        @foreach ($fcFsMutuList as $mutu)
                                            <option value="{{ $mutu }}" wire:key="opt-mutu-{{ \Illuminate\Support\Str::slug($mutu) }}">
                                                {{ strtoupper($mutu) }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </x-ui.select>
                            </div>

                            {{-- Nomor Truck Mixer (TM) --}}
                            <x-ui.input
                                wire:model="truck_no"
                                label="Nomor TM (Truck Mixer)"
                                name="truck_no"
                                placeholder="Contoh: 01, 08"
                                prefix="TM-"
                                class="uppercase font-mono"
                                mono
                                required
                            />

                            {{-- Volume (m3) --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="volume_m3"
                                label="Volume (m³)"
                                name="volume_m3"
                                type="text"
                                placeholder="Contoh: 7.00"
                                suffix="m³"
                                mono
                                required
                            />
                        </div>
                    </div>

                    {{-- Bagian 3: Komposisi Batching Aktual (Input Manual) --}}
                    <div class="pt-2 border-t border-zinc-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                3. Komposisi Batching Aktual (Input Manual)
                            </h3>
                            <span class="text-xs text-zinc-500 font-mono">
                                Otomatis memotong Stok Akhir Teoritis
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-lg bg-zinc-50 border border-zinc-200">
                            {{-- Semen --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="silo1_act"
                                label="Semen Aktual"
                                name="silo1_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- Pasir Palu --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="pasir_palu_act"
                                label="Pasir Palu Aktual"
                                name="pasir_palu_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- Pasir Lokal --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="pasir_lokal_act"
                                label="Pasir Lokal Aktual"
                                name="pasir_lokal_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- Batu 1/2 --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="split_1_2_act"
                                label="Batu 1/2 Aktual"
                                name="split_1_2_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- Batu 2/3 --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="split_2_3_act"
                                label="Batu 2/3 Aktual"
                                name="split_2_3_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- Air --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="water_act"
                                label="Air Aktual"
                                name="water_act"
                                type="text"
                                placeholder="0.00"
                                suffix="Liter"
                                mono
                                required
                            />

                            {{-- SBTJM2 (Admixture) --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="admixture_act"
                                label="SBTJM2 (Admixture)"
                                name="admixture_act"
                                type="text"
                                placeholder="0.00"
                                suffix="Liter"
                                mono
                                required
                            />
                        </div>

                        {{-- Ringkasan Instan Timbangan & Densitas Beton --}}
                        <div class="mt-3 p-3.5 rounded-lg border border-zinc-200 bg-zinc-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div>
                                <div class="font-medium text-zinc-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Ringkasan Batching Aktual</span>
                                </div>
                                <div class="font-mono text-zinc-500 text-[11px] mt-0.5">
                                    Total Berat: <strong class="text-zinc-900">{{ number_format($this->totalBatchWeight, 0, ',', '.') }} kg</strong>
                                    ({{ number_format($this->totalBatchWeight / 1000, 2, ',', '.') }} Ton)
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-[11px] text-zinc-400 font-mono">Densitas Campuran:</div>
                                    <div class="font-mono font-bold text-sm text-zinc-900">
                                        {{ number_format($this->batchDensity, 1, ',', '.') }} <span class="text-[10px] font-normal text-zinc-500">kg/m³</span>
                                    </div>
                                </div>
                                @if ($this->totalBatchWeight > 0 && $this->batchDensity >= 2200 && $this->batchDensity <= 2550)
                                    <x-ui.badge variant="emerald" size="sm" dot>
                                        Densitas Normal
                                    </x-ui.badge>
                                @elseif ($this->totalBatchWeight > 0)
                                    <x-ui.badge variant="amber" size="sm" dot>
                                        Periksa Timbangan
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="zinc" size="sm">
                                        Siap Input
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex items-center justify-between gap-3 pt-4 border-t border-zinc-200">
                        <div>
                            <x-ui.unsaved-changes-guard />
                        </div>

                        <div class="flex items-center gap-3">
                            <div wire:loading class="text-xs font-mono text-zinc-500 flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-zinc-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Menyimpan docket & menghitung stok...</span>
                            </div>

                            <x-ui.button
                                type="submit"
                                variant="primary"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove>Simpan & Terbitkan Docket</span>
                                <span wire:loading>Memproses...</span>
                            </x-ui.button>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>

        {{-- Kolom Kanan: Widget Ringkasan Stok Akhir Teoritis (lg:col-span-4) --}}
        <div class="lg:col-span-4">
            <div class="sticky top-6 space-y-4">
                <x-ui.card
                    title="Stok Akhir Teoritis"
                    description="Ketersediaan material aktual terkini. Berkurang otomatis saat tiket disubmit."
                >
                    <x-slot:action>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Real-Time
                        </span>
                    </x-slot:action>

                    <div class="divide-y divide-zinc-100">
                        @foreach ($stockSummaries as $mStock)
                            @php
                                $isLowStock = (float) $mStock['stok_teoritis'] < 50000;
                            @endphp
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <div class="flex items-center gap-1.5 font-semibold text-zinc-900">
                                        <span>{{ $mStock['material_name'] }}</span>
                                        @if ($isLowStock)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                                ⚠️ Menipis
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-zinc-400 font-mono mt-0.5">
                                        Terima: +{{ number_format($mStock['penerimaan'], 0, ',', '.') }} | Pakai: -{{ number_format($mStock['pemakaian'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold font-mono tabular-nums text-sm {{ $mStock['stok_teoritis'] > 50000 ? 'text-zinc-900' : 'text-amber-600' }}">
                                        {{ number_format($mStock['stok_teoritis'], 2, ',', '.') }}
                                    </div>
                                    <span class="text-[10px] text-zinc-500 font-mono">{{ $mStock['unit'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>

    {{-- Riwayat Surat Jalan / Docket Produksi Harian --}}
    <x-ui.card
        title="Riwayat Surat Jalan & Produksi Harian"
        description="Daftar tiket pengiriman beton ready mix yang telah diterbitkan pada plant."
    >
        <x-slot:action>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                {{-- Quick Date Range Presets --}}
                <div class="inline-flex rounded-md border border-zinc-200 bg-zinc-100 p-0.5 text-xs font-medium">
                    <button
                        type="button"
                        wire:click="setDateRange('all')"
                        class="px-2 py-1 rounded transition-all {{ $filterDateRange === 'all' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('today')"
                        class="px-2 py-1 rounded transition-all {{ $filterDateRange === 'today' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('yesterday')"
                        class="px-2 py-1 rounded transition-all {{ $filterDateRange === 'yesterday' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Kemarin
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('this_week')"
                        class="px-2 py-1 rounded transition-all {{ $filterDateRange === 'this_week' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Minggu Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setDateRange('this_month')"
                        class="px-2 py-1 rounded transition-all {{ $filterDateRange === 'this_month' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                    >
                        Bulan Ini
                    </button>
                </div>

                <div class="w-full sm:w-52">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari docket, customer, TM..."
                        prefix="🔍"
                        size="sm"
                    />
                </div>

                <x-ui.table-density-toggle />

                <a
                    href="{{ route('production-dockets.report-excel', ['month' => now()->month, 'year' => now()->year]) }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Export Rekap Bulanan Produksi ke Excel"
                >
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Excel</span>
                </a>

                <a
                    href="{{ route('production-dockets.report-pdf', ['month' => now()->month, 'year' => now()->year]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Cetak Rekap Bulanan Produksi (PDF)"
                >
                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>PDF</span>
                </a>
            </div>
        </x-slot:action>

        @php
            $headers = [
                ['label' => 'No', 'class' => 'w-12 text-center'],
                ['label' => 'No. Docket', 'class' => ''],
                ['label' => 'Waktu', 'class' => 'hidden sm:table-cell'],
                ['label' => 'Customer & Proyek', 'class' => ''],
                ['label' => 'Mutu Beton', 'class' => ''],
                ['label' => 'No. TM', 'class' => ''],
                ['label' => 'Vol (m³)', 'class' => 'text-right'],
                ['label' => 'Kumulatif (m³)', 'class' => 'text-right hidden md:table-cell'],
                ['label' => 'Operator', 'class' => 'hidden lg:table-cell'],
                ['label' => 'Aksi', 'class' => 'text-right'],
            ];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($dockets as $index => $d)
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- No --}}
                    <td class="px-4 py-3 text-center text-xs text-zinc-500 font-mono tabular-nums">
                        {{ $dockets->firstItem() + $index }}
                    </td>

                    {{-- No Docket --}}
                    <td class="px-4 py-3 font-mono font-bold text-zinc-900 text-xs">
                        <span class="px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200">
                            {{ $d->docket_no }}
                        </span>
                    </td>

                    {{-- Waktu --}}
                    <td class="px-4 py-3 text-xs font-mono tabular-nums text-zinc-600 hidden sm:table-cell">
                        <div>{{ $d->production_date ? \Carbon\Carbon::parse($d->production_date)->format('d/m/Y') : '-' }}</div>
                        <div class="text-[11px] text-zinc-400">{{ substr($d->time_load, 0, 5) }} WITA</div>
                    </td>

                    {{-- Customer & Proyek --}}
                    <td class="px-4 py-3 text-xs">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-zinc-900">{{ $d->customer_name }}</span>
                            @if ($d->salesOrder)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $d->salesOrder->so_number }}
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] text-zinc-500 truncate max-w-xs">{{ $d->address ?? '-' }}</div>
                    </td>

                    {{-- Mutu Beton --}}
                    <td class="px-4 py-3 text-xs font-mono font-semibold text-zinc-800">
                        {{ $d->mutu_code }}
                    </td>

                    {{-- TM --}}
                    <td class="px-4 py-3 text-xs font-mono text-zinc-700">
                        <span class="px-1.5 py-0.5 rounded bg-zinc-50 border border-zinc-200">
                            {{ $d->truck_no }}
                        </span>
                    </td>

                    {{-- Volume --}}
                    <td class="px-4 py-3 text-xs font-mono font-bold tabular-nums text-right text-zinc-900">
                        {{ number_format((float) $d->volume_m3, 2, ',', '.') }}
                    </td>

                    {{-- Kumulatif --}}
                    <td class="px-4 py-3 text-xs font-mono tabular-nums text-right text-zinc-600 hidden md:table-cell">
                        {{ number_format((float) $d->daily_cumulative_m3, 2, ',', '.') }}
                    </td>

                    {{-- Operator --}}
                    <td class="px-4 py-3 text-xs text-zinc-500 hidden lg:table-cell font-mono">
                        {{ $d->creator?->name ?? 'Operator' }}
                    </td>

                    {{-- Aksi --}}
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        @php
                            $docketWaText = "*SURAT JALAN BETON COR - TBI ERP*\n"
                                . "No. Docket: {$d->docket_no}\n"
                                . "Tanggal: " . ($d->production_date ? \Carbon\Carbon::parse($d->production_date)->format('d/m/Y') : '-') . " (" . substr($d->time_load, 0, 5) . " WITA)\n"
                                . "Customer: {$d->customer_name}\n"
                                . ($d->address ? "Proyek: {$d->address}\n" : "")
                                . "Mutu Beton: {$d->mutu_code}\n"
                                . "Truck Mixer: {$d->truck_no}\n"
                                . "Volume: " . number_format((float) $d->volume_m3, 2, ',', '.') . " m³\n"
                                . "Kumulatif Hari Ini: " . number_format((float) $d->daily_cumulative_m3, 2, ',', '.') . " m³\n"
                                . "Operator Plant: " . ($d->creator?->name ?? 'TBI Operator');
                        @endphp
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            @if (auth()->user()?->role === 'admin')
                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $d->id }})"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900 transition-colors shadow-2xs"
                                    title="Edit Data Surat Jalan (Khusus Admin Master)"
                                >
                                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            @endif

                            <a
                                href="{{ route('production-dockets.pdf', $d) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900 transition-colors shadow-2xs"
                                title="Cetak Surat Jalan / Delivery Ticket Resmi (PDF)"
                            >
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>PDF</span>
                            </a>
                            <x-ui.copy-button :text="$d->docket_no" title="Salin No. Docket" />
                            <x-ui.whatsapp-button :message="$docketWaText" label="WA" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-4 py-8 text-center text-zinc-500 text-sm">
                        Belum ada riwayat surat jalan produksi yang dicatat.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($dockets->hasPages() || isset($this->perPage) || $dockets->total() > 0)
            <div class="pt-4 border-t border-zinc-100">
                {{ $dockets->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Modal Edit Surat Jalan (Khusus Admin / Master) --}}
    @if ($showEditModal)
        <div
            @keydown.escape.window="$wire.closeEditModal()"
            wire:click.self="closeEditModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-zinc-950/50 backdrop-blur-xs flex items-center justify-center p-4 cursor-pointer"
        >
            <div class="bg-white rounded-lg border border-zinc-200 shadow-xl max-w-2xl w-full p-6 space-y-4 cursor-default max-h-[90vh] flex flex-col">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100 shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-zinc-900">
                                Edit Surat Jalan Produksi
                            </h3>
                            <span class="px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                Khusus Admin
                            </span>
                        </div>
                        <p class="text-xs font-mono text-zinc-500 mt-0.5">
                            Docket: <span class="font-bold text-zinc-700">{{ $edit_docket_no }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeEditModal"
                        class="text-zinc-400 hover:text-zinc-600 p-1 rounded-md transition-colors"
                        title="Tutup Modal"
                    >
                        ✕
                    </button>
                </div>

                {{-- Modal Body (Scrollable Form) --}}
                <form wire:submit.prevent="updateDocket" class="space-y-4 overflow-y-auto pr-1 flex-1">
                    {{-- Section 1: Informasi Dokumen & Waktu --}}
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            Informasi Dokumen & Pengiriman
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <x-ui.input
                                wire:model="edit_docket_no"
                                label="Nomor Docket *"
                                name="edit_docket_no"
                                mono
                                required
                            />
                            <x-ui.input
                                wire:model="edit_truck_no"
                                label="Nomor Truck Mixer *"
                                name="edit_truck_no"
                                mono
                                placeholder="Contoh: TM-01"
                                required
                            />
                            <x-ui.input
                                wire:model="edit_production_date"
                                label="Tanggal Produksi *"
                                name="edit_production_date"
                                type="date"
                                required
                            />
                            <x-ui.input
                                wire:model="edit_time_load"
                                label="Jam Loading (WITA) *"
                                name="edit_time_load"
                                type="time"
                                required
                            />
                        </div>
                    </div>

                    {{-- Section 2: Customer & Mutu Beton --}}
                    <div class="pt-2 border-t border-zinc-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            Customer, Proyek & Mutu
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <x-ui.input
                                    wire:model="edit_customer_name"
                                    label="Nama Customer *"
                                    name="edit_customer_name"
                                    required
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.input
                                    wire:model="edit_address"
                                    label="Alamat / Lokasi Proyek"
                                    name="edit_address"
                                    placeholder="Contoh: Jl. Poros Samarinda - Bontang"
                                />
                            </div>
                            <x-ui.input
                                wire:model="edit_mutu_code"
                                label="Mutu Beton *"
                                name="edit_mutu_code"
                                mono
                                placeholder="Contoh: K 300, FC 20"
                                required
                            />
                            <x-ui.input
                                wire:model="edit_volume_m3"
                                label="Volume (m³) *"
                                name="edit_volume_m3"
                                type="text"
                                suffix="m³"
                                mono
                                required
                            />
                        </div>
                    </div>

                    {{-- Section 3: Komposisi Batching Aktual --}}
                    <div class="pt-2 border-t border-zinc-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-2">
                            Komposisi Batching Aktual (kg)
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <x-ui.input
                                wire:model="edit_silo1_act"
                                label="Silo 1 (Semen)"
                                name="edit_silo1_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_silo2_act"
                                label="Silo 2 (Fly Ash)"
                                name="edit_silo2_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_pasir_palu_act"
                                label="Pasir Palu"
                                name="edit_pasir_palu_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_pasir_lokal_act"
                                label="Pasir Lokal"
                                name="edit_pasir_lokal_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_split_1_2_act"
                                label="Split 1/2"
                                name="edit_split_1_2_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_split_2_3_act"
                                label="Split 2/3"
                                name="edit_split_2_3_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_water_act"
                                label="Air (Water)"
                                name="edit_water_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                            <x-ui.input
                                wire:model="edit_admixture_act"
                                label="Admixture"
                                name="edit_admixture_act"
                                type="text"
                                suffix="kg"
                                mono
                            />
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 shrink-0">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            wire:click="closeEditModal"
                        >
                            Batal
                        </x-ui.button>
                        <x-ui.button
                            type="submit"
                            variant="primary"
                        >
                            Simpan Perubahan
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

