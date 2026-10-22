<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    OPERASIONAL PLANT
                </span>
                <span class="text-xs text-zinc-500 font-mono">Fleet & Equipment Maintenance System</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Armada & Alat Berat
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Monitoring jam kerja (Hour Meter), jarak tempuh (KM), konsumsi bahan bakar solar, serta jadwal servis berkala 11 Unit TM dan Wheel Loader.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="emerald" size="md" class="font-mono">
                11 TM + 1 Wheel Loader
            </x-ui.badge>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Ringkasan Status KPI Armada --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        {{-- Total Unit --}}
        <div class="p-4 rounded-xl bg-white border border-zinc-200 shadow-2xs">
            <span class="text-xs font-medium text-zinc-500">Total Unit Terdaftar</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-zinc-900">{{ $summaryCounts['total'] }}</span>
                <span class="text-xs text-zinc-400 font-mono">Unit</span>
            </div>
            <div class="text-[11px] text-zinc-400 font-mono mt-1">
                11 TM • 1 Loader • 1 Lain-lain
            </div>
        </div>

        {{-- Siap Operasi --}}
        <div class="p-4 rounded-xl bg-white border border-emerald-200 shadow-2xs">
            <span class="text-xs font-medium text-emerald-700">Kondisi Prima (Siap Operasi)</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-emerald-600">{{ $summaryCounts['ready'] }}</span>
                <span class="text-xs text-emerald-500 font-mono">Unit</span>
            </div>
            <div class="text-[11px] text-emerald-600 font-mono mt-1">
                Sisa HM > 50 jam
            </div>
        </div>

        {{-- Mendekati Servis --}}
        <div class="p-4 rounded-xl bg-white border border-amber-200 shadow-2xs">
            <span class="text-xs font-medium text-amber-700">Mendekati Servis</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-amber-600">{{ $summaryCounts['approaching'] }}</span>
                <span class="text-xs text-amber-500 font-mono">Unit</span>
            </div>
            <div class="text-[11px] text-amber-600 font-mono mt-1">
                Sisa HM &le; 50 jam
            </div>
        </div>

        {{-- Wajib Servis --}}
        <div class="p-4 rounded-xl bg-white border border-red-200 shadow-2xs">
            <span class="text-xs font-medium text-red-700">Wajib Servis (Overdue)</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-red-600">{{ $summaryCounts['urgent'] }}</span>
                <span class="text-xs text-red-500 font-mono">Unit</span>
            </div>
            <div class="text-[11px] text-red-600 font-mono mt-1">
                Harus segera ke bengkel
            </div>
        </div>
    </div>

    {{-- Main Section: Form Input Log Harian (Kiri) & Tab Monitoring Unit (Kanan) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Form Input Cepat Log Harian (Zero Mental Math) (lg:col-span-5) --}}
        <div class="lg:col-span-5 space-y-4">
            <x-ui.card
                title="Input Log Harian HM, KM & Solar"
                description="Operator cukup input angka meteran fisik saat ini. Sistem otomatis menghitung jam kerja dan sisa jam servis."
            >
                <form wire:submit.prevent="saveDailyLog" class="space-y-4">
                    {{-- 1. Pilih Unit Armada --}}
                    <div>
                        <x-ui.select
                            wire:model.live="selected_fleet_id"
                            label="Pilih Unit Armada / Alat"
                            name="selected_fleet_id"
                            required
                        >
                            <optgroup label="TRUCK MIXER (11 UNIT)">
                                @foreach ($tmFleets as $tm)
                                    <option value="{{ $tm->id }}">
                                        {{ $tm->unit_code }} - {{ $tm->name }} (Sisa {{ number_format($tm->remaining_hm, 0) }} HM)
                                    </option>
                                @endforeach
                            </optgroup>
                            @if ($loaderFleet)
                                <optgroup label="ALAT BERAT STOCKPILE">
                                    <option value="{{ $loaderFleet->id }}">
                                        {{ $loaderFleet->unit_code }} (Bucket 1,8)
                                    </option>
                                </optgroup>
                            @endif
                            @if ($othersFleet)
                                <optgroup label="LAIN-LAIN / OPERASIONAL">
                                    <option value="{{ $othersFleet->id }}">
                                        {{ $othersFleet->unit_code }}
                                    </option>
                                </optgroup>
                            @endif
                        </x-ui.select>
                    </div>

                    {{-- Status Banner Unit Terpilih --}}
                    @if ($this->selectedFleet)
                        @php
                            $f = $this->selectedFleet;
                            $statusInfo = $f->service_status_label;
                        @endphp
                        <div class="p-3.5 rounded-lg border {{ $f->is_service_due ? 'bg-red-50/70 border-red-200' : ($f->remaining_hm <= 50 ? 'bg-amber-50/70 border-amber-200' : 'bg-zinc-50 border-zinc-200') }} space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-zinc-900 text-sm font-mono">{{ $f->unit_code }}</span>
                                <x-ui.badge :variant="$statusInfo['variant']" size="sm">
                                    {{ $statusInfo['badge'] }}
                                </x-ui.badge>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                                <div class="p-1.5 rounded bg-white border border-zinc-200/80">
                                    <span class="text-[10px] text-zinc-400 block">HM Terakhir:</span>
                                    <span class="font-bold text-zinc-900">{{ number_format($f->current_hm, 2, ',', '.') }}</span> HM
                                </div>
                                <div class="p-1.5 rounded bg-white border border-zinc-200/80">
                                    <span class="text-[10px] text-zinc-400 block">Sisa Menuju Servis:</span>
                                    <span class="font-bold {{ $f->remaining_hm <= 50 ? 'text-amber-700' : 'text-zinc-900' }}">
                                        {{ number_format($f->remaining_hm, 1, ',', '.') }} Jam
                                    </span>
                                </div>
                            </div>

                            @if ($f->type === 'wheel_loader')
                                <div class="text-[11px] text-zinc-600 bg-white p-2 rounded border border-zinc-200/80 flex items-center justify-between">
                                    <span>Batas Kalender (1 Bulan):</span>
                                    <span class="font-bold font-mono {{ ($f->remaining_days ?? 30) <= 5 ? 'text-amber-700' : 'text-zinc-800' }}">
                                        {{ $f->remaining_days !== null ? ($f->remaining_days > 0 ? "Sisa {$f->remaining_days} Hari" : 'Jatuh Tempo') : '-' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Tanggal Log --}}
                    <x-ui.input
                        wire:model="log_date"
                        label="Tanggal Pencatatan"
                        name="log_date"
                        type="date"
                        required
                    />

                    {{-- Input Hour Meter (HM) --}}
                    <div class="p-3 rounded-lg bg-zinc-50/70 border border-zinc-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900 uppercase tracking-tight">Hour Meter (HM)</span>
                            @if ($this->calculatedTotalHm > 0)
                                <span class="text-[11px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    +{{ number_format($this->calculatedTotalHm, 2, ',', '.') }} Jam Operasi
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 mb-1">HM Kemarin (Awal)</label>
                                <input
                                    type="text"
                                    value="{{ number_format((float) $this->start_hm, 2, ',', '.') }}"
                                    disabled
                                    class="w-full h-10 px-3 rounded-md border border-zinc-200 bg-zinc-100 text-zinc-500 font-mono text-sm cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-zinc-900 mb-1">HM Baru (Meteran Sekarang) <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    wire:model.live.debounce.300ms="end_hm"
                                    placeholder="Contoh: {{ (float) $this->start_hm + 8 }}"
                                    class="w-full h-10 px-3 rounded-md border {{ $this->hmTypoWarning ? 'border-amber-400 bg-amber-50/20' : ($errors->has('end_hm') ? 'border-red-400 focus:ring-red-500' : 'border-zinc-300 focus:ring-zinc-900') }} font-mono text-sm font-bold bg-white focus:outline-none focus:ring-2"
                                    required
                                />
                            </div>
                        </div>

                        {{-- Quick Auto-Fill Chips (Bantu Operator Cepat) --}}
                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <span class="text-[10px] text-zinc-400 font-mono">Cepat:</span>
                            <button
                                type="button"
                                wire:click="applyQuickHmAddition(8)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                                title="Tambah 8 Jam (Shift Normal)"
                            >
                                +8 Jam (Normal)
                            </button>
                            <button
                                type="button"
                                wire:click="applyQuickHmAddition(10)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                                title="Tambah 10 Jam"
                            >
                                +10 Jam
                            </button>
                            <button
                                type="button"
                                wire:click="applyQuickHmAddition(12)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                                title="Tambah 12 Jam (Shift Lembur/Pengecoran Jauh)"
                            >
                                +12 Jam (Lembur)
                            </button>
                        </div>

                        {{-- Real-time Typo Warning Banner --}}
                        @if ($this->hmTypoWarning)
                            <div class="p-2.5 rounded-md bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-2">
                                <span class="text-amber-600 font-bold text-sm">⚠️</span>
                                <span class="font-medium">{{ $this->hmTypoWarning }}</span>
                            </div>
                        @endif

                        @error('end_hm')
                            <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror

                        @if ($this->selectedFleet && $this->calculatedTotalHm > 0)
                            <div class="text-[11px] text-zinc-500 flex items-center justify-between pt-1 border-t border-zinc-200/60 font-mono">
                                <span>Prediksi Sisa HM Servis:</span>
                                <span class="font-bold text-zinc-900">{{ number_format($this->estimatedRemainingHm, 2, ',', '.') }} Jam</span>
                            </div>
                        @endif
                    </div>

                    {{-- Input Jarak Tempuh KM (Khusus TM) --}}
                    @if ($this->selectedFleet && $this->selectedFleet->type === 'truck_mixer')
                        <div class="p-3 rounded-lg bg-zinc-50/70 border border-zinc-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-900 uppercase tracking-tight">Odometer Jarak Tempuh (KM)</span>
                                @if ($this->calculatedTotalKm > 0)
                                    <span class="text-[11px] font-mono font-bold text-zinc-700 bg-zinc-200/70 px-2 py-0.5 rounded">
                                        +{{ number_format($this->calculatedTotalKm, 1, ',', '.') }} KM
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-500 mb-1">KM Kemarin</label>
                                    <input
                                        type="text"
                                        value="{{ $this->start_km !== '' ? number_format((float) $this->start_km, 1, ',', '.') : '-' }}"
                                        disabled
                                        class="w-full h-10 px-3 rounded-md border border-zinc-200 bg-zinc-100 text-zinc-500 font-mono text-sm cursor-not-allowed"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-zinc-900 mb-1">KM Baru (Opsional)</label>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        wire:model.live.debounce.300ms="end_km"
                                        placeholder="Spidometer KM"
                                        class="w-full h-10 px-3 rounded-md border {{ $this->kmTypoWarning ? 'border-amber-400 bg-amber-50/20' : 'border-zinc-300 focus:ring-zinc-900' }} font-mono text-sm bg-white focus:outline-none focus:ring-2"
                                    />
                                </div>
                            </div>

                            {{-- Real-time KM Typo Warning Banner --}}
                            @if ($this->kmTypoWarning)
                                <div class="p-2.5 rounded-md bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-2">
                                    <span class="text-amber-600 font-bold text-sm">⚠️</span>
                                    <span class="font-medium">{{ $this->kmTypoWarning }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Input Bahan Bakar & Pelumas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <x-ui.input
                            wire:model="fuel_liters"
                            label="Solar Dexlite (Liter)"
                            name="fuel_liters"
                            placeholder="0.00"
                            suffix="L"
                            mono
                        />

                        <x-ui.input
                            wire:model="engine_oil_liters"
                            label="Oli Mesin (Liter)"
                            name="engine_oil_liters"
                            placeholder="0.00"
                            suffix="L"
                            mono
                        />

                        <x-ui.input
                            wire:model="hydraulic_oil_liters"
                            label="Oli Hidraulik (Liter)"
                            name="hydraulic_oil_liters"
                            placeholder="0.00"
                            suffix="L"
                            mono
                        />
                    </div>

                    {{-- Driver / Operator & Catatan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <x-ui.input
                            wire:model="driver_operator_name"
                            label="Nama Driver / Operator"
                            name="driver_operator_name"
                            placeholder="Nama personil"
                        />

                        <x-ui.input
                            wire:model="notes"
                            label="Keterangan / Kendala"
                            name="notes"
                            placeholder="Kondisi fisik unit / rute"
                        />
                    </div>

                    {{-- Action Button --}}
                    <div class="pt-2">
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            class="w-full h-12 text-sm font-semibold shadow-xs"
                        >
                            <span>Simpan Laporan Log Harian</span>
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>

        {{-- Kolom Kanan: Tab Tampilan Unit & Riwayat (lg:col-span-7) --}}
        <div class="lg:col-span-7 space-y-4">
            {{-- Tab Navigation Bar --}}
            <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-lg bg-zinc-100 border border-zinc-200/80">
                <button
                    type="button"
                    wire:click="setTab('truck_mixer')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'truck_mixer' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    🚚 Truck Mixer (11 Unit)
                </button>
                <button
                    type="button"
                    wire:click="setTab('wheel_loader')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'wheel_loader' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    🚜 Wheel Loader
                </button>
                <button
                    type="button"
                    wire:click="setTab('others')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'others' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    🛠️ Lain-lain
                </button>
                <button
                    type="button"
                    wire:click="setTab('daily_logs')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'daily_logs' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    📋 Riwayat Log HM/BBM
                </button>
                <button
                    type="button"
                    wire:click="setTab('maintenance')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'maintenance' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    🔧 Buku Servis & Perbaikan
                </button>
            </div>

            {{-- TAB 1: TRUCK MIXER (11 UNIT) --}}
            @if ($activeTab === 'truck_mixer')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @foreach ($tmFleets as $tm)
                        @php
                            $statusInfo = $tm->service_status_label;
                            $pct = $tm->hm_progress_percentage;
                        @endphp
                        <div class="p-4 rounded-xl bg-white border {{ $tm->is_service_due ? 'border-red-300 ring-1 ring-red-200' : 'border-zinc-200' }} shadow-2xs space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold font-mono text-base text-zinc-900">{{ $tm->unit_code }}</span>
                                        <span class="text-xs font-mono text-zinc-400">({{ $tm->license_plate ?? '-' }})</span>
                                    </div>
                                    <span class="text-[11px] text-zinc-500 block">{{ $tm->name }}</span>
                                </div>
                                <x-ui.badge :variant="$statusInfo['variant']" size="sm">
                                    {{ $statusInfo['badge'] }}
                                </x-ui.badge>
                            </div>

                            {{-- Meteran Terkini --}}
                            <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-zinc-50 p-2.5 rounded-lg border border-zinc-100">
                                <div>
                                    <span class="text-[10px] text-zinc-400 block uppercase">Hour Meter:</span>
                                    <span class="font-bold text-zinc-900 text-sm">{{ number_format($tm->current_hm, 1, ',', '.') }}</span> HM
                                </div>
                                <div>
                                    <span class="text-[10px] text-zinc-400 block uppercase">Jarak Tempuh:</span>
                                    <span class="font-bold text-zinc-900 text-sm">{{ number_format($tm->current_km ?? 0, 0, ',', '.') }}</span> KM
                                </div>
                            </div>

                            {{-- Traffic Light Countdown Bar Menuju Servis 500 Jam --}}
                            @php
                                $pill = $tm->countdown_pill;
                            @endphp
                            <div class="space-y-1.5 p-2 rounded-lg bg-zinc-50 border border-zinc-100">
                                <div class="flex items-center justify-between text-[11px] font-mono">
                                    <span class="text-zinc-500 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $pill['dot'] }} {{ $tm->is_service_due ? 'animate-ping' : '' }}"></span>
                                        Siklus 500 Jam:
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $pill['bg'] }}">
                                        {{ $pill['text'] }}
                                    </span>
                                </div>
                                <div class="w-full h-2.5 rounded-full bg-zinc-200 overflow-hidden relative">
                                    <div
                                        class="h-full rounded-full transition-all duration-300 {{ $pill['bar_color'] }}"
                                        style="width: {{ $pct }}%"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-400 font-mono">
                                    <span>Servis Terakhir: {{ number_format($tm->last_service_hm, 0) }} HM</span>
                                    <span>Target: {{ number_format($tm->next_service_hm, 0) }} HM</span>
                                </div>
                            </div>

                            {{-- Action Quick Buttons --}}
                            <div class="flex items-center gap-2 pt-1 border-t border-zinc-100">
                                <button
                                    type="button"
                                    wire:click="selectFleet({{ $tm->id }})"
                                    class="flex-1 py-1.5 px-2.5 text-xs font-medium rounded-md bg-zinc-100 hover:bg-zinc-200 text-zinc-800 transition-colors text-center cursor-pointer"
                                >
                                    📝 Input Log HM
                                </button>
                                <button
                                    type="button"
                                    wire:click="openServiceModal({{ $tm->id }})"
                                    class="py-1.5 px-2.5 text-xs font-medium rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors text-center cursor-pointer"
                                    title="Catat servis berkala dan reset sisa 500 jam"
                                >
                                    🔧 Catat Servis
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- TAB 2: WHEEL LOADER (BUCKET 1,8 M3) --}}
            @if ($activeTab === 'wheel_loader' && $loaderFleet)
                @php
                    $wl = $loaderFleet;
                    $statusInfo = $wl->service_status_label;
                    $pctHm = $wl->hm_progress_percentage;
                    $remDays = $wl->remaining_days ?? 30;
                    $pctDays = min(100, max(0, round(((30 - $remDays) / 30) * 100, 1)));
                @endphp
                <div class="p-5 rounded-xl bg-white border {{ $wl->is_service_due ? 'border-red-300 ring-1 ring-red-200' : 'border-zinc-200' }} shadow-2xs space-y-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-bold font-mono text-zinc-900">{{ $wl->unit_code }}</h3>
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-zinc-100 text-zinc-700">
                                    Bucket 1,8
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                Alat berat feeding agregat pasir & batu ke hopper batching plant.
                            </p>
                        </div>
                        <x-ui.badge :variant="$statusInfo['variant']" size="md">
                            {{ $statusInfo['badge'] }}
                        </x-ui.badge>
                    </div>

                    {{-- Meteran Terkini --}}
                    <div class="p-3.5 rounded-lg bg-zinc-50 border border-zinc-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-zinc-400 font-mono uppercase block">Hour Meter (HM) Terkini:</span>
                            <span class="text-2xl font-bold font-mono text-zinc-900">{{ number_format($wl->current_hm, 2, ',', '.') }}</span>
                            <span class="text-xs text-zinc-500 font-mono">HM</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-zinc-400 font-mono uppercase block">Servis Terakhir:</span>
                            <span class="text-sm font-bold font-mono text-zinc-700">
                                {{ $wl->last_service_date ? $wl->last_service_date->format('d/m/Y') : '-' }}
                            </span>
                            <span class="text-[11px] text-zinc-400 block font-mono">pada {{ number_format($wl->last_service_hm, 0) }} HM</span>
                        </div>
                    </div>

                    {{-- Dual Countdown: 500 Jam ATAU 1 Bulan --}}
                    <div class="space-y-4 pt-2 border-t border-zinc-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-900 uppercase tracking-tight">
                                Dual-Threshold Maintenance Alert (Whichever Comes First)
                            </span>
                            <span class="text-[11px] text-zinc-500 font-mono">Aturan: 500 Jam ATAU 1 Bulan</span>
                        </div>

                        {{-- Kriteria 1: 500 Jam HM --}}
                        @php
                            $wlPill = $wl->countdown_pill;
                            $dayColor = $remDays <= 0 ? 'bg-red-500 text-white animate-pulse' : ($remDays <= 5 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-50 text-emerald-800 border border-emerald-200');
                            $dayBarColor = $remDays <= 0 ? 'bg-red-500' : ($remDays <= 5 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="p-3.5 rounded-lg bg-zinc-50/70 border border-zinc-200/80 space-y-2">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="font-semibold text-zinc-700 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $wlPill['dot'] }}"></span>
                                    1. Batas Jam Kerja (500 Jam HM):
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $wlPill['bg'] }}">
                                    {{ $wlPill['text'] }}
                                </span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-zinc-200 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300 {{ $wlPill['bar_color'] }}"
                                    style="width: {{ $pctHm }}%"
                                ></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-zinc-400 font-mono">
                                <span>Terpakai: {{ number_format($wl->current_hm - $wl->last_service_hm, 1) }} Jam</span>
                                <span>Target Servis: {{ number_format($wl->next_service_hm, 0) }} HM</span>
                            </div>
                        </div>

                        {{-- Kriteria 2: 30 Hari Kalender --}}
                        <div class="p-3.5 rounded-lg bg-zinc-50/70 border border-zinc-200/80 space-y-2">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="font-semibold text-zinc-700 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $remDays <= 0 ? 'bg-red-500' : ($remDays <= 5 ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                    2. Batas Kalender (1 Bulan / 30 Hari):
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $dayColor }}">
                                    {{ $remDays > 0 ? "🟢 Sisa {$remDays} Hari" : '🚨 JATUH TEMPO' }}
                                </span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-zinc-200 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300 {{ $dayBarColor }}"
                                    style="width: {{ $pctDays }}%"
                                ></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-zinc-400 font-mono">
                                <span>Servis Terakhir: {{ $wl->last_service_date ? $wl->last_service_date->format('d/m/Y') : '-' }}</span>
                                <span>Jatuh Tempo: {{ $wl->last_service_date ? $wl->last_service_date->copy()->addDays(30)->format('d/m/Y') : '-' }}</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            wire:click="selectFleet({{ $wl->id }})"
                            class="flex-1 py-2 px-4 rounded-md bg-zinc-900 hover:bg-zinc-800 text-white font-semibold text-xs transition-colors text-center cursor-pointer shadow-2xs"
                        >
                            📝 Input Log Harian Loader
                        </button>
                        <button
                            type="button"
                            wire:click="openServiceModal({{ $wl->id }})"
                            class="py-2 px-4 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-colors text-center cursor-pointer shadow-2xs"
                        >
                            🔧 Catat Servis Selesai & Reset
                        </button>
                    </div>
                </div>
            @endif

            {{-- TAB 3: LAIN-LAIN / OPERASIONAL CADANGAN --}}
            @if ($activeTab === 'others' && $othersFleet)
                <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-bold font-mono text-zinc-900">{{ $othersFleet->unit_code }}</h3>
                            <p class="text-xs text-zinc-500">
                                Kategori antisipasi untuk alat operasional lapangan lainnya (Excavator, Concrete Pump, Mobil Lapangan Hilux, dll.).
                            </p>
                        </div>
                        <x-ui.badge variant="zinc" size="sm">Siap Digunakan</x-ui.badge>
                    </div>

                    <div class="p-4 rounded-lg bg-zinc-50 border border-zinc-200/80 text-xs text-zinc-600 space-y-2">
                        <p>
                            Gunakan unit ini saat ada pengisian solar Dexlite atau jam kerja alat sewa/proyek di luar 11 TM dan Wheel Loader.
                        </p>
                        <div class="flex items-center gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="selectFleet({{ $othersFleet->id }})"
                                class="py-1.5 px-3 rounded-md bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 transition-colors cursor-pointer"
                            >
                                📝 Catat Pemakaian Unit Ini
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 4: RIWAYAT LOG HARIAN --}}
            @if ($activeTab === 'daily_logs')
                <x-ui.card
                    title="Riwayat Pencatatan Log Harian (HM, KM & Solar)"
                    description="Histori pencatatan jam kerja mesin dan bahan bakar yang terinput oleh operator."
                >
                    <div class="mb-4 max-w-sm">
                        <x-ui.input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari unit atau driver..."
                            prefix="🔍"
                        />
                    </div>

                    @php
                        $headers = ['Tanggal', 'Unit', 'HM Awal', 'HM Akhir', 'Total Jam', 'KM', 'BBM Solar', 'Driver / Operator'];
                    @endphp
                    <x-ui.table :headers="$headers">
                        @forelse ($dailyLogs as $log)
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                                    {{ $log->log_date ? $log->log_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-semibold font-mono text-zinc-900 whitespace-nowrap">
                                    {{ $log->fleet->unit_code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 tabular-nums">
                                    {{ number_format((float) $log->start_hm, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-900 font-bold tabular-nums">
                                    {{ number_format((float) $log->end_hm, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-emerald-700 font-bold whitespace-nowrap tabular-nums">
                                    +{{ number_format((float) $log->total_hm, 2, ',', '.') }} Jam
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 whitespace-nowrap tabular-nums">
                                    {{ $log->total_km !== null ? '+' . number_format((float) $log->total_km, 1, ',', '.') . ' KM' : '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 whitespace-nowrap tabular-nums">
                                    {{ (float) $log->fuel_liters > 0 ? number_format((float) $log->fuel_liters, 1, ',', '.') . ' L' : '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-700 whitespace-nowrap">
                                    {{ $log->driver_operator_name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                                    Belum ada log harian yang tercatat. Silakan input pada form sebelah kiri.
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>

                    @if ($dailyLogs->hasPages())
                        <div class="mt-4 pt-3 border-t border-zinc-100">
                            {{ $dailyLogs->links() }}
                        </div>
                    @endif
                </x-ui.card>
            @endif

            {{-- TAB 5: BUKU SERVIS & RIWAYAT MAINTENANCE --}}
            @if ($activeTab === 'maintenance')
                <x-ui.card
                    title="Buku Servis & Riwayat Perawatan Berkala"
                    description="Catatan servis berkala 500 jam / bulanan yang telah dilakukan oleh teknisi/mekanik."
                >
                    @php
                        $mHeaders = ['Tanggal', 'Unit', 'HM Servis', 'Jenis Servis', 'Mekanik', 'Rincian Perbaikan & Sparepart', 'Biaya'];
                    @endphp
                    <x-ui.table :headers="$mHeaders">
                        @forelse ($maintenanceRecords as $m)
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                                    {{ $m->service_date ? $m->service_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-semibold font-mono text-zinc-900 whitespace-nowrap">
                                    {{ $m->fleet->unit_code ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-bold text-zinc-900 tabular-nums">
                                    {{ number_format((float) $m->hm_at_service, 2, ',', '.') }} HM
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-ui.badge variant="zinc" size="sm">
                                        {{ str_replace('_', ' ', ucwords($m->service_type, '_')) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-800 font-medium whitespace-nowrap">
                                    {{ $m->mechanic_name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-600">
                                    <div class="font-medium text-zinc-900">{{ $m->description }}</div>
                                    @if ($m->parts_replaced)
                                        <div class="text-[11px] text-zinc-400 mt-0.5">Part: {{ $m->parts_replaced }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900 whitespace-nowrap tabular-nums">
                                    {{ (float) $m->cost > 0 ? 'Rp ' . number_format((float) $m->cost, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($mHeaders) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                                    Belum ada catatan servis tercatat. Klik tombol "Catat Servis" pada tab unit untuk menambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>

                    @if ($maintenanceRecords->hasPages())
                        <div class="mt-4 pt-3 border-t border-zinc-100">
                            {{ $maintenanceRecords->links() }}
                        </div>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>

    {{-- Modal Catat Servis Selesai & Reset Indikator --}}
    @if ($showServiceModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl border border-zinc-200 max-w-lg w-full overflow-hidden space-y-4">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Catat Servis Selesai & Reset Indikator</h3>
                        <p class="text-xs text-zinc-500">Mencatat penggantian oli/sparepart dan mereset hitungan servis ke 500 jam / 30 hari.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeServiceModal"
                        class="text-zinc-400 hover:text-zinc-600 text-lg font-mono cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="saveMaintenanceRecord" class="px-6 py-2 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input
                            wire:model="service_date"
                            label="Tanggal Servis Selesai"
                            name="service_date"
                            type="date"
                            required
                        />

                        <x-ui.input
                            wire:model="hm_at_service"
                            label="HM Saat Diservis"
                            name="hm_at_service"
                            type="text"
                            placeholder="Contoh: 2500"
                            mono
                            required
                        />
                    </div>

                    <x-ui.input
                        wire:model="mechanic_name"
                        label="Nama Teknisi / Mekanik"
                        name="mechanic_name"
                        placeholder="Contoh: Budi (Mekanik Plant)"
                        required
                    />

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Rincian Servis & Pekerjaan <span class="text-red-500">*</span></label>
                        <textarea
                            wire:model="service_description"
                            rows="2"
                            class="w-full px-3 py-2 rounded-md border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        ></textarea>
                    </div>

                    <x-ui.input
                        wire:model="parts_replaced"
                        label="Sparepart / Pelumas yang Diganti"
                        name="parts_replaced"
                        placeholder="Contoh: Filter Oli, Filter Solar, 18L Oli Mesin 15W-40"
                    />

                    <x-ui.input
                        wire:model="service_cost"
                        label="Total Biaya Servis (Rp, Opsional)"
                        name="service_cost"
                        placeholder="0"
                        prefix="Rp"
                        mono
                    />

                    <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2 pb-4">
                        <button
                            type="button"
                            wire:click="closeServiceModal"
                            class="px-4 py-2 text-xs font-semibold rounded-md border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-xs font-semibold rounded-md bg-zinc-900 text-white hover:bg-zinc-800 transition-colors cursor-pointer shadow-xs"
                        >
                            Simpan & Reset Indikator Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
