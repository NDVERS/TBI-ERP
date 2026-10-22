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
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-5 h-5 rounded-full bg-zinc-900 text-white font-mono text-[10px] font-bold flex items-center justify-center shrink-0">1</span>
                            <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">
                                Identitas Surat Jalan & Waktu Dispatch
                            </h3>
                        </div>
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
                    <div class="pt-5 border-t border-zinc-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-900 text-white font-mono text-[10px] font-bold flex items-center justify-center shrink-0">2</span>
                                <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">
                                    Data Customer, Mutu Beton & Armada
                                </h3>
                            </div>
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
                        <div class="p-3 rounded-lg border border-zinc-200/90 bg-zinc-50/50 mb-4 space-y-1.5">
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
                            <span class="text-[11px] text-zinc-500 block">
                                Memilih No. SO otomatis mengisi Nama Customer, Lokasi Proyek, Mutu Beton, dan memotong sisa kuota volume pemesanan saat docket disimpan.
                            </span>
                        </div>

                        <div class="space-y-4">
                            {{-- Baris 1: Customer (1 col) & Alamat (2 col) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

                                <div class="sm:col-span-2">
                                    <x-ui.input
                                        wire:model="address"
                                        label="Alamat Proyek / Lokasi Pengecoran"
                                        name="address"
                                        placeholder="OTOMATIS TERISI DARI CUSTOMER, DAPAT DISESUAIKAN..."
                                        class="uppercase"
                                    />
                                </div>
                            </div>

                            {{-- Baris 2: Mutu Beton, No. TM, Volume, Status Dispatch (Simetris 4 Kolom) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                                <div>
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
                                </div>

                                {{-- Volume (m³) --}}
                                <div>
                                    <x-ui.input
                                        wire:model.live.debounce.250ms="volume_m3"
                                        label="Volume Cor (m³)"
                                        name="volume_m3"
                                        type="text"
                                        placeholder="Contoh: 7.00"
                                        suffix="m³"
                                        mono
                                        required
                                    />
                                </div>

                                {{-- Status Dispatch Armada --}}
                                <div>
                                    <x-ui.select
                                        wire:model="delivery_status"
                                        label="Status Dispatch Armada"
                                        name="delivery_status"
                                        required
                                    >
                                        <option value="completed">✅ Selesai Bongkar</option>
                                        <option value="loading">🔄 Antri Muat (Plant)</option>
                                        <option value="in_transit">🚚 Dalam Perjalanan</option>
                                        <option value="on_site">🏗️ Di Lokasi Proyek</option>
                                    </x-ui.select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bagian 3: Komposisi Batching Aktual --}}
                    <div class="pt-5 border-t border-zinc-200">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-zinc-900 text-white font-mono text-[10px] font-bold flex items-center justify-center shrink-0">3</span>
                                <div>
                                    <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">
                                        Komposisi Penimbangan Batching Aktual
                                    </h3>
                                    <p class="text-[11px] text-zinc-500 font-mono">
                                        Otomatis memotong stok teoritis plant sesuai timbangan riil
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="autoFillFromJobMix"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded border border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-800 transition-colors shadow-2xs cursor-pointer"
                                    title="Hitung takaran agregat, semen, dan air otomatis dari resep mutu beton"
                                >
                                    <span>📋 Hitung dari Resep Job Mix</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="toggleMoistureCalculator"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded border transition-colors shadow-2xs cursor-pointer {{ $showMoistureCalculator ? 'border-sky-300 bg-sky-50 text-sky-900' : 'border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-800' }}"
                                    title="Koreksi kadar air pasir dan split agar w/c ratio beton tetap presisi"
                                >
                                    <span>💧 Koreksi Kadar Air (Moisture)</span>
                                </button>
                            </div>
                        </div>

                        {{-- Moisture Correction Calculator Widget --}}
                        @if ($showMoistureCalculator)
                            <div class="mb-4 p-4 rounded-lg bg-sky-50/70 border border-sky-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">💧</span>
                                        <div>
                                            <h4 class="text-xs font-bold text-sky-950 uppercase tracking-wider">Kalkulator Koreksi Kadar Air Agregat (Moisture Correction)</h4>
                                            <p class="text-[11px] text-sky-800">Kompensasikan kadar air pada pasir dan split basah: air mixer otomatis dikurangi dan timbangan agregat basah disesuaikan.</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="toggleMoistureCalculator"
                                        class="text-xs text-sky-700 hover:text-sky-900 font-semibold cursor-pointer"
                                    >
                                        Tutup ✕
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <x-ui.input
                                        wire:model="moisture_pasir_palu"
                                        label="Kadar Air Pasir Palu (%)"
                                        name="moisture_pasir_palu"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="20"
                                        placeholder="0.0"
                                        suffix="%"
                                        mono
                                    />
                                    <x-ui.input
                                        wire:model="moisture_pasir_lokal"
                                        label="Kadar Air Pasir Lokal (%)"
                                        name="moisture_pasir_lokal"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="20"
                                        placeholder="0.0"
                                        suffix="%"
                                        mono
                                    />
                                    <x-ui.input
                                        wire:model="moisture_split"
                                        label="Kadar Air Batu Split (%)"
                                        name="moisture_split"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="10"
                                        placeholder="0.0"
                                        suffix="%"
                                        mono
                                    />
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-sky-200/60">
                                    <div class="text-[11px] text-sky-900 font-medium">
                                        @if ($moisture_correction_summary)
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-800">
                                                <span>✓</span> {{ $moisture_correction_summary }}
                                            </span>
                                        @else
                                            <span>*Isi % kadar air di atas, lalu klik tombol terapkan.</span>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="applyMoistureCorrection"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded bg-sky-900 text-white hover:bg-sky-800 transition-colors cursor-pointer shadow-2xs whitespace-nowrap self-end sm:self-auto"
                                    >
                                        <span>💧 Terapkan Koreksi Kadar Air</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Grid 4 Kolom x 2 Baris: 7 Input Material + 1 Slot Card Ringkasan Densitas Simetris --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 p-4 rounded-xl bg-zinc-50/80 border border-zinc-200">
                            {{-- 1. Semen --}}
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

                            {{-- 2. Pasir Palu --}}
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

                            {{-- 3. Pasir Lokal --}}
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

                            {{-- 4. Air --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="water_act"
                                label="Air Bersih Aktual"
                                name="water_act"
                                type="text"
                                placeholder="0.00"
                                suffix="Liter"
                                mono
                                required
                            />

                            {{-- 5. Batu 1/2 --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="split_1_2_act"
                                label="Batu Split 1/2 Aktual"
                                name="split_1_2_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- 6. Batu 2/3 --}}
                            <x-ui.input
                                wire:model.live.debounce.250ms="split_2_3_act"
                                label="Batu Split 2/3 Aktual"
                                name="split_2_3_act"
                                type="text"
                                placeholder="0.00"
                                suffix="kg"
                                mono
                                required
                            />

                            {{-- 7. SBTJM2 (Admixture) --}}
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

                            {{-- 8. Real-Time Mini Metrics Card (Menutup Slot ke-8 secara Sempurna & Fungsional) --}}
                            <div class="flex flex-col justify-between p-3 rounded-lg border border-zinc-200 bg-white shadow-2xs min-h-18">
                                <div class="flex items-center justify-between pb-1.5 border-b border-zinc-100">
                                    <span class="text-[10px] font-mono font-bold text-zinc-500 uppercase tracking-tight">Kalkulasi Batch</span>
                                    @if ($this->totalBatchWeight > 0 && $this->batchDensity >= 2200 && $this->batchDensity <= 2550)
                                        <x-ui.badge variant="emerald" size="sm" dot>Normal</x-ui.badge>
                                    @elseif ($this->totalBatchWeight > 0)
                                        <x-ui.badge variant="amber" size="sm" dot>Cek Timbangan</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="zinc" size="sm">Siap</x-ui.badge>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-2 my-1 text-center font-mono">
                                    <div class="p-1 rounded bg-zinc-50 border border-zinc-100">
                                        <div class="text-[10px] text-zinc-400">Total Berat</div>
                                        <div class="font-bold text-zinc-900 text-xs tabular-nums">{{ number_format($this->totalBatchWeight, 0, ',', '.') }} <span class="text-[9px] font-normal text-zinc-500">kg</span></div>
                                    </div>
                                    <div class="p-1 rounded bg-zinc-50 border border-zinc-100">
                                        <div class="text-[10px] text-zinc-400">Densitas</div>
                                        <div class="font-bold text-zinc-900 text-xs tabular-nums">{{ number_format($this->batchDensity, 1, ',', '.') }} <span class="text-[9px] font-normal text-zinc-500">kg/m³</span></div>
                                    </div>
                                </div>

                                <div class="text-[10px] text-zinc-400 font-mono flex items-center justify-between pt-1 border-t border-zinc-100">
                                    <span>Konversi Tonase:</span>
                                    <span class="font-bold text-zinc-700">{{ number_format($this->totalBatchWeight / 1000, 2, ',', '.') }} Ton</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-5 border-t border-zinc-200">
                        <div class="flex items-center gap-2">
                            <x-ui.unsaved-changes-guard />
                            <span class="text-[11px] text-zinc-500 hidden sm:inline">
                                Pastikan takaran aktual sesuai nota timbangan plant sebelum menerbitkan tiket.
                            </span>
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
                                class="px-5 py-2 text-sm font-semibold shadow-xs"
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
                    description="Ketersediaan material terkini di plant. Berkurang otomatis saat tiket disimpan."
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
                                $stok = (float) $mStock['stok_teoritis'];
                                $unit = strtolower($mStock['unit'] ?? 'kg');
                                $isDeficit = $stok < 0;
                                // Ambang batas realistis per jenis satuan: liter (BBM/Kimia) vs kg (semen/agregat)
                                $isLowStock = ! $isDeficit && ($unit === 'liter' ? $stok < 1000 : $stok < 50000);
                            @endphp
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <div class="flex items-center gap-1.5 font-semibold text-zinc-900">
                                        <span>{{ $mStock['material_name'] }}</span>
                                        @if ($isDeficit)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-red-50 text-red-700 border border-red-200">
                                                ⚠️ Defisit
                                            </span>
                                        @elseif ($isLowStock)
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
                                    <div class="font-bold font-mono tabular-nums text-sm {{ $isDeficit ? 'text-red-600' : ($isLowStock ? 'text-amber-600' : 'text-zinc-900') }}">
                                        {{ number_format($stok, 2, ',', '.') }}
                                    </div>
                                    <span class="text-[10px] text-zinc-500 font-mono">{{ $mStock['unit'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                {{-- Widget Ringkasan KPI Produksi Hari Ini --}}
                @php
                    $kpi = $this->dailyKpi;
                @endphp
                <x-ui.card
                    title="Ringkasan Produksi Hari Ini"
                    description="{{ $kpi['date_formatted'] }} (Shift Berjalan)"
                >
                    <x-slot:action>
                        <x-ui.badge variant="zinc" size="sm" class="font-mono">
                            {{ $kpi['total_ritase'] }} Ritase
                        </x-ui.badge>
                    </x-slot:action>

                    <div class="space-y-3.5">
                        {{-- 2 Metrik Utama --}}
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="p-3 rounded-lg bg-zinc-50 border border-zinc-200/80">
                                <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-tight block">
                                    Volume Cor
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-bold font-mono tracking-tight text-zinc-900 tabular-nums">
                                        {{ number_format($kpi['total_volume'], 1, ',', '.') }}
                                    </span>
                                    <span class="text-xs font-mono text-zinc-500">m³</span>
                                </div>
                                <span class="text-[10px] text-zinc-400 font-mono mt-0.5 block">
                                    ~{{ number_format($kpi['avg_volume'], 1, ',', '.') }} m³/rit
                                </span>
                            </div>

                            <div class="p-3 rounded-lg bg-zinc-50 border border-zinc-200/80">
                                <span class="text-[11px] font-medium text-zinc-500 uppercase tracking-tight block">
                                    Armada TM
                                </span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-bold font-mono tracking-tight text-zinc-900 tabular-nums">
                                        {{ $kpi['active_trucks_count'] }}
                                    </span>
                                    <span class="text-xs font-mono text-zinc-500">Unit</span>
                                </div>
                                <span class="text-[10px] text-zinc-400 font-mono mt-0.5 block">
                                    Beroperasi aktif
                                </span>
                            </div>
                        </div>

                        {{-- Rincian Tambahan --}}
                        <div class="space-y-2 pt-2 border-t border-zinc-100 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500">Mutu Terbanyak:</span>
                                @if ($kpi['top_mutu_code'] !== '-')
                                    <span class="font-mono font-semibold text-zinc-900">
                                        {{ $kpi['top_mutu_code'] }}
                                        <span class="text-zinc-400 font-normal">({{ $kpi['top_mutu_count'] }} rit)</span>
                                    </span>
                                @else
                                    <span class="text-zinc-400 font-mono">-</span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500">Akumulasi Bulan Ini:</span>
                                <span class="font-mono font-bold text-zinc-900">
                                    {{ number_format($kpi['mtd_volume'], 1, ',', '.') }} m³
                                    <span class="text-zinc-400 font-normal">({{ $kpi['mtd_ritase'] }} rit)</span>
                                </span>
                            </div>
                        </div>
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
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('production-dockets.report-excel', ['month' => now()->month, 'year' => now()->year]) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Export Rekap Bulanan Produksi ke Excel"
                >
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Ekspor Excel</span>
                </a>

                <a
                    href="{{ route('production-dockets.report-pdf', ['month' => now()->month, 'year' => now()->year]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                    title="Cetak Rekap Bulanan Produksi (PDF)"
                >
                    <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </x-slot:action>

        {{-- Toolbar Status Ritase Kanban & Pencarian/Periode --}}
        @php
            $counts = $this->deliveryStatusCounts;
        @endphp
        <div class="mb-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="setFilterDeliveryStatus('all')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer {{ $filterDeliveryStatus === 'all' ? 'bg-zinc-900 text-white border-zinc-900 shadow-2xs' : 'bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50' }}"
                >
                    <span>Semua Ritase</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $filterDeliveryStatus === 'all' ? 'bg-zinc-700 text-white' : 'bg-zinc-100 text-zinc-700' }}">{{ $counts['all'] }}</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilterDeliveryStatus('loading')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer {{ $filterDeliveryStatus === 'loading' ? 'bg-amber-600 text-white border-amber-600 shadow-2xs' : 'bg-amber-50/70 text-amber-900 border-amber-200 hover:bg-amber-100/70' }}"
                >
                    <span>🔄 Antri Muat (Plant)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $filterDeliveryStatus === 'loading' ? 'bg-amber-800 text-white' : 'bg-amber-200 text-amber-900' }}">{{ $counts['loading'] }}</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilterDeliveryStatus('in_transit')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer {{ $filterDeliveryStatus === 'in_transit' ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-blue-50/70 text-blue-900 border-blue-200 hover:bg-blue-100/70' }}"
                >
                    <span>🚚 Dalam Perjalanan</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $filterDeliveryStatus === 'in_transit' ? 'bg-blue-800 text-white' : 'bg-blue-200 text-blue-900' }}">{{ $counts['in_transit'] }}</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilterDeliveryStatus('on_site')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer {{ $filterDeliveryStatus === 'on_site' ? 'bg-purple-600 text-white border-purple-600 shadow-2xs' : 'bg-purple-50/70 text-purple-900 border-purple-200 hover:bg-purple-100/70' }}"
                >
                    <span>🏗️ Di Lokasi Proyek</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $filterDeliveryStatus === 'on_site' ? 'bg-purple-800 text-white' : 'bg-purple-200 text-purple-900' }}">{{ $counts['on_site'] }}</span>
                </button>

                <button
                    type="button"
                    wire:click="setFilterDeliveryStatus('completed')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer {{ $filterDeliveryStatus === 'completed' ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs' : 'bg-emerald-50/70 text-emerald-900 border-emerald-200 hover:bg-emerald-100/70' }}"
                >
                    <span>✅ Selesai Bongkar</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ $filterDeliveryStatus === 'completed' ? 'bg-emerald-800 text-white' : 'bg-emerald-200 text-emerald-900' }}">{{ $counts['completed'] }}</span>
                </button>
            </div>

            {{-- Toolbar Pencarian & Filter Periode --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-zinc-100">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Quick Date Range Presets --}}
                    <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-0.5 text-xs font-medium">
                        <button
                            type="button"
                            wire:click="setDateRange('all')"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer {{ $filterDateRange === 'all' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            wire:click="setDateRange('today')"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer {{ $filterDateRange === 'today' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            Hari Ini
                        </button>
                        <button
                            type="button"
                            wire:click="setDateRange('yesterday')"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer {{ $filterDateRange === 'yesterday' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            Kemarin
                        </button>
                        <button
                            type="button"
                            wire:click="setDateRange('this_week')"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer {{ $filterDateRange === 'this_week' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            Minggu Ini
                        </button>
                        <button
                            type="button"
                            wire:click="setDateRange('this_month')"
                            class="px-2.5 py-1 rounded-md transition-all cursor-pointer {{ $filterDateRange === 'this_month' ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            Bulan Ini
                        </button>
                    </div>

                    <x-ui.table-density-toggle />
                </div>

                <div class="w-full sm:w-64">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari no. docket, customer, TM..."
                        prefix="🔍"
                        size="sm"
                    />
                </div>
            </div>
        </div>


        @php
            $headers = [
                ['label' => 'No', 'class' => 'w-14 text-center first:pl-3 first:pr-3 whitespace-nowrap'],
                ['label' => 'No. Docket', 'class' => 'whitespace-nowrap'],
                ['label' => 'Waktu', 'class' => 'whitespace-nowrap hidden sm:table-cell'],
                ['label' => 'Customer & Proyek', 'class' => ''],
                ['label' => 'Mutu Beton', 'class' => 'whitespace-nowrap'],
                ['label' => 'No. TM', 'class' => 'whitespace-nowrap'],
                ['label' => 'Vol (m³)', 'class' => 'text-right whitespace-nowrap'],
                ['label' => 'Kumulatif (m³)', 'class' => 'text-right whitespace-nowrap hidden md:table-cell'],
                ['label' => 'Status Ritase', 'class' => 'whitespace-nowrap'],
                ['label' => 'Operator', 'class' => 'hidden lg:table-cell whitespace-nowrap'],
                ['label' => 'Aksi', 'class' => 'text-right whitespace-nowrap'],
            ];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($dockets as $index => $d)
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- No Urut --}}
                    <td class="px-2 py-3 text-center first:pl-3 first:pr-3 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded bg-zinc-100 border border-zinc-200 text-zinc-600 font-mono text-xs font-semibold tabular-nums">
                            {{ $dockets->firstItem() + $index }}
                        </span>
                    </td>

                    {{-- No Docket (Klik untuk Melihat Rincian Material & Dokumen) --}}
                    <td class="px-4 py-3 font-mono font-bold text-zinc-900 text-xs whitespace-nowrap">
                        <button
                            type="button"
                            wire:click="openDetailModal({{ $d->id }})"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 hover:bg-zinc-900 hover:text-white border border-zinc-200 text-zinc-900 font-mono font-bold text-xs transition-all cursor-pointer group shadow-2xs"
                            title="Klik untuk melihat Rincian & Timbangan Material Aktual"
                        >
                            <span>{{ $d->docket_no }}</span>
                            <svg class="w-3.5 h-3.5 text-zinc-400 group-hover:text-zinc-200 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </td>

                    {{-- Waktu --}}
                    <td class="px-4 py-3 text-xs font-mono tabular-nums text-zinc-600 hidden sm:table-cell whitespace-nowrap">
                        <div>{{ $d->production_date ? \Carbon\Carbon::parse($d->production_date)->format('d/m/Y') : '-' }}</div>
                        <div class="text-[11px] text-zinc-400 font-mono">{{ substr($d->time_load, 0, 5) }} WITA</div>
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
                    <td class="px-4 py-3 text-xs font-mono whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200 font-bold text-zinc-800">
                            {{ $d->mutu_code }}
                        </span>
                    </td>

                    {{-- No. TM (Truck Mixer) --}}
                    <td class="px-4 py-3 text-xs font-mono text-zinc-700 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-50 border border-zinc-200 font-bold text-zinc-800 whitespace-nowrap">
                            {{ $d->truck_no }}
                        </span>
                    </td>

                    {{-- Volume --}}
                    <td class="px-4 py-3 text-xs font-mono font-bold tabular-nums text-right text-zinc-900 whitespace-nowrap">
                        {{ number_format((float) $d->volume_m3, 2, ',', '.') }}
                    </td>

                    {{-- Kumulatif --}}
                    <td class="px-4 py-3 text-xs font-mono tabular-nums text-right text-zinc-600 hidden md:table-cell">
                        {{ number_format((float) $d->daily_cumulative_m3, 2, ',', '.') }}
                    </td>

                    {{-- Status Ritase Dispatch --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        @php
                            $statusInfo = $d->delivery_status_info;
                        @endphp
                        <div class="inline-flex items-center gap-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold
                                {{ $d->delivery_status === 'loading' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                                {{ $d->delivery_status === 'in_transit' ? 'bg-blue-100 text-blue-800 border border-blue-200' : '' }}
                                {{ $d->delivery_status === 'on_site' ? 'bg-purple-100 text-purple-800 border border-purple-200' : '' }}
                                {{ $d->delivery_status === 'completed' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' }}
                            ">
                                <span>{{ $statusInfo['icon'] }}</span>
                                <span>{{ $statusInfo['short'] }}</span>
                            </span>

                            {{-- Tombol Cepat Majukan Status --}}
                            @if ($d->delivery_status === 'loading')
                                <button
                                    type="button"
                                    wire:click="updateDeliveryStatus({{ $d->id }}, 'in_transit')"
                                    class="p-1 rounded text-zinc-400 hover:text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer text-xs"
                                    title="Tandai: Jalan Menuju Proyek"
                                >
                                    ➔ 🚚
                                </button>
                            @elseif ($d->delivery_status === 'in_transit')
                                <button
                                    type="button"
                                    wire:click="updateDeliveryStatus({{ $d->id }}, 'on_site')"
                                    class="p-1 rounded text-zinc-400 hover:text-purple-600 hover:bg-purple-50 transition-colors cursor-pointer text-xs"
                                    title="Tandai: Tiba di Lokasi Proyek"
                                >
                                    ➔ 🏗️
                                </button>
                            @elseif ($d->delivery_status === 'on_site')
                                <button
                                    type="button"
                                    wire:click="updateDeliveryStatus({{ $d->id }}, 'completed')"
                                    class="p-1 rounded text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer text-xs"
                                    title="Tandai: Selesai Bongkar"
                                >
                                    ➔ ✅
                                </button>
                            @endif
                        </div>
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
                            {{-- Tombol Rincian (Dapat Diakses Seluruh Role) --}}
                            <button
                                type="button"
                                wire:click="openDetailModal({{ $d->id }})"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900 transition-colors shadow-2xs cursor-pointer"
                                title="Lihat Rincian Timbangan & Dokumen Surat Jalan"
                            >
                                <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Rincian</span>
                            </button>

                            @if (auth()->user()?->role === 'admin')
                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $d->id }})"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900 transition-colors shadow-2xs cursor-pointer"
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
                            <div class="sm:col-span-2">
                                <x-ui.select
                                    wire:model="edit_delivery_status"
                                    label="Status Ritase Dispatch *"
                                    name="edit_delivery_status"
                                    required
                                >
                                    <option value="completed">✅ Selesai Bongkar</option>
                                    <option value="loading">🔄 Antri Muat (Plant)</option>
                                    <option value="in_transit">🚚 Dalam Perjalanan</option>
                                    <option value="on_site">🏗️ Di Lokasi Proyek</option>
                                </x-ui.select>
                            </div>
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

    {{-- Modal Rincian Surat Jalan & Penimbangan Batching Aktual --}}
    @if ($showDetailModal && $selectedDocket)
        <div
            @keydown.escape.window="$wire.closeDetailModal()"
            wire:click.self="closeDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-2xl max-h-[92vh] flex flex-col rounded-xl border border-zinc-200 bg-white shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-100 bg-zinc-50/80 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-zinc-900 text-white shadow-2xs">
                            {{ $selectedDocket->docket_no }}
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">
                                Rincian Surat Jalan & Timbangan Aktual
                            </h3>
                            <p class="text-[11px] text-zinc-500 font-mono">
                                Produksi: {{ $selectedDocket->production_date ? \Carbon\Carbon::parse($selectedDocket->production_date)->translatedFormat('d F Y') : '-' }} • {{ substr($selectedDocket->time_load, 0, 5) }} WITA
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closeDetailModal"
                        class="p-1 rounded-md text-zinc-400 hover:text-zinc-700 hover:bg-zinc-200/70 transition-colors cursor-pointer"
                        title="Tutup Modal"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body (Scrollable) --}}
                <div class="p-5 overflow-y-auto space-y-4 text-xs">
                    {{-- 1. Info Pengiriman & Proyek --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 rounded-lg border border-zinc-200 bg-zinc-50/60 font-mono">
                        <div>
                            <span class="text-[10px] text-zinc-500 block uppercase">Customer</span>
                            <span class="font-bold text-zinc-900 text-xs font-sans">{{ $selectedDocket->customer_name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-zinc-500 block uppercase">Mutu Beton</span>
                            <span class="font-bold text-zinc-900 text-xs">{{ $selectedDocket->mutu_code }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-zinc-500 block uppercase">Volume Cor</span>
                            <span class="font-bold text-emerald-700 text-sm">{{ number_format((float) $selectedDocket->volume_m3, 2, ',', '.') }} m³</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-zinc-500 block uppercase">Truk Mixer</span>
                            <span class="font-bold text-zinc-900 text-xs">{{ $selectedDocket->truck_no }}</span>
                        </div>
                    </div>

                    @if ($selectedDocket->address)
                        <div class="px-3 py-2 rounded-lg border border-zinc-200 bg-white text-zinc-700">
                            <span class="text-[10px] font-mono text-zinc-400 block uppercase">Lokasi / Alamat Pengecoran:</span>
                            <span class="text-xs font-medium">{{ $selectedDocket->address }}</span>
                        </div>
                    @endif

                    {{-- 2. Rincian Penimbangan Material Batching --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-tight flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <span>Realisasi Penimbangan Material Aktual</span>
                            </h4>
                            <span class="text-[10px] font-mono text-zinc-500">Timbangan Plant</span>
                        </div>

                        @php
                            $semenTotal = (float) $selectedDocket->silo1_act + (float) $selectedDocket->silo2_act;
                            $pasirPalu = (float) $selectedDocket->pasir_palu_act;
                            $pasirLokal = (float) $selectedDocket->pasir_lokal_act;
                            $split12 = (float) $selectedDocket->split_1_2_act;
                            $split23 = (float) $selectedDocket->split_2_3_act;
                            $air = (float) $selectedDocket->water_act;
                            $admixture = (float) $selectedDocket->admixture_act;

                            $totalWeightKg = $semenTotal + $pasirPalu + $pasirLokal + $split12 + $split23 + $air + $admixture;
                            $vol = (float) $selectedDocket->volume_m3 > 0 ? (float) $selectedDocket->volume_m3 : 1;
                            $density = $totalWeightKg / $vol;
                        @endphp

                        <div class="rounded-lg border border-zinc-200 overflow-hidden shadow-2xs">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-zinc-100 text-zinc-700 font-mono text-[11px] border-b border-zinc-200">
                                        <th class="px-3 py-2 text-left">Komponen Material</th>
                                        <th class="px-3 py-2 text-right">Berat / Volume Aktual</th>
                                        <th class="px-3 py-2 text-center">Satuan</th>
                                        <th class="px-3 py-2 text-right">Proporsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white font-mono">
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Semen Portland / OPC</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($semenTotal, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($semenTotal / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Pasir Palu (Agregat Halus)</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($pasirPalu, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($pasirPalu / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Pasir Lokal</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($pasirLokal, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($pasirLokal / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Batu Split 1/2 (Batu Kecil)</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($split12, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($split12 / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Batu Split 2/3 (Batu Besar)</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($split23, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($split23 / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Air Bersih (Water)</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($air, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Liter</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($air / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-zinc-900">Additive Kimia (SBTJM2)</td>
                                        <td class="px-3 py-2 text-right font-bold text-zinc-900">{{ number_format($admixture, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-500">Liter</td>
                                        <td class="px-3 py-2 text-right text-zinc-500">{{ $totalWeightKg > 0 ? number_format($admixture / $totalWeightKg * 100, 1) : 0 }}%</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-zinc-100/80 font-bold border-t border-zinc-300">
                                        <td class="px-3 py-2 font-sans text-zinc-900">TOTAL TIMBANGAN CAMPURAN</td>
                                        <td class="px-3 py-2 text-right text-zinc-900">{{ number_format($totalWeightKg, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-center text-zinc-600">Kg</td>
                                        <td class="px-3 py-2 text-right text-zinc-600">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Card Densitas & Status Ritase --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-lg border border-zinc-200 bg-zinc-50/70 font-mono">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 text-xs font-sans">Densitas Campuran:</span>
                            <span class="font-bold text-zinc-900 text-xs">{{ number_format($density, 1, ',', '.') }} kg/m³</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 text-xs font-sans">Status Pengiriman:</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold
                                {{ $selectedDocket->delivery_status === 'loading' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $selectedDocket->delivery_status === 'in_transit' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $selectedDocket->delivery_status === 'on_site' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $selectedDocket->delivery_status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                            ">
                                {{ $selectedDocket->delivery_status_info['icon'] }} {{ $selectedDocket->delivery_status_info['label'] }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-between px-5 py-3 border-t border-zinc-100 bg-zinc-50 shrink-0">
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('production-dockets.pdf', $selectedDocket) }}"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-300 bg-white hover:bg-zinc-100 text-zinc-800 text-xs font-semibold shadow-2xs transition-colors"
                        >
                            <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak PDF Surat Jalan</span>
                        </a>

                        @php
                            $modalWaText = "*SURAT JALAN BETON COR - TBI ERP*\n"
                                . "No. Docket: {$selectedDocket->docket_no}\n"
                                . "Tanggal: " . ($selectedDocket->production_date ? \Carbon\Carbon::parse($selectedDocket->production_date)->format('d/m/Y') : '-') . "\n"
                                . "Customer: {$selectedDocket->customer_name}\n"
                                . "Mutu Beton: {$selectedDocket->mutu_code}\n"
                                . "Volume: " . number_format((float) $selectedDocket->volume_m3, 2, ',', '.') . " m³\n"
                                . "TM: {$selectedDocket->truck_no}\n"
                                . "Total Timbangan: " . number_format($totalWeightKg, 0, ',', '.') . " kg";
                        @endphp
                        <x-ui.whatsapp-button :message="$modalWaText" label="Kirim WA" />
                    </div>

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeDetailModal"
                    >
                        Tutup
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>

