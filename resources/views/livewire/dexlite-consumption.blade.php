<div class="space-y-6">
    {{-- Header & Aksi Cepat --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900">
                    Buku Konsumsi Dexlite & Pelumas (BBM)
                </h1>
                <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-emerald-100 text-emerald-800">
                    Tangki Plant Ringroad 3
                </span>
            </div>
            <p class="text-xs sm:text-sm text-zinc-500 mt-1">
                Rekap kendali harian pengisian BBM solar Dexlite per unit, log penerimaan tangki, oli pelumas, serta analisis indeks efisiensi liter/m³.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <input
                type="month"
                wire:model.live="selectedMonth"
                class="px-3 py-1.5 rounded-md border border-zinc-300 text-xs font-mono font-semibold bg-white text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900"
            />
            <button
                type="button"
                wire:click="exportExcel"
                class="px-3 py-2 rounded-md bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold border border-zinc-300 transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs"
                title="1-Click Export Excel format laporan resmi plant TBI (.csv)"
            >
                <span>📥</span>
                <span>Export Excel</span>
            </button>
            <button
                type="button"
                wire:click="openShareModal"
                class="px-3 py-2 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs"
                title="Bagikan rekap harian BBM via WhatsApp"
            >
                <span>📲</span>
                <span>Share WA</span>
            </button>
            <button
                type="button"
                wire:click="openReceiveModal"
                class="px-3.5 py-2 rounded-md bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            >
                <span>➕</span>
                <span>Penerimaan Solar</span>
            </button>
            <button
                type="button"
                wire:click="openUsageModal"
                class="px-3.5 py-2 rounded-md bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer flex items-center gap-1.5"
            >
                <span>⛽</span>
                <span>Catat Isi BBM Unit</span>
            </button>
        </div>
    </div>

    {{-- Smart Alert Stok Minimum Tangki Solar --}}
    @if ($this->isLowStock)
        <div class="p-4 rounded-xl border {{ $this->isCriticalStock ? 'bg-red-50 border-red-300 text-red-900' : 'bg-amber-50 border-amber-300 text-amber-900' }} shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="text-2xl {{ $this->isCriticalStock ? 'animate-bounce' : '' }}">
                    {{ $this->isCriticalStock ? '🚨' : '⚠️' }}
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-sm">
                            {{ $this->isCriticalStock ? 'PERINGATAN KRITIS: STOK SOLAR MENIPIS TINGGI!' : 'SMART ALERT: STOK TANGKI SOLAR MENDEKATI BATAS MINIMUM' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $this->isCriticalStock ? 'bg-red-200 text-red-900' : 'bg-amber-200 text-amber-900' }}">
                            Sisa {{ number_format($this->physicalSoundingStock, 2, ',', '.') }} L
                        </span>
                    </div>
                    <p class="text-xs mt-1 leading-relaxed">
                        Rata-rata konsumsi harian plant: <strong class="font-mono">{{ number_format($this->averageDailyBurnRate, 1, ',', '.') }} Liter/hari</strong>.
                        Estimasi ketahanan stok fisik tangki: <strong class="font-mono text-zinc-950 font-bold underline">~{{ $this->daysRemaining }} Hari Produksi</strong>.
                        Disarankan segera mengajukan Purchase Order DO ke PT Pertamina Patra Niaga untuk menjaga kelancaran operasi batching.
                    </p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-2">
                <button
                    type="button"
                    wire:click="openReceiveModal"
                    class="px-3.5 py-2 rounded-md text-xs font-bold font-mono transition-colors cursor-pointer shadow-xs {{ $this->isCriticalStock ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white' }}"
                >
                    + Catat Masuk DO Solar
                </button>
            </div>
        </div>
    @endif

    {{-- Flash Notifications --}}
    @if (session()->has('success'))
        <div class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Ringkasan KPI Konsumsi & Rekonsiliasi BBM --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        {{-- Stok Awal --}}
        <div class="p-3.5 rounded-xl bg-white border border-zinc-200 shadow-2xs">
            <span class="text-[11px] font-medium text-zinc-500 block">Stok Awal Bulan</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl font-bold font-mono text-zinc-900">{{ number_format($this->initialStock, 2, ',', '.') }}</span>
                <span class="text-xs text-zinc-400 font-mono">Liter</span>
            </div>
            <span class="text-[10px] text-zinc-400 block mt-1">Saldo awal buku</span>
        </div>

        {{-- Total Penerimaan --}}
        <div class="p-3.5 rounded-xl bg-white border border-zinc-200 shadow-2xs">
            <span class="text-[11px] font-medium text-emerald-700 block">Total Penerimaan (Masuk)</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl font-bold font-mono text-emerald-700">+{{ number_format($this->totalReceives, 2, ',', '.') }}</span>
                <span class="text-xs text-zinc-400 font-mono">Liter</span>
            </div>
            <span class="text-[10px] text-zinc-400 block mt-1">8 Pengiriman Pertamina</span>
        </div>

        {{-- Total Pemakaian --}}
        <div class="p-3.5 rounded-xl bg-white border border-zinc-200 shadow-2xs">
            <span class="text-[11px] font-medium text-amber-700 block">Total Pemakaian (Keluar)</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl font-bold font-mono text-amber-700">-{{ number_format($this->totalUsage, 2, ',', '.') }}</span>
                <span class="text-xs text-zinc-400 font-mono">Liter</span>
            </div>
            <span class="text-[10px] text-zinc-400 block mt-1">TM: {{ number_format($this->tmUsage, 0) }}L | Loader: {{ number_format($this->loaderUsage, 0) }}L</span>
        </div>

        {{-- Sisa Stok Sistem --}}
        <div class="p-3.5 rounded-xl bg-white border border-zinc-200 shadow-2xs">
            <span class="text-[11px] font-medium text-zinc-500 block">Sisa Stok Akhir Sistem</span>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl font-bold font-mono text-zinc-900">{{ number_format($this->endingCalculatedStock, 2, ',', '.') }}</span>
                <span class="text-xs text-zinc-400 font-mono">Liter</span>
            </div>
            <span class="text-[10px] text-zinc-400 block mt-1">Formula: Awal + Masuk - Keluar</span>
        </div>

        {{-- Sounding Tangki Fisik & Status --}}
        <div class="p-3.5 rounded-xl bg-white border {{ abs($this->reconciliationDiscrepancy) < 0.01 ? 'border-emerald-200 bg-emerald-50/20' : 'border-amber-200 bg-amber-50/20' }} shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-medium text-zinc-600 block">Sounding Fisik Tangki</span>
                <x-ui.badge :variant="abs($this->reconciliationDiscrepancy) < 0.01 ? 'emerald' : 'amber'" size="sm">
                    {{ abs($this->reconciliationDiscrepancy) < 0.01 ? 'SESUAI 100%' : 'Ada Selisih' }}
                </x-ui.badge>
            </div>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-xl font-bold font-mono text-emerald-800">{{ number_format($this->physicalSoundingStock, 2, ',', '.') }}</span>
                <span class="text-xs text-zinc-400 font-mono">Liter</span>
            </div>
            <span class="text-[10px] font-mono {{ abs($this->reconciliationDiscrepancy) < 0.01 ? 'text-emerald-600' : 'text-amber-600' }} block mt-1">
                Selisih: {{ number_format($this->reconciliationDiscrepancy, 2, ',', '.') }} Liter
            </span>
        </div>
    </div>

    {{-- Navigasi Tab Menu --}}
    <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-lg bg-zinc-100 border border-zinc-200/80">
        <button
            type="button"
            wire:click="setTab('matrix')"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'matrix' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
        >
            📊 Matriks Harian (Excel Grid)
        </button>
        <button
            type="button"
            wire:click="setTab('indices')"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'indices' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
        >
            🎯 Indeks Efisiensi BBM (L/m³)
        </button>
        <button
            type="button"
            wire:click="setTab('receives')"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'receives' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
        >
            🛢️ Buku Penerimaan Solar
        </button>
        <button
            type="button"
            wire:click="setTab('sounding')"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'sounding' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
        >
            📏 Kalkulator Sounding Tangki (π r² t)
        </button>
        <button
            type="button"
            wire:click="setTab('lubricants')"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer {{ $activeTab === 'lubricants' ? 'bg-white text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
        >
            🔧 Log Oli Pelumas
        </button>
    </div>

    {{-- TAB 1: MATRIKS HARIAN (EXCEL GRID) --}}
    @if ($activeTab === 'matrix')
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900">
                        Matriks Pemakaian Solar Per Unit Harian (Bulan: {{ \Illuminate\Support\Carbon::parse($selectedMonth.'-01')->isoFormat('MMMM Y') }})
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Pencatatan rincian volume BBM solar Dexlite yang diisi ke masing-masing armada dan alat berat tiap harinya.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs font-mono">
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-zinc-100 text-zinc-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Inflow: +{{ number_format($this->totalReceives, 1) }} L
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-zinc-100 text-zinc-700">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Outflow: -{{ number_format($this->totalUsage, 1) }} L
                    </span>
                </div>
            </div>

            {{-- Keyboard Hint & Outflow info --}}
            <div class="flex items-center justify-between text-xs font-mono text-zinc-500 pt-1">
                <span class="flex items-center gap-1.5 text-zinc-600">
                    <span>⌨️</span>
                    <span><strong>Navigasi Excel:</strong> Klik cell mana saja, gunakan tombol panah <strong>&larr; &uarr; &rarr; &darr;</strong>, <strong>Enter</strong> atau <strong>Tab</strong> untuk berpindah.</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                        <span>🚩</span>
                        <span>Flag Anomali (&gt; 200L TM / &gt; 300L Loader)</span>
                    </span>
                </span>
            </div>

            {{-- Table Grid with Excel Arrow Key Navigation --}}
            <div
                x-data="{
                    activeR: 1,
                    activeC: 2,
                    maxR: {{ count($this->dailyMatrixRows) }},
                    maxC: 18,
                    move(dr, dc) {
                        let nr = Math.max(1, Math.min(this.maxR, this.activeR + dr));
                        let nc = Math.max(1, Math.min(this.maxC, this.activeC + dc));
                        this.activeR = nr;
                        this.activeC = nc;
                        let target = document.getElementById('mc-' + nr + '-' + nc);
                        if (target) {
                            target.focus();
                            target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                        }
                    }
                }"
                @keydown.up.prevent="move(-1, 0)"
                @keydown.down.prevent="move(1, 0)"
                @keydown.left.prevent="move(0, -1)"
                @keydown.right.prevent="move(0, 1)"
                @keydown.tab.prevent="move(0, 1)"
                @keydown.enter.prevent="move(1, 0)"
                class="overflow-x-auto border border-zinc-200 rounded-lg focus:outline-none"
            >
                <table class="min-w-full text-xs divide-y divide-zinc-200 font-mono">
                    <thead class="bg-zinc-100/80 text-zinc-700 text-[11px] uppercase tracking-wider sticky top-0">
                        <tr>
                            <th class="py-2.5 px-3 text-left font-bold border-r border-zinc-200 bg-zinc-100 sticky left-0 z-10 w-20">Tgl</th>
                            <th class="py-2.5 px-3 text-right font-bold text-emerald-800 bg-emerald-50/70 border-r border-zinc-200">Penerimaan</th>
                            {{-- Group Truck Mixer --}}
                            @foreach (['TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06', 'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12'] as $tm)
                                <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold">{{ $tm }}</th>
                            @endforeach
                            <th class="py-2.5 px-2.5 text-right font-bold text-zinc-900 bg-zinc-200/60 border-r border-zinc-300">Total TM</th>
                            {{-- Alat Berat & Operasional --}}
                            <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold bg-amber-50/40">Loader</th>
                            <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold">TM Rental</th>
                            <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold">Excavator</th>
                            <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold">CP (Pump)</th>
                            <th class="py-2.5 px-2 text-right border-r border-zinc-200 font-semibold">Hilux</th>
                            <th class="py-2.5 px-3 text-right font-bold text-zinc-900 bg-zinc-200 sticky right-0 z-10">Total Hari</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">
                        @foreach ($this->dailyMatrixRows as $rIndex => $row)
                            @php
                                $rNum = $rIndex + 1;
                                $hasActivity = $row['receive_vol'] > 0 || $row['daily_total'] > 0;
                            @endphp
                            <tr class="hover:bg-zinc-50 transition-colors {{ ! $hasActivity ? 'text-zinc-400' : 'text-zinc-800' }}">
                                <td class="py-2 px-3 font-bold border-r border-zinc-200 bg-white sticky left-0 z-10">
                                    {{ $row['day'] }} {{ \Illuminate\Support\Carbon::parse($row['date'])->format('M') }}
                                </td>
                                {{-- Col 1: Penerimaan --}}
                                <td
                                    id="mc-{{ $rNum }}-1"
                                    tabindex="0"
                                    @focus="activeR = {{ $rNum }}; activeC = 1"
                                    class="py-2 px-3 text-right border-r border-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:bg-amber-100/70 select-none {{ $row['receive_vol'] > 0 ? 'font-bold text-emerald-700 bg-emerald-50/30' : 'text-zinc-300' }}"
                                >
                                    {{ $row['receive_vol'] > 0 ? number_format($row['receive_vol'], 1, ',', '.') : '-' }}
                                </td>
                                {{-- 11 TM Columns (Cols 2 to 12) --}}
                                @foreach (['TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06', 'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12'] as $tmIdx => $tm)
                                    @php
                                        $cNum = $tmIdx + 2;
                                        $v = $row['units'][$tm] ?? 0;
                                        $isAnomaly = $v > 200;
                                    @endphp
                                    <td
                                        id="mc-{{ $rNum }}-{{ $cNum }}"
                                        tabindex="0"
                                        @focus="activeR = {{ $rNum }}; activeC = {{ $cNum }}"
                                        class="py-2 px-2 text-right border-r border-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:bg-amber-100/70 select-none {{ $isAnomaly ? 'bg-amber-100/80 text-amber-950 font-black' : ($v > 0 ? 'font-bold text-zinc-900 bg-zinc-50/50' : 'text-zinc-300') }}"
                                    >
                                        @if ($v > 0)
                                            <span>{{ number_format($v, 0) }}</span>
                                            @if ($isAnomaly)
                                                <span class="text-[10px] text-red-600 font-bold ml-0.5" title="Anomali: Pemakaian {{ $v }}L di atas kapasitas normal TM (>200L)">🚩</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                {{-- Col 13: Subtotal TM --}}
                                <td
                                    id="mc-{{ $rNum }}-13"
                                    tabindex="0"
                                    @focus="activeR = {{ $rNum }}; activeC = 13"
                                    class="py-2 px-2.5 text-right font-bold border-r border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:bg-amber-200 select-none {{ $row['tm_subtotal'] > 0 ? 'bg-zinc-100 text-zinc-900' : 'bg-zinc-50 text-zinc-300' }}"
                                >
                                    {{ $row['tm_subtotal'] > 0 ? number_format($row['tm_subtotal'], 0) : '-' }}
                                </td>
                                {{-- Col 14: Loader --}}
                                @php
                                    $wl = $row['units']['Wheel Loader'] ?? 0;
                                    $isWlAnomaly = $wl > 300;
                                @endphp
                                <td
                                    id="mc-{{ $rNum }}-14"
                                    tabindex="0"
                                    @focus="activeR = {{ $rNum }}; activeC = 14"
                                    class="py-2 px-2 text-right border-r border-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:bg-amber-100/70 select-none {{ $isWlAnomaly ? 'bg-amber-100/80 text-amber-950 font-black' : ($wl > 0 ? 'font-bold text-amber-800 bg-amber-50/40' : 'text-zinc-300') }}"
                                >
                                    @if ($wl > 0)
                                        <span>{{ number_format($wl, 0) }}</span>
                                        @if ($isWlAnomaly)
                                            <span class="text-[10px] text-red-600 font-bold ml-0.5" title="Anomali: Pemakaian Loader {{ $wl }}L (>300L)">🚩</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                {{-- Cols 15 to 18: Operasional --}}
                                @foreach (['TM Rental', 'Excavator', 'Concrete Pump', 'Hilux'] as $opIdx => $op)
                                    @php
                                        $cNum = $opIdx + 15;
                                        $opVal = $row['units'][$op] ?? 0;
                                    @endphp
                                    <td
                                        id="mc-{{ $rNum }}-{{ $cNum }}"
                                        tabindex="0"
                                        @focus="activeR = {{ $rNum }}; activeC = {{ $cNum }}"
                                        class="py-2 px-2 text-right border-r border-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:bg-amber-100/70 select-none {{ $opVal > 0 ? 'font-bold text-zinc-800' : 'text-zinc-300' }}"
                                    >
                                        {{ $opVal > 0 ? number_format($opVal, 0) : '-' }}
                                    </td>
                                @endforeach
                                {{-- Total Hari --}}
                                <td class="py-2 px-3 text-right font-bold bg-zinc-100 sticky right-0 z-10 {{ $row['daily_total'] > 0 ? 'text-zinc-900' : 'text-zinc-300' }}">
                                    {{ $row['daily_total'] > 0 ? number_format($row['daily_total'], 0) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    {{-- TOTAL ROW --}}
                    <tfoot class="bg-zinc-200/90 font-bold text-zinc-900 border-t-2 border-zinc-300 sticky bottom-0 z-20">
                        <tr>
                            <td class="py-2.5 px-3 uppercase text-left border-r border-zinc-300 bg-zinc-200 sticky left-0 z-20">TOTAL</td>
                            <td class="py-2.5 px-3 text-right text-emerald-800 border-r border-zinc-300 bg-emerald-100/70">
                                {{ number_format($this->matrixTotals['receive_vol'], 1, ',', '.') }}
                            </td>
                            @foreach (['TM 01', 'TM 02', 'TM 03', 'TM 10', 'TM 05', 'TM 06', 'TM 07', 'TM 08', 'TM 09', 'TM 11', 'TM 12'] as $tm)
                                <td class="py-2.5 px-2 text-right border-r border-zinc-300">
                                    {{ number_format($this->matrixTotals['units'][$tm] ?? 0, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="py-2.5 px-2.5 text-right border-r border-zinc-300 bg-zinc-300/80 text-zinc-950">
                                {{ number_format($this->matrixTotals['tm_subtotal'], 0, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-2 text-right border-r border-zinc-300 bg-amber-100/80 text-amber-950">
                                {{ number_format($this->matrixTotals['units']['Wheel Loader'] ?? 0, 0, ',', '.') }}
                            </td>
                            @foreach (['TM Rental', 'Excavator', 'Concrete Pump', 'Hilux'] as $op)
                                <td class="py-2.5 px-2 text-right border-r border-zinc-300">
                                    {{ number_format($this->matrixTotals['units'][$op] ?? 0, 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="py-2.5 px-3 text-right text-zinc-950 bg-zinc-300 sticky right-0 z-20">
                                {{ number_format($this->matrixTotals['daily_total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    {{-- TAB 2: INDEKS EFISIENSI BBM (L/M3) --}}
    @if ($activeTab === 'indices')
        <div class="space-y-6">
            <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">
                            Analisis Indeks Konsumsi Bahan Bakar Terhadap Produksi Beton
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Menghitung rasio pemakaian solar per 1 m³ beton cor yang diproduksi plant. Evaluasi efisiensi operasional armada & alat berat.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-50 border border-zinc-200">
                        <label class="text-xs font-semibold text-zinc-700 whitespace-nowrap">Volume Beton Referensi:</label>
                        <input
                            type="number"
                            step="0.1"
                            wire:model.live="referenceConcreteVolume"
                            class="w-24 px-2 py-1 text-xs font-mono font-bold rounded border border-zinc-300 text-right bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
                        />
                        <span class="text-xs text-zinc-500 font-mono">m³</span>
                    </div>
                </div>

                {{-- Table Indeks --}}
                <div class="overflow-hidden border border-zinc-200 rounded-lg">
                    <table class="min-w-full text-xs divide-y divide-zinc-200 font-mono">
                        <thead class="bg-zinc-100 text-zinc-700 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold">Kategori Unit</th>
                                <th class="py-3 px-4 text-right font-bold">Total Pemakaian Solar</th>
                                <th class="py-3 px-4 text-right font-bold">Indeks Riil (L/m³)</th>
                                <th class="py-3 px-4 text-right font-bold">Normal Indeks (Benchmark)</th>
                                <th class="py-3 px-4 text-center font-bold">Evaluasi Operasional</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            @foreach ($this->indices as $idx)
                                <tr class="hover:bg-zinc-50 transition-colors">
                                    <td class="py-3 px-4 font-bold text-zinc-900 font-sans">
                                        {{ $idx['unit'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-zinc-700">
                                        {{ number_format($idx['fuel_used'], 1, ',', '.') }} Liter
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-sm text-zinc-900">
                                        {{ number_format($idx['real_index'], 2, ',', '.') }} <span class="text-xs text-zinc-400 font-normal">L/m³</span>
                                    </td>
                                    <td class="py-3 px-4 text-right text-zinc-500">
                                        {{ number_format($idx['normal_index'], 2, ',', '.') }} <span class="text-xs text-zinc-400">L/m³</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-sans">
                                        <x-ui.badge :variant="$idx['status_color']" size="sm">
                                            {{ $idx['status'] }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Card Penjelasan Tolok Ukur --}}
                <div class="p-4 rounded-lg bg-zinc-50 border border-zinc-200/80 text-xs text-zinc-600 space-y-2">
                    <span class="font-bold text-zinc-900 block">Keterangan Rumus & Ketentuan Manajemen TBI:</span>
                    <ul class="list-disc list-inside space-y-1 text-zinc-500">
                        <li><strong>Indeks Riil (L/m³)</strong> = Total Liter Solar Unit ÷ Total Volume Produksi Beton (m³).</li>
                        <li><strong>Loader:</strong> Maksimal normal <code class="font-mono text-zinc-700">0,50 L/m³</code>. Jika riil &gt; 0,50 L/m³ status bertanda <em>Perbaiki</em> (cek idle engine atau rute feeding agregat).</li>
                        <li><strong>Truck Mixer (11 Unit):</strong> Patokan normal pengantaran <code class="font-mono text-zinc-700">3,00 L/m³</code>. Konsumsi aktual dipengaruhi jarak proyek, waktu tunggu slump di lokasi cor, dan kepadatan lalu lintas kota Samarinda.</li>
                        <li><strong>Evaluasi:</strong> Jika Indeks Riil &lt; Normal Indeks $\rightarrow$ <em>Pertahankan</em>. Jika melampaui $\rightarrow$ <em>Perbaiki</em>.</li>
                    </ul>
                </div>
            </div>

            {{-- Panel Deteksi Anomali Pemakaian BBM (Outlier Flagging) --}}
            <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                            <span>🚩</span>
                            <span>Deteksi Anomali Pemakaian BBM (Outlier Flagging)</span>
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Peringatan otomatis jika pengisian BBM melampaui kapasitas normal harian (TM &gt; 200L / Loader &gt; 300L) untuk mencegah kebocoran atau pemborosan.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded text-xs font-mono font-semibold {{ count($this->anomalousFills) > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ count($this->anomalousFills) }} Catatan Anomali
                    </span>
                </div>

                @if (count($this->anomalousFills) > 0)
                    <div class="overflow-hidden border border-zinc-200 rounded-lg">
                        <table class="min-w-full text-xs divide-y divide-zinc-200 font-mono">
                            <thead class="bg-zinc-100 text-zinc-700 text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-3 text-left font-bold">Tanggal</th>
                                    <th class="py-2.5 px-3 text-left font-bold">Unit Armada</th>
                                    <th class="py-2.5 px-3 text-right font-bold text-amber-800">Volume Terisi</th>
                                    <th class="py-2.5 px-4 text-left font-bold font-sans">Keterangan / Alasan Flag</th>
                                    <th class="py-2.5 px-3 text-center font-bold font-sans">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white">
                                @foreach ($this->anomalousFills as $anomaly)
                                    <tr class="hover:bg-amber-50/40 transition-colors">
                                        <td class="py-2.5 px-3 font-bold text-zinc-900">{{ $anomaly['date'] }}</td>
                                        <td class="py-2.5 px-3 font-bold text-zinc-800">{{ $anomaly['unit'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-amber-800">{{ number_format($anomaly['volume'], 0) }} Liter</td>
                                        <td class="py-2.5 px-4 text-zinc-600 font-sans text-xs">{{ $anomaly['reason'] }}</td>
                                        <td class="py-2.5 px-3 text-center font-sans">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                                <span>🚩</span>
                                                <span>Verifikasi</span>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                        <span>✓</span>
                        <span>Seluruh pencatatan pengisian solar pada bulan ini dalam batas wajar kapasitas unit. Tidak ada anomali terdeteksi.</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- TAB 3: BUKU PENERIMAAN SOLAR --}}
    @if ($activeTab === 'receives')
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">
                        Riwayat Penerimaan Solar Dexlite (Bulan: {{ \Illuminate\Support\Carbon::parse($selectedMonth.'-01')->isoFormat('MMMM Y') }})
                    </h3>
                    <p class="text-xs text-zinc-500">Log pengiriman tangki suplai bahan bakar solar Dexlite yang masuk ke plant TBI.</p>
                </div>
                <button
                    type="button"
                    wire:click="openReceiveModal"
                    class="px-3 py-1.5 rounded-md bg-zinc-900 text-white text-xs font-semibold hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    ➕ Catat Penerimaan Baru
                </button>
            </div>

            <div class="overflow-hidden border border-zinc-200 rounded-lg">
                <table class="min-w-full text-xs divide-y divide-zinc-200 font-mono">
                    <thead class="bg-zinc-100 text-zinc-700 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3 text-left font-bold">No</th>
                            <th class="py-2.5 px-3 text-left font-bold">Tanggal</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Supplier / Pemasok</th>
                            <th class="py-2.5 px-3 text-left font-bold">No. Surat Jalan / DO</th>
                            <th class="py-2.5 px-3 text-left font-bold">No. Kendaraan</th>
                            <th class="py-2.5 px-4 text-right font-bold text-emerald-800">Volume (Liter)</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">
                        @forelse ($receivesList as $idx => $rec)
                            <tr class="hover:bg-zinc-50">
                                <td class="py-2.5 px-3 text-zinc-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-bold text-zinc-900">{{ $rec->receive_date->format('d/m/Y') }}</td>
                                <td class="py-2.5 px-3 text-zinc-800 font-sans font-medium">{{ $rec->supplier_name }}</td>
                                <td class="py-2.5 px-3 text-zinc-600">{{ $rec->ri_do_number ?? '-' }}</td>
                                <td class="py-2.5 px-3 text-zinc-600">{{ $rec->vehicle_no ?? '-' }}</td>
                                <td class="py-2.5 px-4 text-right font-bold text-sm text-emerald-700">
                                    {{ number_format($rec->volume_liters, 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-zinc-500 font-sans">{{ $rec->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-zinc-400 font-sans">
                                    Belum ada catatan penerimaan solar pada bulan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-zinc-100 font-bold text-zinc-900">
                        <tr>
                            <td colspan="5" class="py-2.5 px-3 text-right uppercase">Total Penerimaan:</td>
                            <td class="py-2.5 px-4 text-right text-emerald-800 text-sm">
                                {{ number_format($receivesList->sum('volume_liters'), 2, ',', '.') }} L
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    {{-- TAB 4: KALKULATOR SOUNDING TANGKI SILINDER --}}
    @if ($activeTab === 'sounding')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Form Input Sounding --}}
            <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
                <div>
                    <h3 class="text-base font-bold text-zinc-900">
                        Kalkulator Sounding Tangki Silinder (π r² t)
                    </h3>
                    <p class="text-xs text-zinc-500">
                        Perhitungan volume cairan solar dalam tangki silinder vertikal berdasarkan tinggi celup mistar ukur (sounding tape).
                    </p>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <div>
                        <label class="block font-semibold text-zinc-700 mb-1 font-sans">Jari-jari Tangki Silinder (r) dalam cm:</label>
                        <input
                            type="number"
                            step="0.5"
                            wire:model.live="tankRadiusCm"
                            class="w-full px-3 py-2 rounded-md border border-zinc-300 font-bold bg-white text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900"
                        />
                        <span class="text-[11px] text-zinc-400 block mt-0.5">Diameter tangki: {{ number_format($tankRadiusCm * 2, 1) }} cm</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 mb-1 font-sans">Tinggi Celup Cairan Solar Terukur (t / h) dalam cm:</label>
                        <input
                            type="number"
                            step="0.1"
                            wire:model.live="liquidLevelCm"
                            class="w-full px-3 py-2 rounded-md border border-zinc-300 font-bold bg-white text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 text-lg"
                        />
                        <span class="text-[11px] text-zinc-400 block mt-0.5">Hasil celup mistar sounding tangki plant</span>
                    </div>

                    <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 space-y-2">
                        <span class="text-xs text-emerald-800 font-semibold uppercase block font-sans">Volume Solar Terhitung:</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold font-mono text-emerald-950">{{ number_format($this->calculatedSoundingLiters, 2, ',', '.') }}</span>
                            <span class="text-sm font-bold text-emerald-800">Liter</span>
                        </div>
                        <p class="text-[11px] text-emerald-700 font-sans">
                            Rumus: $V = \frac{\pi \times r^2 \times t}{1.000} = \frac{3,1416 \times {{ $tankRadiusCm }}^2 \times {{ $liquidLevelCm }}}{1.000}$
                        </p>
                    </div>
                </div>
            </div>

            {{-- Ilustrasi & Status Tangki --}}
            <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
                <h3 class="text-base font-bold text-zinc-900">Parameter Kalibrasi Tangki BBM TBI</h3>
                <div class="p-4 rounded-lg bg-zinc-50 border border-zinc-200/80 space-y-3 text-xs text-zinc-600 font-sans">
                    <div class="flex items-center justify-between py-1 border-b border-zinc-200">
                        <span class="text-zinc-500">Bentuk Geometri:</span>
                        <span class="font-bold text-zinc-900">Silinder Tegak (Vertical Tank)</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-zinc-200">
                        <span class="text-zinc-500">Massa Jenis / Densitas Solar:</span>
                        <span class="font-bold font-mono text-zinc-900">840,00 kg/m³</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-zinc-200">
                        <span class="text-zinc-500">Stok Fisik Opname Terkini:</span>
                        <span class="font-bold font-mono text-emerald-700">{{ number_format($this->physicalSoundingStock, 2, ',', '.') }} Liter</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-zinc-500">Status Validasi Fisik:</span>
                        <x-ui.badge variant="emerald" size="sm">✓ SESUAI REKONSILIASI</x-ui.badge>
                    </div>
                </div>

                <div class="p-3.5 rounded-lg border border-blue-200 bg-blue-50/50 text-xs text-blue-900 space-y-1">
                    <span class="font-bold block">💡 Integrasi Stock Opname Bulanan</span>
                    <p class="text-blue-800">
                        Hasil pengukuran tangki ini terhubung langsung dengan modul <strong>Stock Opname</strong> dan perhitungan selisih rekonsiliasi akhir bulan TBI Samarinda.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 5: LOG OLI & PELUMAS --}}
    @if ($activeTab === 'lubricants')
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-2xs space-y-4">
            <div>
                <h3 class="text-base font-bold text-zinc-900">
                    Buku Transfer & Pemakaian Oli Pelumas (Bulan: {{ \Illuminate\Support\Carbon::parse($selectedMonth.'-01')->isoFormat('MMMM Y') }})
                </h3>
                <p class="text-xs text-zinc-500">
                    Pencatatan sirkulasi pelumas Oli Mesin (15W-40) dan Oli Hidraulik (ISO VG 68) untuk armada Truck Mixer dan alat berat Loader.
                </p>
            </div>

            <div class="overflow-hidden border border-zinc-200 rounded-lg">
                <table class="min-w-full text-xs divide-y divide-zinc-200 font-mono">
                    <thead class="bg-zinc-100 text-zinc-700 text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3 text-left font-bold">No</th>
                            <th class="py-2.5 px-3 text-left font-bold">Tanggal</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Jenis Transaksi</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Kategori Oli</th>
                            <th class="py-2.5 px-4 text-right font-bold">Volume (Liter)</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Plant Asal / Tujuan</th>
                            <th class="py-2.5 px-3 text-left font-bold font-sans">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">
                        @forelse ($transfersList as $idx => $tr)
                            <tr class="hover:bg-zinc-50">
                                <td class="py-2.5 px-3 text-zinc-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-bold text-zinc-900">{{ $tr->transfer_date->format('d/m/Y') }}</td>
                                <td class="py-2.5 px-3 font-sans">
                                    <x-ui.badge :variant="$tr->transfer_type === 'transfer_in' ? 'emerald' : 'amber'" size="sm">
                                        {{ $tr->transfer_type === 'transfer_in' ? 'Transfer Masuk' : 'Transfer Keluar' }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-2.5 px-3 text-zinc-800 font-sans font-semibold">
                                    {{ $tr->oil_type === 'engine_oil' ? 'Oli Mesin' : 'Oli Hidraulik' }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-bold text-zinc-900">
                                    {{ number_format($tr->liters, 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-zinc-600 font-sans">
                                    {{ $tr->source_plant ?? '-' }} → {{ $tr->destination_plant ?? '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-zinc-500 font-sans">{{ $tr->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-zinc-400 font-sans">
                                    Belum ada transaksi transfer oli khusus di bulan ini. (Pemakaian oli rutin per unit tercatat di Buku Servis).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL 1: CATAT PENERIMAAN SOLAR MASUK --}}
    @if ($showReceiveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl border border-zinc-200 max-w-md w-full overflow-hidden space-y-4">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Catat Penerimaan Solar Dexlite</h3>
                        <p class="text-xs text-zinc-500">Mencatat suplai BBM yang masuk ke tangki timbun plant.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeReceiveModal"
                        class="text-zinc-400 hover:text-zinc-600 text-lg font-mono cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="saveFuelReceive" class="px-6 py-2 space-y-4">
                    <x-ui.input
                        wire:model="receive_date"
                        label="Tanggal Penerimaan"
                        name="receive_date"
                        type="date"
                        required
                    />

                    <x-ui.input
                        wire:model="supplier_name"
                        label="Nama Supplier / Pemasok"
                        name="supplier_name"
                        required
                    />

                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input
                            wire:model="ri_do_number"
                            label="Nomor DO / Surat Jalan"
                            name="ri_do_number"
                            placeholder="Contoh: DO-PRT/26/10/009"
                        />
                        <x-ui.input
                            wire:model="vehicle_no"
                            label="No. Polisi Truk Tangki"
                            name="vehicle_no"
                            placeholder="Contoh: KT 8123 AB"
                        />
                    </div>

                    <x-ui.input
                        wire:model="volume_liters"
                        label="Volume Diterima (Liter)"
                        name="volume_liters"
                        type="text"
                        placeholder="Contoh: 1950.00"
                        suffix="Liter"
                        mono
                        required
                    />

                    <x-ui.input
                        wire:model="receive_notes"
                        label="Catatan Tambahan (Opsional)"
                        name="receive_notes"
                        placeholder="Kondisi segel tangki, density, dll."
                    />

                    <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2 pb-4">
                        <button
                            type="button"
                            wire:click="closeReceiveModal"
                            class="px-4 py-2 text-xs font-semibold rounded-md border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-xs font-semibold rounded-md bg-zinc-900 text-white hover:bg-zinc-800 transition-colors cursor-pointer shadow-xs"
                        >
                            Simpan Penerimaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL 2: CATAT PENGISIAN BBM UNIT --}}
    @if ($showUsageModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl border border-zinc-200 max-w-md w-full overflow-hidden space-y-4">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900">Catat Pengisian BBM Unit</h3>
                        <p class="text-xs text-zinc-500">Input pengisian solar Dexlite ke unit tertentu pada tanggal terpilih.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeUsageModal"
                        class="text-zinc-400 hover:text-zinc-600 text-lg font-mono cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="saveDailyFuelLog" class="px-6 py-2 space-y-4">
                    <x-ui.input
                        wire:model="usage_date"
                        label="Tanggal Pengisian"
                        name="usage_date"
                        type="date"
                        required
                    />

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Pilih Unit Armada / Alat <span class="text-red-500">*</span></label>
                        <select
                            wire:model.live="usage_fleet_id"
                            class="w-full px-3 py-2 rounded-md border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        >
                            @foreach ($this->allFleets as $fl)
                                <option value="{{ $fl->id }}">{{ $fl->unit_code }} - {{ $fl->name }}</option>
                            @endforeach
                        </select>
                        @if ($this->lastUsageFuel)
                            <span class="text-[11px] text-zinc-500 font-mono block mt-1">
                                Isi Terakhir: <strong class="text-zinc-900">{{ number_format($this->lastUsageFuel, 0) }} Liter</strong> pada {{ $this->lastUsageDate }}
                            </span>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <x-ui.input
                            wire:model.live.debounce.300ms="usage_fuel_liters"
                            label="Volume Pengisian Solar (Liter)"
                            name="usage_fuel_liters"
                            type="text"
                            placeholder="Contoh: 150.0"
                            suffix="Liter"
                            mono
                            required
                        />

                        {{-- Quick Preset Chips --}}
                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <span class="text-[10px] text-zinc-400 font-mono">Cepat:</span>
                            @if ($this->lastUsageFuel)
                                <button
                                    type="button"
                                    wire:click="setQuickFuel({{ $this->lastUsageFuel }})"
                                    class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition-colors cursor-pointer"
                                >
                                    {{ number_format($this->lastUsageFuel, 0) }} L (Sama Terakhir)
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="setQuickFuel(100)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                            >
                                100 L
                            </button>
                            <button
                                type="button"
                                wire:click="setQuickFuel(150)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                            >
                                150 L
                            </button>
                            <button
                                type="button"
                                wire:click="setQuickFuel(180)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                            >
                                180 L
                            </button>
                            <button
                                type="button"
                                wire:click="setQuickFuel(200)"
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                            >
                                200 L (Full)
                            </button>
                        </div>

                        {{-- Real-time Typo Warning Banner --}}
                        @if ($this->usageTypoWarning)
                            <div class="p-2.5 rounded-md bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-2">
                                <span class="text-amber-600 font-bold text-sm">⚠️</span>
                                <span class="font-medium">{{ $this->usageTypoWarning }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input
                            wire:model="usage_engine_oil"
                            label="Oli Mesin (Opsional)"
                            name="usage_engine_oil"
                            placeholder="0"
                            suffix="L"
                            mono
                        />
                        <x-ui.input
                            wire:model="usage_hydraulic_oil"
                            label="Oli Hidraulik (Opsional)"
                            name="usage_hydraulic_oil"
                            placeholder="0"
                            suffix="L"
                            mono
                        />
                    </div>

                    <x-ui.input
                        wire:model="usage_notes"
                        label="Keterangan (Opsional)"
                        name="usage_notes"
                        placeholder="Rute proyek, operator, dll."
                    />

                    <div class="pt-3 border-t border-zinc-100 flex items-center justify-end gap-2 pb-4">
                        <button
                            type="button"
                            wire:click="closeUsageModal"
                            class="px-4 py-2 text-xs font-semibold rounded-md border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-xs font-semibold rounded-md bg-emerald-700 text-white hover:bg-emerald-600 transition-colors cursor-pointer shadow-xs"
                        >
                            Simpan Pengisian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL 3: SHARE WHATSAPP REKAP HARIAN --}}
    @if ($showShareModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-zinc-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl border border-zinc-200 max-w-lg w-full overflow-hidden space-y-4">
                <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 flex items-center gap-2">
                            <span>📲</span>
                            <span>Share Rekap BBM Harian via WhatsApp</span>
                        </h3>
                        <p class="text-xs text-zinc-500">Kirim format laporan ringkas harian langsung ke grup WhatsApp manajemen plant.</p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeShareModal"
                        class="text-zinc-400 hover:text-zinc-600 text-lg font-mono cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <div class="px-6 py-2 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Pilih Tanggal Laporan</label>
                        <input
                            type="date"
                            wire:model.live="shareDate"
                            class="w-full px-3 py-2 rounded-md border border-zinc-300 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                        />
                    </div>

                    {{-- Live Preview Box --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Preview Format Pesan WhatsApp:</label>
                        <div class="p-3.5 rounded-lg bg-zinc-900 text-emerald-400 font-mono text-xs whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto border border-zinc-800">
{{ $this->whatsappDailyText }}
                        </div>
                    </div>

                    <div
                        x-data="{
                            copied: false,
                            copyText() {
                                navigator.clipboard.writeText(`{{ addslashes($this->whatsappDailyText) }}`);
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2500);
                            }
                        }"
                        class="pt-3 border-t border-zinc-100 flex items-center justify-between gap-2 pb-4"
                    >
                        <button
                            type="button"
                            @click="copyText()"
                            class="px-3.5 py-2 text-xs font-semibold rounded-md border border-zinc-300 hover:bg-zinc-100 text-zinc-800 transition-colors cursor-pointer flex items-center gap-1.5"
                        >
                            <span x-text="copied ? '✓ Tersalin!' : '📋 Salin Teks'"></span>
                        </button>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="closeShareModal"
                                class="px-3.5 py-2 text-xs font-semibold rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-50 transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                            <a
                                href="https://wa.me/?text={{ rawurlencode($this->whatsappDailyText) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-4 py-2 text-xs font-semibold rounded-md bg-emerald-600 hover:bg-emerald-500 text-white transition-colors cursor-pointer shadow-xs flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.768.46 3.491 1.334 5.006L2 22l5.105-1.338A10.007 10.007 0 0012.031 22C17.567 22 22 17.505 22 12.031 22 6.495 17.567 2 12.031 2zm5.834 14.156c-.244.686-1.42 1.258-1.95 1.285-.504.025-1.12-.132-3.414-1.082-2.327-.965-3.83-3.328-3.947-3.483-.117-.156-.95-1.264-.95-2.41 0-1.147.604-1.71.818-1.944.214-.233.468-.292.624-.292.156 0 .312.002.449.009.145.008.34-.055.53.402.196.471.67 1.636.729 1.753.059.117.098.254.02.41-.078.156-.117.254-.234.39-.117.137-.246.305-.351.41-.117.117-.24.244-.103.479.137.234.61 1.004 1.309 1.626.9.8 1.66 1.047 1.895 1.164.234.117.371.098.508-.059.137-.156.586-.684.742-.918.156-.234.312-.195.527-.117.215.078 1.367.645 1.602.762.234.117.39.176.449.273.059.098.059.566-.185 1.252z"/>
                                </svg>
                                <span>Buka WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
