<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    SURVEYOR TABLET
                </span>
                <span class="text-xs text-zinc-500 font-mono">Lap. Batching Plant</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Form Opname Fisik Timbunan
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan pengukuran geometri timbunan material agregat di lapangan (Stock Pile & Bin).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Formula Protected: Zero Exposure
            </x-ui.badge>
        </div>
    </div>


    {{-- Tablet Touch-Friendly Opname Form --}}
    <x-ui.card
        title="Input Geometri Pengukuran"
        description="Pilih metode bentuk timbunan dan masukkan parameter dimensi lapangan secara presisi."
    >
        <form wire:submit.prevent="save" class="space-y-6">
            {{-- Bagian 1: Header Opname --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Tanggal Opname --}}
                <div>
                    <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1.5">
                        Tanggal Opname <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        wire:model="opname_date"
                        class="w-full h-12 px-3 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                        required
                    />
                    @error('opname_date')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Material Agregat --}}
                <div>
                    <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1.5">
                        Material Agregat / Pasir <span class="text-red-500">*</span>
                    </label>
                    <select
                        wire:model.live="material_id"
                        class="w-full h-12 px-3 rounded-md border border-zinc-300 text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                        required
                    >
                        <option value="">-- Pilih Material Agregat --</option>
                        @foreach ($materials as $mat)
                            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                        @endforeach
                    </select>
                    @error('material_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Bagian 2: Pilihan Bentuk Geometri (Limas Terpancung / Kerucut Terpancung) --}}
            <div>
                <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-2">
                    Metode Bentuk Timbunan (Geometri) <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all {{ $shape_type === 'limas_terpancung' ? 'border-zinc-900 bg-zinc-900/5 ring-1 ring-zinc-900' : 'border-zinc-200 hover:bg-zinc-50' }}">
                        <input
                            type="radio"
                            wire:model.live="shape_type"
                            value="limas_terpancung"
                            class="h-4 w-4 text-zinc-900 border-zinc-300 focus:ring-zinc-900"
                        />
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-zinc-900">Limas Terpancung</span>
                            <span class="text-xs text-zinc-500">Timbunan trapesium/persegi dengan bidang atas & bawah</span>
                        </div>
                    </label>

                    <label class="relative flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all {{ $shape_type === 'kerucut_terpancung' ? 'border-zinc-900 bg-zinc-900/5 ring-1 ring-zinc-900' : 'border-zinc-200 hover:bg-zinc-50' }}">
                        <input
                            type="radio"
                            wire:model.live="shape_type"
                            value="kerucut_terpancung"
                            class="h-4 w-4 text-zinc-900 border-zinc-300 focus:ring-zinc-900"
                        />
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-zinc-900">Kerucut Terpancung</span>
                            <span class="text-xs text-zinc-500">Timbunan kerucut lingkaran dengan jari-jari atas & bawah</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Bagian 3: Input Dimensi Geometri Dinamis (Touch-Friendly Tinggi h-12) --}}
            <div class="p-4 rounded-lg bg-zinc-50 border border-zinc-200 space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">
                        Dimensi Fisik Timbunan (Satuan Meter)
                    </h4>
                    <span class="text-xs text-zinc-500 font-mono">inputmode="decimal"</span>
                </div>

                {{-- Tinggi timbunan (t) - Berlaku untuk kedua bentuk --}}
                <div>
                    <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                        Tinggi Timbunan ($t$) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            inputmode="decimal"
                            wire:model="tinggi"
                            placeholder="0.00"
                            class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        />
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                            meter
                        </span>
                    </div>
                    @error('tinggi')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if ($shape_type === 'limas_terpancung')
                    {{-- Selector Mode: Dimensi Sisi (P × L) vs Luas Langsung (m²) --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-zinc-200">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-zinc-700">Metode Input:</span>
                            <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-0.5 text-xs font-medium shadow-2xs">
                                <button
                                    type="button"
                                    wire:click="$set('limas_input_mode', 'sisi')"
                                    class="px-2.5 py-1 rounded-md transition-all {{ $limas_input_mode === 'sisi' ? 'bg-zinc-900 font-bold text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                                >
                                    📐 Ukur Sisi (P &times; L)
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('limas_input_mode', 'luas')"
                                    class="px-2.5 py-1 rounded-md transition-all {{ $limas_input_mode === 'luas' ? 'bg-zinc-900 font-bold text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                                >
                                    📊 Luas Langsung (m²)
                                </button>
                            </div>
                        </div>
                        <span class="text-[11px] text-zinc-400 font-mono">
                            {{ $limas_input_mode === 'sisi' ? 'Cukup masukkan meteran panjang & lebar lapangan' : 'Gunakan jika luas sudah dihitung sebelumnya' }}
                        </span>
                    </div>

                    @if ($limas_input_mode === 'sisi')
                        {{-- Mode Sisi: Puncak (pa × la) dan Dasar (pb × lb) --}}
                        <div class="space-y-4">
                            {{-- 1. Bidang Atas (Puncak Timbunan) --}}
                            <div class="p-3.5 rounded-lg bg-white border border-zinc-200/90 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-800">
                                            Bidang Atas (Puncak Timbunan)
                                        </span>
                                    </div>
                                    @if ($la)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Luas ($L_a$): {{ number_format((float) $la, 2, ',', '.') }} m²
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                                            Panjang Atas ($p_a$) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                wire:model.live.debounce.250ms="pa"
                                                placeholder="0.00"
                                                class="w-full h-11 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                                required
                                            />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                                meter
                                            </span>
                                        </div>
                                        @error('pa')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                                            Lebar Atas ($l_a$) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                wire:model.live.debounce.250ms="la_side"
                                                placeholder="0.00"
                                                class="w-full h-11 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                                required
                                            />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                                meter
                                            </span>
                                        </div>
                                        @error('la_side')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Bidang Bawah (Dasar / Kaki Timbunan) --}}
                            <div class="p-3.5 rounded-lg bg-white border border-zinc-200/90 shadow-2xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-zinc-900"></span>
                                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-800">
                                            Bidang Bawah (Dasar / Kaki Timbunan)
                                        </span>
                                    </div>
                                    @if ($lb)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Luas ($L_b$): {{ number_format((float) $lb, 2, ',', '.') }} m²
                                        </span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                                            Panjang Bawah ($p_b$) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                wire:model.live.debounce.250ms="pb"
                                                placeholder="0.00"
                                                class="w-full h-11 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                                required
                                            />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                                meter
                                            </span>
                                        </div>
                                        @error('pb')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                                            Lebar Bawah ($l_b$) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                wire:model.live.debounce.250ms="lb_side"
                                                placeholder="0.00"
                                                class="w-full h-11 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                                required
                                            />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                                meter
                                            </span>
                                        </div>
                                        @error('lb_side')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Mode Luas Langsung: La dan Lb --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                                    Luas Bidang Atas ($L_a$) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model="la"
                                        placeholder="0.00"
                                        class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                        m²
                                    </span>
                                </div>
                                @error('la')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                                    Luas Bidang Bawah ($L_b$) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model="lb"
                                        placeholder="0.00"
                                        class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                        required
                                    />
                                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                        m²
                                    </span>
                                </div>
                                @error('lb')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Bidang Kerucut Terpancung: Ra dan Rb --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                                Jari-jari Bidang Atas ($R_a$) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    wire:model="ra"
                                    placeholder="0.00"
                                    class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                    required
                                />
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                    meter
                                </span>
                            </div>
                            @error('ra')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                                Jari-jari Bidang Bawah ($R_b$) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    wire:model="rb"
                                    placeholder="0.00"
                                    class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                                    required
                                />
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                                    meter
                                </span>
                            </div>
                            @error('rb')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                {{-- Stock Bin Hopper (Tambahan) --}}
                <div class="pt-2 border-t border-zinc-200">
                    <label class="block text-xs md:text-sm font-medium text-zinc-700 mb-1">
                        Sisa Material di Stock Bin / Hopper (Opsional)
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            inputmode="decimal"
                            wire:model="stock_bin"
                            placeholder="0.00 (jika ada)"
                            class="w-full h-12 px-3 pr-12 rounded-md border border-zinc-300 font-mono text-base md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                        />
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 font-mono text-xs">
                            {{ $selectedMaterialUnit ?? 'Kg' }}
                        </span>
                    </div>
                    @error('stock_bin')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Submit Action --}}
            <div class="pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full h-14 rounded-md bg-zinc-900 text-white font-medium text-base hover:bg-zinc-800 transition-colors flex items-center justify-center gap-2 shadow-xs cursor-pointer disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan & Kirim Data Opname ke Server
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menghitung & Mengirim ke Server...
                    </span>
                </button>
            </div>
        </form>
    </x-ui.card>

    {{-- Riwayat Pengukuran Opname --}}
    <x-ui.card
        title="Riwayat Pengukuran Stock Opname"
        description="Daftar penginputan data opname lapangan yang telah terkirim ke server."
    >
        @php
            $headers = ['Tanggal', 'Material', 'Bentuk Geometri', 'Dimensi Terinput', 'Status Sinkronisasi'];
            if (auth()->user()?->role === 'admin') {
                $headers[] = 'Hasil Hitung Server (Admin)';
            }
        @endphp

        {{-- Search Bar --}}
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari material atau bentuk geometri..."
                prefix="🔍"
                :clearable="true"
                :has-value="!empty($search)"
                loading-target="search"
            />
        </div>

        <x-ui.table :headers="$headers">
            @forelse ($opnames as $opname)
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Tanggal --}}
                    <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                        {{ $opname->opname_date ? $opname->opname_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Material --}}
                    <td class="px-4 py-3 font-semibold text-zinc-900">
                        <div>{{ $opname->material->name ?? '-' }}</div>
                        @if (!empty($opname->dimensions_json['pile_name']) && strtolower(trim($opname->dimensions_json['pile_name'])) !== strtolower(trim($opname->material?->name ?? '')))
                            <div class="text-[11px] font-normal text-zinc-500">
                                Timbunan: <span class="font-medium text-zinc-700">{{ $opname->dimensions_json['pile_name'] }}</span>
                            </div>
                        @endif
                    </td>

                    {{-- Bentuk --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge variant="zinc">
                            {{ str_replace('_', ' ', ucwords($opname->method, '_')) }}
                        </x-ui.badge>
                    </td>

                    {{-- Dimensi Input --}}
                    <td class="px-4 py-3 font-mono text-xs text-zinc-600">
                        @php
                            $d = $opname->dimensions_json ?? [];
                            $tinggi = $d['tinggi'] ?? $d['t'] ?? '-';
                        @endphp
                        <div>t: {{ $tinggi }} m</div>
                        @if ($opname->method === 'limas_terpancung')
                            @if (!empty($d['pa']) && !empty($d['la_side']))
                                <div class="text-[11px] text-zinc-600 font-medium">
                                    Atas: {{ $d['pa'] }}m × {{ $d['la_side'] }}m <span class="text-zinc-400">({{ $d['la'] ?? '-' }} m²)</span>
                                </div>
                                <div class="text-[11px] text-zinc-600 font-medium">
                                    Bawah: {{ $d['pb'] ?? '-' }}m × {{ $d['lb_side'] ?? '-' }}m <span class="text-zinc-400">({{ $d['lb'] ?? '-' }} m²)</span>
                                </div>
                            @else
                                <div class="text-[11px] text-zinc-500">
                                    La: {{ $d['la'] ?? '-' }} m², Lb: {{ $d['lb'] ?? '-' }} m²
                                </div>
                            @endif
                        @else
                            <div class="text-[11px] text-zinc-500">
                                Ra: {{ $d['ra'] ?? '-' }} m, Rb: {{ $d['rb'] ?? '-' }} m
                            </div>
                        @endif
                        @if (!empty($d['stock_bin']) && (float) $d['stock_bin'] > 0)
                            <div class="text-[11px] text-zinc-500">
                                Bin: {{ number_format((float) $d['stock_bin'], 2, ',', '.') }} Kg
                                @if (!empty($d['stock_bin_m3']))
                                    <span class="text-zinc-400">({{ $d['stock_bin_m3'] }} m³)</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    {{-- Status: Surveyor hanya melihat status "Tersimpan di Server" --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge variant="emerald" size="sm">
                            ✓ Tersimpan di Server (Menunggu Rekonsiliasi)
                        </x-ui.badge>
                    </td>

                    {{-- Khusus Admin: Kolom Hasil Hitung Server --}}
                    @if (auth()->user()?->role === 'admin')
                        <td class="px-4 py-3 last:pr-5 font-mono text-xs text-zinc-900 whitespace-nowrap">
                            <div>Vol: <span class="font-bold">{{ number_format((float) $opname->calculated_volume_m3, 4, ',', '.') }}</span> m³</div>
                            @if (($opname->material?->name ?? '') === 'Semen' || strtoupper($opname->material?->unit ?? '') === 'TON')
                                <div class="text-zinc-700">
                                    Berat: <span class="font-bold text-zinc-900">{{ number_format((float) $opname->calculated_weight_kg / 1000, 2, ',', '.') }}</span> Ton
                                    <span class="text-[11px] text-zinc-400 font-normal">({{ number_format((float) $opname->calculated_weight_kg, 2, ',', '.') }} Kg)</span>
                                </div>
                            @else
                                <div class="text-zinc-600">Berat: <span class="font-bold">{{ number_format((float) $opname->calculated_weight_kg, 2, ',', '.') }}</span> {{ $opname->material?->unit ?? 'Kg' }}</div>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada riwayat stock opname yang tercatat.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($opnames->hasPages() || isset($this->perPage) || $opnames->total() > 0)
            <div class="mt-4 pt-3 border-t border-zinc-100">
                {{ $opnames->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
