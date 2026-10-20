<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    EKSEKUTIF & MANAJEMEN
                </span>
                <span class="text-xs text-zinc-500 font-mono">Batching Plant TBI</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Dashboard Eksekutif Real-Time
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Monitoring indikator kinerja operasional produksi, arus logistik material, dan integritas stok batching plant.
            </p>
        </div>

        {{-- Periode Filter --}}
        <div class="flex items-center gap-2">
            <select
                wire:model.live="selectedMonth"
                class="h-10 px-3 text-xs md:text-sm font-medium rounded-md border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
            >
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>

            <select
                wire:model.live="selectedYear"
                class="h-10 px-3 text-xs md:text-sm font-medium font-mono rounded-md border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900"
            >
                @for ($y = 2024; $y <= 2030; $y++)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>

            <x-ui.badge variant="zinc" size="md" class="hidden sm:inline-flex font-mono">
                Real-Time: Active
            </x-ui.badge>
        </div>
    </div>

    {{-- Peringatan Dini Stok Kritis --}}
    @if ($lowStockMaterials->isNotEmpty())
        <div class="p-4 rounded-lg bg-amber-50 border border-amber-200/90 text-amber-900 shadow-xs space-y-2">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-6 h-6 rounded-md bg-amber-500/20 text-amber-800 flex items-center justify-center shrink-0">
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

                <a
                    href="{{ route('material-receives.index') }}"
                    class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-amber-900 text-white text-xs font-medium hover:bg-amber-950 transition-colors shrink-0 shadow-xs"
                >
                    <span>Input Penerimaan Truk</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 pt-1">
                @foreach ($lowStockMaterials as $lowMat)
                    <div class="px-3 py-2 rounded bg-white/80 border border-amber-200 text-xs flex items-center justify-between">
                        <div class="font-semibold text-zinc-900 truncate pr-2">
                            {{ $lowMat['material_name'] }}
                        </div>
                        <div class="font-mono tabular-nums text-red-600 font-bold shrink-0">
                            {{ number_format((float) $lowMat['stok_teoritis'], 2, ',', '.') }} Kg
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 1. KPI Metrics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Penjualan Bulan Ini --}}
        <div class="p-5 rounded-lg bg-white border border-zinc-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Volume Penjualan SO</span>
                <div class="w-8 h-8 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ number_format($totalSalesVolume, 2, ',', '.') }} <span class="text-base font-normal text-zinc-500">m³</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1 flex items-center gap-1">
                    <span class="font-semibold text-zinc-800 font-mono">{{ $totalSalesOrders }}</span> pesanan order aktif
                </div>
            </div>
        </div>

        {{-- Card 2: Total Material Masuk --}}
        <div class="p-5 rounded-lg bg-white border border-zinc-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Material Masuk Inbound</span>
                <div class="w-8 h-8 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ number_format($totalMaterialInKg / 1000, 2, ',', '.') }} <span class="text-base font-normal text-zinc-500">Ton</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1 flex items-center gap-1">
                    <span class="font-semibold text-zinc-800 font-mono">{{ $totalReceiveTrips }}</span> tiket truk timbangan
                </div>
            </div>
        </div>

        {{-- Card 3: Status Deviasi Stok --}}
        <div class="p-5 rounded-lg bg-white border border-zinc-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Kepatuhan Stok Opname</span>
                <div class="w-8 h-8 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-emerald-700">
                    {{ $sesuaiCount }} <span class="text-xs font-normal text-zinc-500 font-sans">Material Sesuai</span>
                </div>
                <div class="flex items-center gap-1.5 mt-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $kelebihanCount }} Lebih
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-red-50 text-red-700 border border-red-200">
                        {{ $kekuranganCount }} Kurang
                    </span>
                    <span class="text-[11px] text-zinc-400">Toleransi &le; 0.5%</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Estimasi Solar Armada --}}
        <div class="p-5 rounded-lg bg-white border border-zinc-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Estimasi BBM Armada</span>
                <div class="w-8 h-8 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl lg:text-3xl font-bold font-mono tabular-nums text-zinc-900">
                    {{ number_format($totalEstSolar, 1, ',', '.') }} <span class="text-base font-normal text-zinc-500">L</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1">
                    Solar armada ready-mix delivery
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Visualisasi Data: Tabel SO Terbaru & Top Mutu Beton --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Tabel 5 Sales Order Terbaru (Col-span 2) --}}
        <div class="lg:col-span-2">
            <x-ui.card
                title="5 Sales Order Terbaru"
                description="Transaksi pesanan cor ready-mix terakhir yang tercatat di sistem."
            >
                <x-slot:action>
                    <a
                        href="{{ route('sales-orders.index') }}"
                        class="text-xs font-medium text-zinc-700 hover:text-zinc-900 flex items-center gap-1 group"
                    >
                        <span>Lihat Semua SO</span>
                        <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </a>
                </x-slot:action>

                <x-ui.table :headers="['No. SO', 'Tanggal', 'Customer', 'Lokasi Proyek', 'Mutu', 'Volume']">
                    @forelse ($latestSalesOrders as $so)
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
                            <td colspan="6" class="px-5 py-6 text-center text-zinc-500 text-sm">
                                Belum ada Sales Order yang terdaftar bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        {{-- Statistik: Top Mutu Beton Paling Sering Dipesan (Col-span 1) --}}
        <div>
            <x-ui.card
                title="Top Mutu Beton Dipesan"
                description="Peringkat mutu beton dengan volume pesanan tertinggi periode ini."
            >
                <div class="space-y-4">
                    @forelse ($topJobMixes as $index => $item)
                        @php
                            $maxVol = $topJobMixes->first()->total_volume ?? 1;
                            $pct = $maxVol > 0 ? min(100, round(((float) $item->total_volume / (float) $maxVol) * 100)) : 0;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded bg-zinc-100 text-zinc-700 font-mono font-bold text-[11px] flex items-center justify-center">
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
                        <div class="py-8 text-center text-zinc-500 text-xs">
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
                class="text-xs font-medium text-zinc-700 hover:text-zinc-900 flex items-center gap-1 group"
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
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    <td class="px-4 py-3 first:pl-5 font-semibold text-zinc-900 whitespace-nowrap text-xs">
                        {{ $row['material_name'] }}
                        <span class="text-[10px] text-zinc-400 font-mono block">{{ $row['unit'] }}</span>
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 text-xs whitespace-nowrap">
                        {{ number_format((float) $row['stok_awal'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-emerald-700 text-xs whitespace-nowrap">
                        +{{ number_format((float) $row['penerimaan'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-red-600 text-xs whitespace-nowrap">
                        -{{ number_format((float) $row['pemakaian'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-zinc-900 text-xs whitespace-nowrap bg-zinc-50/50">
                        {{ number_format((float) $row['stok_teoritis'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-blue-700 text-xs whitespace-nowrap">
                        {{ number_format((float) $row['stok_fisik'], 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 font-mono tabular-nums text-xs whitespace-nowrap {{ (float) $row['selisih'] == 0 ? 'text-zinc-500' : ((float) $row['selisih'] > 0 ? 'text-red-600 font-medium' : 'text-amber-600 font-medium') }}">
                        {{ (float) $row['selisih'] > 0 ? '-' : ((float) $row['selisih'] < 0 ? '+' : '') }}{{ number_format(abs((float) $row['selisih']), 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap">
                        <x-ui.badge :variant="$variant" size="sm">
                            {{ $row['status'] }}
                        </x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-6 text-center text-zinc-500 text-sm">
                        Belum ada ringkasan material untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
