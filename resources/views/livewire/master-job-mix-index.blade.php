<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900">
                Master Resep Adukan Beton (Job Mix)
            </h1>
            <p class="text-xs md:text-sm text-zinc-500 mt-1">
                Daftar formula baku kebutuhan material per 1 m³ beton ready-mix standar operasional TBI Batching Plant.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Total Mutu: {{ $totalAll }}
            </x-ui.badge>
            <x-ui.badge variant="blue" size="md" class="font-mono">
                K-Series: {{ $totalKSeries }}
            </x-ui.badge>
            <x-ui.badge variant="emerald" size="md" class="font-mono">
                FC/FS: {{ $totalFcFs }}
            </x-ui.badge>
        </div>
    </div>

    {{-- Main Card Container --}}
    <x-ui.card
        title="Daftar Standar Komposisi Batching (Per 1 m³)"
        description="Rasio komponen campuran semen, agregat halus pasir palu & lokal, batu split 1/2 & 2/3, admixture SBTJM2, dan air."
    >
        {{-- Search & Filter Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div class="w-full sm:max-w-xs">
                <div class="relative flex items-center w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Cari kode mutu (misal: K250, FC20, FS45)..."
                        class="w-full h-10 pl-9 pr-4 text-xs md:text-sm font-medium rounded-md border border-zinc-300 bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors"
                    />
                </div>
            </div>

            {{-- Category Filter Tabs --}}
            <div class="inline-flex items-center p-1 bg-zinc-100 rounded-lg border border-zinc-200 text-xs font-medium self-start sm:self-auto">
                <button
                    type="button"
                    wire:click="$set('selectedCategory', '')"
                    class="px-3 py-1.5 rounded-md transition-colors {{ $selectedCategory === '' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Semua ({{ $totalAll }})
                </button>
                <button
                    type="button"
                    wire:click="$set('selectedCategory', 'k_series')"
                    class="px-3 py-1.5 rounded-md transition-colors {{ $selectedCategory === 'k_series' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Seri K ({{ $totalKSeries }})
                </button>
                <button
                    type="button"
                    wire:click="$set('selectedCategory', 'fc_fs')"
                    class="px-3 py-1.5 rounded-md transition-colors {{ $selectedCategory === 'fc_fs' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900' }}"
                >
                    Seri FC & FS ({{ $totalFcFs }})
                </button>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="w-full overflow-hidden border border-zinc-200 rounded-lg bg-white shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm text-zinc-800">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-[11px] font-semibold text-zinc-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-3.5 py-3 text-center w-12 font-mono">No</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Kode Mutu</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Kategori</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Semen (Kg)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Pasir Palu (Kg)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Pasir Lokal (Kg)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Split 1/2 (Kg)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Split 2/3 (Kg)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">SBTJM2 (Liter)</th>
                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap font-mono">Air (L/Kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white font-normal">
                        @forelse ($jobMixes as $index => $jm)
                            @php
                                $isKSeries = str_starts_with($jm->mutu_code, 'K');
                            @endphp
                            <tr class="hover:bg-zinc-50/80 transition-colors">
                                {{-- No --}}
                                <td class="px-3.5 py-3 text-center font-mono text-zinc-500 text-xs">
                                    {{ $index + 1 }}
                                </td>

                                {{-- Kode Mutu --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold font-mono tracking-tight {{ $isKSeries ? 'bg-zinc-100 text-zinc-900 border border-zinc-300' : 'bg-emerald-50 text-emerald-800 border border-emerald-300' }}">
                                        {{ $jm->mutu_code }}
                                    </span>
                                </td>

                                {{-- Kategori --}}
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-600">
                                    {{ $jm->category_name }}
                                </td>

                                {{-- Semen --}}
                                <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-zinc-900 whitespace-nowrap">
                                    {{ number_format((float) $jm->semen_kg, 2, ',', '.') }}
                                </td>

                                {{-- Pasir Palu --}}
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                                    {{ number_format((float) $jm->pasir_palu_kg, 2, ',', '.') }}
                                </td>

                                {{-- Pasir Lokal --}}
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                                    {{ number_format((float) $jm->pasir_lokal_kg, 2, ',', '.') }}
                                </td>

                                {{-- Split 1/2 --}}
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                                    {{ number_format((float) $jm->split_1_2_kg, 2, ',', '.') }}
                                </td>

                                {{-- Split 2/3 --}}
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                                    {{ number_format((float) $jm->split_2_3_kg, 2, ',', '.') }}
                                </td>

                                {{-- SBTJM2 (Admixture) --}}
                                <td class="px-4 py-3 text-right font-mono font-semibold tabular-nums text-zinc-900 whitespace-nowrap">
                                    {{ number_format((float) $jm->additive_liter, 2, ',', '.') }}
                                </td>

                                {{-- Air --}}
                                <td class="px-4 py-3 text-right font-mono tabular-nums text-zinc-600 whitespace-nowrap">
                                    {{ number_format((float) $jm->water_liter, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-12 text-center text-zinc-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-9 h-9 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-400">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-zinc-900">
                                            Tidak ada resep mutu beton yang sesuai
                                        </p>
                                        <p class="text-xs text-zinc-500">
                                            Coba kata kunci pencarian lain atau pilih kategori resep berbeda.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Note Section --}}
        <div class="mt-4 p-3 rounded-md bg-zinc-50 border border-zinc-200 text-xs text-zinc-500 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-zinc-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="space-y-0.5">
                <p class="font-medium text-zinc-700">Informasi Formula Batching & Toleransi Ready-Mix:</p>
                <p>
                    Nilai di atas merupakan racikan material dasar per 1 m³ beton. Pada modul Sales Order dan Produksi Harian, sistem secara otomatis mengenkapsulasi faktor waste sebesar 5% (1.0500) untuk mengantisipasi susut di perjalanan dan kelekatan material di mixer.
                </p>
            </div>
        </div>
    </x-ui.card>
</div>
