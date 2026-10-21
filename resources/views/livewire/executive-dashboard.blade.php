<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-zinc-900 text-white font-mono tracking-tight">
                    EKSEKUTIF & MANAJEMEN
                </span>
                <span class="text-xs text-zinc-500 font-mono">• Batching Plant TBI</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Dashboard Eksekutif Real-Time
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Monitoring indikator kinerja operasional produksi, arus logistik material, dan integritas stok batching plant.
            </p>
        </div>

        {{-- Periode Filter --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Quick Presets --}}
            <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-100 p-0.5 text-xs font-medium shadow-2xs">
                <button
                    type="button"
                    wire:click="setThisMonth"
                    class="px-2.5 py-1.5 rounded-md transition-all {{ $selectedMonth === now()->month && $selectedYear === now()->year ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Bulan Ini
                </button>
                <button
                    type="button"
                    wire:click="setLastMonth"
                    class="px-2.5 py-1.5 rounded-md transition-all {{ $selectedMonth === now()->subMonth()->month && $selectedYear === now()->subMonth()->year ? 'bg-white font-bold text-zinc-900 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Bulan Lalu
                </button>
            </div>

            <select
                wire:model.live="selectedMonth"
                class="h-9 px-3 text-xs md:text-sm font-medium rounded-lg border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
            >
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>

            <select
                wire:model.live="selectedYear"
                class="h-9 px-3 text-xs md:text-sm font-medium font-mono rounded-lg border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 shadow-2xs"
            >
                @for ($y = 2024; $y <= 2030; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>

            <div class="hidden sm:inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50/70 text-emerald-800 text-xs font-mono font-medium shadow-2xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Real-Time: Active</span>
            </div>
        </div>
    </div>

    {{-- Peringatan Dini Stok Kritis --}}
    @if ($lowStockMaterials->isNotEmpty())
        <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-200/90 text-amber-950 shadow-xs space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0 border border-amber-300/40">
                        <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs md:text-sm font-bold tracking-tight text-amber-950">
                            Peringatan Dini: {{ $lowStockMaterials->count() }} Material Berada di Bawah Stok Kritis (&lt; {{ number_format($lowStockThresholdKg, 0, ',', '.') }} Kg)
                        </h4>
                        <p class="text-[11px] md:text-xs text-amber-800/90 mt-0.5">
                            Stok buku teoritis mendekati batas minimum operasional batching plant. Segera lakukan pengadaan atau input penerimaan material.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                    <a
                        href="{{ route('purchase-orders.index') }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-semibold transition-colors shadow-xs"
                    >
                        <span>+ Terbitkan PO</span>
                    </a>
                    <a
                        href="{{ route('material-receives.index') }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-amber-300/80 bg-white hover:bg-amber-100/50 text-amber-950 text-xs font-semibold transition-colors shadow-2xs"
                    >
                        <span>Input Penerimaan</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 pt-1">
                @foreach ($lowStockMaterials as $lowMat)
                    <div class="px-3.5 py-2.5 rounded-lg bg-white/90 border border-amber-200/80 text-xs flex items-center justify-between shadow-2xs">
                        <div class="flex items-center gap-2 truncate pr-2">
                            <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                            <span class="font-semibold text-zinc-900 truncate">
                                {{ $lowMat['material_name'] }}
                            </span>
                        </div>
                        <div class="font-mono tabular-nums text-red-600 font-bold shrink-0">
                            {{ number_format((float) $lowMat['stok_teoritis'], 2, ',', '.') }} {{ $lowMat['unit'] ?? 'Kg' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 1. KPI Metrics Cards (4 Equal-Height Balanced Cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Produksi Kumulatif Hari Ini --}}
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-xs flex flex-col justify-between hover:border-zinc-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Kumulatif Cor Hari Ini</span>
                <div class="w-8 h-8 rounded-lg bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ number_format($todayProductionVolume, 2, ',', '.') }} <span class="text-base font-normal text-zinc-500">m³</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1.5 flex items-center gap-1">
                    <span class="font-semibold text-zinc-800 font-mono">{{ $todayDocketCount }}</span> tiket docket diterbitkan hari ini
                </div>
            </div>
        </div>

        {{-- Card 2: Inbound Logistik PO Terdekat --}}
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-xs flex flex-col justify-between hover:border-zinc-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">PO Logistik Terdekat</span>
                <div class="w-8 h-8 rounded-lg bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ $nearestPOs->count() }} <span class="text-base font-normal text-zinc-500">PO Aktif</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1.5 truncate" title="{{ $nearestPOs->first()?->vessel_name ?: ($nearestPOs->first()?->po_number ?? 'Belum ada PO aktif') }}">
                    @if ($nearestPOs->isNotEmpty())
                        <span class="font-semibold text-zinc-800 font-mono">{{ $nearestPOs->first()->po_number }}</span>
                        <span class="text-zinc-400">•</span>
                        <span>{{ $nearestPOs->first()->vessel_name ?: ($nearestPOs->first()->supplier->name ?? '-') }}</span>
                    @else
                        Tidak ada pengapalan aktif
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Status Deviasi Stok --}}
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-xs flex flex-col justify-between hover:border-zinc-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Kepatuhan Stok Opname</span>
                <div class="w-8 h-8 rounded-lg bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums {{ $sesuaiCount > 0 ? 'text-emerald-700' : 'text-zinc-900' }}">
                    {{ $sesuaiCount }} <span class="text-xs font-normal text-zinc-500 font-sans">dari {{ $quickStockMaterials->count() }} Sesuai</span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $kelebihanCount }} Lebih
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-red-50 text-red-700 border border-red-200">
                        {{ $kekuranganCount }} Kurang
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-zinc-100 text-zinc-600 border border-zinc-200">
                        {{ $belumDiopnameCount }} Belum Diopname
                    </span>
                </div>
            </div>
        </div>

        {{-- Card 4: Estimasi Solar Armada --}}
        <div class="p-5 rounded-xl bg-white border border-zinc-200 shadow-xs flex flex-col justify-between hover:border-zinc-300 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Estimasi BBM Armada</span>
                <div class="w-8 h-8 rounded-lg bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ number_format($totalEstSolar, 1, ',', '.') }} <span class="text-base font-normal text-zinc-500">L</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1.5">
                    Solar armada ready-mix delivery
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Visualisasi Operasional & Logistik --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Tabel 5 Sales Order Rencana Cor Besok (H+1) (Col-span 2) --}}
        <div class="lg:col-span-2">
            <x-ui.card
                title="Jadwal Cor Besok (H+1)"
                description="Rencana pengiriman beton ready-mix untuk persiapan armada dan mutu material."
            >
                <x-slot:action>
                    <a
                        href="{{ route('sales-orders.index') }}"
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 flex items-center gap-1 group transition-colors"
                    >
                        <span>Lihat Semua SO</span>
                        <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </a>
                </x-slot:action>

                <x-ui.table :headers="['No. SO', 'Tanggal Rencana', 'Customer', 'Lokasi Proyek', 'Mutu', 'Volume']">
                    @forelse ($tomorrowSalesOrders as $so)
                        <tr class="hover:bg-zinc-50/80 transition-colors">
                            <td class="px-4 py-3 first:pl-5 font-mono font-semibold text-zinc-900 text-xs whitespace-nowrap">
                                {{ $so->so_number }}
                            </td>
                            <td class="px-4 py-3 font-mono text-zinc-600 text-xs whitespace-nowrap">
                                {{ $so->order_date ? $so->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 text-xs">
                                {{ $so->customer->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 text-xs truncate max-w-xs">
                                {{ $so->location }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs">
                                <x-ui.badge variant="zinc" size="sm">
                                    {{ $so->jobMix->mutu_code ?? '-' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 last:pr-5 font-mono tabular-nums font-bold text-zinc-900 text-xs whitespace-nowrap">
                                {{ number_format((float) $so->volume_m3, 2, ',', '.') }} m³
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-zinc-500 text-xs">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <svg class="w-8 h-8 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-medium text-zinc-600">Belum ada Sales Order terjadwal untuk besok.</span>
                                    <span class="text-zinc-400 text-[11px]">Jadwal pengiriman cor harian akan otomatis terisi saat pesanan dikonfirmasi.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        {{-- Kolom Kanan: Detail Inbound Logistik Kapal & Top Mutu Beton (Col-span 1) --}}
        <div class="space-y-6">
            {{-- Monitoring Inbound Logistik Kapal --}}
            <x-ui.card
                title="Logistik Pengapalan Aktif"
                description="Status posisi dan estimasi tiba tongkang/kapal material."
            >
                <x-slot:action>
                    <a
                        href="{{ route('purchase-orders.index') }}"
                        class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 flex items-center gap-1 group transition-colors"
                        title="Buka Modul PO"
                    >
                        <span>Kelola PO</span>
                        <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </a>
                </x-slot:action>

                <div class="space-y-2.5">
                    @forelse ($nearestPOs as $po)
                        @php
                            $statusVariant = match ($po->status) {
                                'Sudah Tiba di Pelabuhan' => 'emerald',
                                'Penerimaan Sebagian' => 'blue',
                                'Dalam Perjalanan' => 'amber',
                                'Sudah Dimuat' => 'indigo',
                                default => 'zinc',
                            };

                            $arrivalDate = $po->est_arrival_date ?? $po->actual_arrival_date;
                            $etaLabel = '-';
                            if ($arrivalDate) {
                                $diffDays = (int) now()->startOfDay()->diffInDays($arrivalDate->copy()->startOfDay(), false);
                                if ($diffDays === 0) {
                                    $etaText = 'Hari ini';
                                } elseif ($diffDays === 1) {
                                    $etaText = 'Besok';
                                } elseif ($diffDays > 1) {
                                    $etaText = "{$diffDays} hr lagi";
                                } else {
                                    $absDays = abs($diffDays);
                                    $etaText = "{$absDays} hr lalu";
                                }
                                $etaLabel = $arrivalDate->format('d/m') . " ({$etaText})";
                            }
                        @endphp
                        <div class="p-3 rounded-lg bg-zinc-50/80 border border-zinc-200/80 hover:border-zinc-300 transition-colors text-xs">
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="font-mono font-bold text-zinc-900 truncate">
                                    {{ $po->po_number }}
                                </span>
                                <x-ui.badge :variant="$statusVariant" size="sm" class="text-[10px] px-1.5 py-0 font-medium">
                                    {{ $po->status }}
                                </x-ui.badge>
                            </div>
                            <div class="flex items-center justify-between mt-1.5 text-[11px] text-zinc-600">
                                <span class="font-semibold text-zinc-800 truncate" title="{{ $po->vessel_name ?: ($po->supplier->name ?? '-') }}">
                                    🚢 {{ $po->vessel_name ?: ($po->supplier->name ?? '-') }}
                                </span>
                                <span class="font-mono font-bold text-zinc-900 tabular-nums shrink-0">
                                    {{ number_format((float) $po->qty_ordered, 0, ',', '.') }} {{ $po->unit }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-1 text-[10px] text-zinc-400 font-mono pt-1 border-t border-zinc-200/50">
                                <span class="truncate text-zinc-500">{{ $po->material_type_input ?: ($po->material->name ?? '-') }}</span>
                                <span class="text-zinc-700 font-semibold shrink-0">ETA: {{ $etaLabel }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-zinc-400 text-xs">
                            Belum ada PO pengapalan aktif saat ini.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            {{-- Statistik: Top Mutu Beton Paling Sering Dipesan --}}
            <x-ui.card
                title="Top Mutu Beton Dipesan"
                description="Peringkat mutu beton dengan akumulasi volume tertinggi periode ini."
            >
                <div class="space-y-3.5">
                    @forelse ($topJobMixes as $index => $item)
                        @php
                            $maxVol = $topJobMixes->first()->total_volume ?? 1;
                            $pct = $maxVol > 0 ? min(100, round(((float) $item->total_volume / (float) $maxVol) * 100)) : 0;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md bg-zinc-100 text-zinc-800 font-mono font-bold text-[11px] flex items-center justify-center border border-zinc-200/60">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="font-bold text-zinc-900">
                                        {{ $item->jobMix->mutu_code ?? 'Mutu #' . $item->job_mix_id }}
                                    </span>
                                </div>
                                <div class="font-mono tabular-nums font-semibold text-zinc-800">
                                    {{ number_format((float) $item->total_volume, 1, ',', '.') }} m³
                                    <span class="text-zinc-400 font-normal">({{ $item->total_orders }}x)</span>
                                </div>
                            </div>
                            <div class="w-full bg-zinc-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-zinc-900 h-1.5 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-zinc-400 text-xs">
                            Belum ada statistik pemesanan mutu beton bulan ini.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- 3. Tabel Ringkasan Cepat Stok & Deviasi --}}
    <x-ui.card
        title="Ringkasan Status Stok & Deviasi Material Utama"
        description="Hasil rekonsiliasi stok fisik terkini terhadap akumulasi penerimaan dan pemakaian."
    >
        <x-slot:action>
            <a
                href="{{ route('reconciliation.index') }}"
                class="text-xs font-semibold text-zinc-700 hover:text-zinc-900 flex items-center gap-1 group transition-colors"
            >
                <span>Buka Rekonsiliasi Lengkap</span>
                <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
            </a>
        </x-slot:action>

        @php
            $headers = ['Material', 'Stok Awal', 'Penerimaan (+)', 'Pemakaian (-)', 'Stok Akhir Teoritis', 'Stok Fisik Opname', 'Deviasi', 'Status'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($quickStockMaterials as $row)
                @php
                    $status = $row['status'];
                    $variant = match ($status) {
                        'SESUAI' => 'emerald',
                        'KELEBIHAN' => 'amber',
                        'KEKURANGAN' => 'red',
                        default => 'zinc',
                    };
                    $hasOpname = ! empty($row['has_opname']);
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    <td class="px-4 py-3 first:pl-5 font-semibold text-zinc-900 whitespace-nowrap text-xs">
                        @if (strcasecmp($row['material_name'], 'Additive') === 0)
                            <div class="flex items-center gap-1.5">
                                <span>{{ $row['material_name'] }}</span>
                                <button
                                    type="button"
                                    @click="$dispatch('open-additive-modal')"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-zinc-100 hover:bg-zinc-200 text-zinc-800 border border-zinc-200 transition-colors shadow-2xs"
                                    title="Klik untuk melihat rincian 4 serbuk kimia Additive"
                                >
                                    <span>🔍 Powder</span>
                                </button>
                            </div>
                        @else
                            {{ $row['material_name'] }}
                        @endif
                        <span class="text-[10px] text-zinc-400 font-mono block">Satuan: {{ $row['unit'] }}</span>
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 text-xs whitespace-nowrap">
                        {{ number_format((float) $row['stok_awal'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-emerald-700 text-xs whitespace-nowrap font-medium">
                        +{{ number_format((float) $row['penerimaan'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-red-600 text-xs whitespace-nowrap font-medium">
                        -{{ number_format((float) $row['pemakaian'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-zinc-900 text-xs whitespace-nowrap bg-zinc-50/60">
                        {{ number_format((float) $row['stok_teoritis'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-xs whitespace-nowrap {{ $hasOpname ? 'text-blue-700' : 'text-zinc-400' }}">
                        @if ($hasOpname)
                            {{ number_format((float) $row['stok_fisik'], 2, ',', '.') }}
                        @else
                            <span class="font-normal text-[11px] text-zinc-400 italic">Belum Diopname</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-xs whitespace-nowrap {{ ! $hasOpname ? 'text-zinc-400' : ((float) $row['selisih'] == 0 ? 'text-zinc-500' : ((float) $row['selisih'] > 0 ? 'text-red-600 font-semibold' : 'text-amber-600 font-semibold')) }}">
                        @if ($hasOpname)
                            {{ (float) $row['selisih'] > 0 ? '-' : ((float) $row['selisih'] < 0 ? '+' : '') }}{{ number_format(abs((float) $row['selisih']), 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap">
                        <x-ui.badge :variant="$variant" size="sm">
                            {{ $row['status'] }}
                        </x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada ringkasan material untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
