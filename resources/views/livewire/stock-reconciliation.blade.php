<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    ADMIN AUDIT
                </span>
                <span class="text-xs text-zinc-500 font-mono">Batching Plant TBI</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Rekonsiliasi Stok Material
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Audit otomatis perbandingan stok buku teoritis vs hasil opname fisik lapangan. Toleransi deviasi &le; 0.5%.
            </p>
        </div>

        {{-- Filter Periode & Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Quick Presets --}}
            <div class="inline-flex rounded-md border border-zinc-200 bg-zinc-100 p-0.5 text-xs font-medium">
                <button
                    type="button"
                    wire:click="setThisMonth"
                    class="px-2.5 py-1.5 rounded transition-all {{ $selectedMonth === now()->month && $selectedYear === now()->year ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Bulan Ini
                </button>
                <button
                    type="button"
                    wire:click="setLastMonth"
                    class="px-2.5 py-1.5 rounded transition-all {{ $selectedMonth === now()->subMonth()->month && $selectedYear === now()->subMonth()->year ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Bulan Lalu
                </button>
            </div>

            {{-- Month Selector --}}
            <select
                wire:model.live="selectedMonth"
                class="h-10 px-3 text-xs md:text-sm font-medium rounded-md border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
            >
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>

            {{-- Year Selector --}}
            <select
                wire:model.live="selectedYear"
                class="h-10 px-3 text-xs md:text-sm font-medium font-mono rounded-md border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
            >
                @for ($y = 2024; $y <= 2030; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>

            {{-- Export Excel Button --}}
            <button
                type="button"
                wire:click="exportExcel"
                class="inline-flex items-center gap-1.5 h-10 px-3.5 rounded-md bg-zinc-900 text-white text-xs md:text-sm font-medium hover:bg-zinc-800 transition-colors shadow-xs"
                title="Download Format Excel (.xlsx) Rekap Stok & Deviasi Material"
            >
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export Excel</span>
            </button>

            {{-- Print Button --}}
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-1.5 h-10 px-3.5 rounded-md border border-zinc-300 bg-white text-zinc-700 text-xs md:text-sm font-medium hover:bg-zinc-50 transition-colors shadow-xs"
                title="Cetak Halaman"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak</span>
            </button>
        </div>
    </div>

    {{-- KPI Metric Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Card Total --}}
        <div class="p-4 rounded-lg bg-white border border-zinc-200 shadow-xs">
            <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Total Material Diaudit</span>
            <div class="text-2xl font-bold font-mono tabular-nums text-zinc-900 mt-1">
                {{ $reconciliations->count() }}
            </div>
            <span class="text-[11px] text-zinc-400 mt-0.5 block">Material aktif di sistem</span>
        </div>

        {{-- Card BELUM DIOPNAME --}}
        <div class="p-4 rounded-lg bg-white border border-zinc-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-600 uppercase tracking-wider">Belum Diopname</span>
                <span class="w-2.5 h-2.5 rounded-full bg-zinc-400"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-zinc-700 mt-1">
                {{ $belumDiopnameCount }}
            </div>
            <span class="text-[11px] text-zinc-400 mt-0.5 block">Perlu verifikasi fisik lapangan</span>
        </div>

        {{-- Card SESUAI --}}
        <div class="p-4 rounded-lg bg-white border border-emerald-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Status Sesuai</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-emerald-700 mt-1">
                {{ $sesuaiCount }}
            </div>
            <span class="text-[11px] text-emerald-600/80 mt-0.5 block">Deviasi &le; 0.5% (Toleransi OK)</span>
        </div>

        {{-- Card KELEBIHAN --}}
        <div class="p-4 rounded-lg bg-white border border-amber-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Kelebihan Fisik</span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-amber-700 mt-1">
                {{ $kelebihanCount }}
            </div>
            <span class="text-[11px] text-amber-600/80 mt-0.5 block">Fisik > Teoritis (> +0.5%)</span>
        </div>

        {{-- Card KEKURANGAN --}}
        <div class="p-4 rounded-lg bg-white border border-red-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-red-700 uppercase tracking-wider">Kekurangan Fisik</span>
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-red-700 mt-1">
                {{ $kekuranganCount }}
            </div>
            <span class="text-[11px] text-red-600/80 mt-0.5 block">Fisik < Teoritis (> -0.5%)</span>
        </div>
    </div>

    {{-- Peringatan Deviasi Opname Terlalu Jauh (> 2%) --}}
    @if ($highDeviationCount > 0)
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-950 shadow-xs flex items-start gap-3">
            <div class="w-6 h-6 rounded-md bg-red-500/20 text-red-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-xs md:text-sm font-bold text-red-950">
                    Peringatan Audit: {{ $highDeviationCount }} Material Mengalami Deviasi Opname Tinggi (&gt; &plusmn;2.0%)
                </h4>
                <p class="text-[11px] md:text-xs text-red-800 mt-0.5">
                    Selisih fisik opname lapangan terhadap stok buku melebihi batas kewajaran toleransi. Disarankan segera melakukan investigasi sampling kalibrasi timbangan dan cek kebocoran batching plant.
                </p>
            </div>
        </div>
    @endif

    {{-- Tabel Rekonsiliasi --}}
    <x-ui.card
        title="Tabel Rekapitulasi Stok Periode {{ $months[$selectedMonth] }} {{ $selectedYear }}"
        description="Hasil kalkulasi stok akhir teoritis dari pergerakan material dibandingkan stok fisik opname terkini."
    >
        {{-- Search Input & Density Toggle --}}
        <div class="mb-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="w-full sm:max-w-sm">
                <x-ui.input
                    wire:model.live.debounce.250ms="search"
                    placeholder="Cari nama material atau status..."
                    prefix="🔍"
                />
            </div>
            <div>
                <x-ui.table-density-toggle />
            </div>
        </div>

        @php
            $headers = [
                'Material',
                'Stok Awal',
                'Penerimaan (+)',
                'Pemakaian (-)',
                'Stok Teoritis',
                'Stok Fisik Opname',
                'Deviasi Selisih',
                'Deviasi (%)',
                'Status',
            ];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($reconciliations as $row)
                @php
                    $status = $row['status'];
                    $variant = match ($status) {
                        'SESUAI' => 'emerald',
                        'KELEBIHAN' => 'amber',
                        'KEKURANGAN' => 'red',
                        default => 'zinc',
                    };
                    $hasOpname = ! empty($row['has_opname']);
                    $isAnomaly = $hasOpname && (float) ($row['persentase_selisih'] ?? 0) > 2.0;
                @endphp
                <tr class="{{ $isAnomaly ? 'bg-red-50/70 border-l-4 border-l-red-600 hover:bg-red-50' : 'hover:bg-zinc-50/80' }} transition-colors">
                    {{-- Nama Material --}}
                    <td class="px-4 py-3 first:pl-5 font-semibold text-zinc-900 whitespace-nowrap">
                        @if (strcasecmp($row['material_name'], 'Additive') === 0)
                            <div class="flex items-center gap-2">
                                <span>{{ $row['material_name'] }}</span>
                                <button
                                    type="button"
                                    @click="$dispatch('open-additive-modal')"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-zinc-100 hover:bg-zinc-200 text-zinc-800 border border-zinc-200 transition-colors shadow-2xs"
                                    title="Klik untuk melihat rincian 4 serbuk kimia Additive"
                                >
                                    <span>🔍 Detail Powder</span>
                                </button>
                            </div>
                        @else
                            {{ $row['material_name'] }}
                        @endif
                        <span class="text-[11px] font-mono text-zinc-400 font-normal block">
                            Satuan: {{ $row['unit'] }}
                        </span>
                    </td>

                    {{-- Stok Awal --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                        {{ number_format((float) $row['stok_awal'], 2, ',', '.') }}
                    </td>

                    {{-- Total Penerimaan --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                        +{{ number_format((float) $row['penerimaan'], 2, ',', '.') }}
                    </td>

                    {{-- Total Pemakaian --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-red-600 whitespace-nowrap">
                        -{{ number_format((float) $row['pemakaian'], 2, ',', '.') }}
                    </td>

                    {{-- Stok Akhir Teoritis --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-zinc-900 whitespace-nowrap bg-zinc-50/60">
                        {{ number_format((float) $row['stok_teoritis'], 2, ',', '.') }}
                    </td>

                    {{-- Stok Fisik Opname --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-bold whitespace-nowrap {{ $hasOpname ? 'text-blue-700' : 'text-zinc-400' }}">
                        @if ($hasOpname)
                            {{ number_format((float) $row['stok_fisik'], 2, ',', '.') }}
                        @else
                            <span class="font-normal text-xs text-zinc-400 italic">Belum Diopname</span>
                        @endif
                    </td>

                    {{-- Deviasi Selisih --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-medium whitespace-nowrap {{ ! $hasOpname ? 'text-zinc-400' : ((float) $row['selisih'] == 0 ? 'text-zinc-500' : ((float) $row['selisih'] > 0 ? 'text-red-600' : 'text-amber-600')) }}">
                        @if ($hasOpname)
                            {{ (float) $row['selisih'] > 0 ? '-' : ((float) $row['selisih'] < 0 ? '+' : '') }}{{ number_format(abs((float) $row['selisih']), 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>

                    {{-- Persentase Selisih --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 whitespace-nowrap text-xs font-semibold">
                        @if ($hasOpname)
                            {{ number_format((float) $row['persentase_selisih'], 2, ',', '.') }}%
                        @else
                            -
                        @endif
                    </td>

                    {{-- Status Badge & Anomali Indicator --}}
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <x-ui.badge :variant="$variant" size="sm">
                                {{ $row['status'] }}
                            </x-ui.badge>
                            @if ($isAnomaly)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold font-mono bg-red-100 text-red-800 border border-red-300">
                                    ANOMALI (&gt;2%)
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Tidak ada data rekonsiliasi yang cocok dengan pencarian.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div class="mt-4 p-3 rounded-lg bg-zinc-50 border border-zinc-200 text-xs text-zinc-500 space-y-1">
            <div class="font-semibold text-zinc-700">Keterangan Ambang Batas (PRD Compliance):</div>
            <div>• <strong class="text-zinc-700">BELUM DIOPNAME:</strong> Belum ada entri opname fisik yang dicatat surveyor pada bulan berjalan (eliminasi status semu).</div>
            <div>• <strong class="text-emerald-700">SESUAI:</strong> Deviasi persentase &le; 0.50% terhadap stok teoritis (rentang toleransi wajar susut/timbang).</div>
            <div>• <strong class="text-amber-700">KELEBIHAN:</strong> Stok fisik opname lapangan lebih besar dari perhitungan buku teoritis (> +0.50%).</div>
            <div>• <strong class="text-red-700">KEKURANGAN:</strong> Stok fisik opname lapangan lebih kecil dari perhitungan buku teoritis (> -0.50%).</div>
        </div>
    </x-ui.card>
</div>
