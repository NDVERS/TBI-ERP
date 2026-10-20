<x-layouts.app title="Dashboard Operasional" header="Dashboard Operasional Batching Plant">
    <x-slot:action>
        @auth
            <a href="{{ route('sales-orders.index') }}">
                <x-ui.button variant="outline" size="sm">
                    Sales Order
                </x-ui.button>
            </a>
            <a href="{{ route('material-receives.index') }}">
                <x-ui.button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Penerimaan Material
                </x-ui.button>
            </a>
        @else
            <x-ui.button
                variant="primary"
                size="sm"
                @click="$dispatch('open-auth-modal')"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Input Data Baru
            </x-ui.button>
        @endauth
    </x-slot:action>

    <div
        x-data="{
            drawerOpen: false,
            selected: null,
            materials: {{ Js::from($safeMaterials) }},
            openMaterial(id) {
                this.selected = this.materials.find(m => m.id === id) || null;
                if (this.selected) {
                    this.drawerOpen = true;
                }
            }
        }"
        class="flex flex-col gap-6 max-w-7xl mx-auto"
    >
        <!-- Top Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.card padding="p-4 md:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Total Material</span>
                    <x-ui.badge variant="zinc" size="sm">10 Jenis</x-ui.badge>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-zinc-900 font-mono tabular-nums">
                        {{ $materials->count() }}
                    </span>
                    <span class="text-xs text-zinc-500">Item Aktif</span>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-4 md:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Master Supplier</span>
                    <x-ui.badge variant="blue" size="sm">Terdaftar</x-ui.badge>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-zinc-900 font-mono tabular-nums">
                        {{ $supplierCount }}
                    </span>
                    <span class="text-xs text-zinc-500">Mitra Pemasok</span>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-4 md:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Master Customer</span>
                    <x-ui.badge variant="emerald" size="sm">Aktif</x-ui.badge>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-zinc-900 font-mono tabular-nums">
                        {{ $customerCount }}
                    </span>
                    <span class="text-xs text-zinc-500">Perusahaan & Proyek</span>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-4 md:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Resep Job Mix</span>
                    <x-ui.badge variant="zinc" size="sm">K100 - K350</x-ui.badge>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold tracking-tight text-zinc-900 font-mono tabular-nums">
                        {{ $jobMixCount }}
                    </span>
                    <span class="text-xs text-zinc-500">Formula Terkunci</span>
                </div>
            </x-ui.card>
        </div>

        <!-- Filter & Search Section (Demo of Input & Select) -->
        <x-ui.card title="Pencarian & Filter Cepat Material" description="Gunakan filter responsif yang dioptimasi untuk tablet lapangan maupun laptop.">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    label="Cari Nama Material"
                    placeholder="Contoh: Semen, Pasir Palu..."
                    prefix="🔍"
                />

                <x-ui.select label="Filter Satuan" placeholder="Semua Satuan">
                    <option value="Kg">Kg (Kilogram)</option>
                    <option value="Liter">Liter</option>
                </x-ui.select>

                <div class="flex items-end gap-2">
                    <x-ui.button variant="primary" class="flex-1">
                        Terapkan Filter
                    </x-ui.button>
                    <x-ui.button variant="outline">
                        Reset
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- Master Materials Table Section (Responsive Hybrid Table with Drawer) -->
        <x-ui.card
            title="Daftar Material Utama & Stok Akhir Teoritis"
            description="Klik baris material untuk melihat rincian spesifikasi teknis dan ketersediaan stok teoritis di slide-over drawer."
        >
            <x-slot:action>
                <span class="text-xs text-zinc-400 font-mono hidden sm:inline-flex items-center gap-1">
                    <span>💡 Tap baris untuk detail</span>
                </span>
            </x-slot:action>

            @php
                $tableHeaders = [
                    ['label' => 'No', 'class' => 'w-12 text-center'],
                    ['label' => 'Nama Material', 'class' => ''],
                    ['label' => 'Satuan', 'class' => 'hidden md:table-cell'],
                    ['label' => 'Berat Jenis (kg/m³)', 'class' => 'hidden md:table-cell'],
                    ['label' => 'Stok Akhir Teoritis', 'class' => 'text-right md:text-left'],
                    ['label' => 'Keterangan', 'class' => 'hidden lg:table-cell'],
                    ['label' => 'Status', 'class' => 'hidden sm:table-cell'],
                    ['label' => '', 'class' => 'w-10 text-right md:hidden'],
                ];
            @endphp

            <x-ui.table :headers="$tableHeaders">
                @forelse ($materials as $index => $material)
                    <tr
                        @click="openMaterial({{ $material->id }})"
                        class="hover:bg-zinc-100/70 active:bg-zinc-200/80 cursor-pointer transition-colors group"
                    >
                        {{-- 1. No --}}
                        <td class="px-4 py-3.5 first:pl-5 font-mono tabular-nums text-xs text-zinc-500 text-center">
                            {{ $index + 1 }}
                        </td>

                        {{-- 2. Nama Material & Mobile Meta --}}
                        <td class="px-4 py-3.5 font-semibold text-zinc-900">
                            <div class="flex items-center gap-2">
                                <span>{{ $material->name }}</span>
                                <span class="md:hidden text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 border border-zinc-200">
                                    {{ $material->unit }}
                                </span>
                            </div>
                            <div class="md:hidden text-[11px] text-zinc-400 font-mono mt-0.5 flex items-center gap-1.5">
                                @if (($material->theoretical_stock_kg ?? $material->initial_stock_kg) > 0)
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>Tersedia</span>
                                @else
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span>Nihil</span>
                                @endif
                            </div>
                        </td>

                        {{-- 3. Satuan (Hidden on mobile) --}}
                        <td class="px-4 py-3.5 text-zinc-600 font-mono text-xs hidden md:table-cell">
                            <span class="px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200">
                                {{ $material->unit }}
                            </span>
                        </td>

                        {{-- 4. Berat Jenis (Hidden on mobile, Admin Only) --}}
                        <td class="px-4 py-3.5 font-mono tabular-nums text-zinc-800 hidden md:table-cell">
                            @if ($isAdmin)
                                {{ $material->density_kg_m3 ? number_format($material->density_kg_m3, 2, ',', '.') : '-' }}
                            @else
                                <span class="text-xs text-zinc-400 font-mono italic">Terkunci (Admin Only)</span>
                            @endif
                        </td>

                        {{-- 5. Stok Akhir Teoritis --}}
                        <td class="px-4 py-3.5 font-mono tabular-nums font-semibold text-zinc-900 whitespace-nowrap text-right md:text-left">
                            {{ number_format($material->theoretical_stock_kg ?? $material->initial_stock_kg, 2, ',', '.') }}
                            <span class="md:hidden text-zinc-400 font-normal text-xs">{{ $material->unit }}</span>
                        </td>

                        {{-- 6. Keterangan (Hidden on mobile/tablet) --}}
                        <td class="px-4 py-3.5 text-zinc-500 text-xs hidden lg:table-cell">
                            {{ $material->notes ?? '-' }}
                        </td>

                        {{-- 7. Status (Hidden on mobile) --}}
                        <td class="px-4 py-3.5 whitespace-nowrap hidden sm:table-cell">
                            @if (($material->theoretical_stock_kg ?? $material->initial_stock_kg) > 0)
                                <x-ui.badge variant="emerald" dot size="sm">Tersedia</x-ui.badge>
                            @else
                                <x-ui.badge variant="amber" dot size="sm">Nihil</x-ui.badge>
                            @endif
                        </td>

                        {{-- 8. Mobile Chevron Indicator --}}
                        <td class="px-3 py-3.5 last:pr-4 text-right md:hidden text-zinc-400 group-hover:text-zinc-900 transition-colors">
                            <svg class="w-4 h-4 ml-auto inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-500 text-sm">
                            Belum ada data material. Silakan jalankan `php artisan db:seed`.
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <!-- Material Detail Slide-Over Drawer -->
        <x-ui.drawer
            model="drawerOpen"
            maxWidth="max-w-md"
        >
            <x-slot:title>
                <span x-text="selected ? selected.name : 'Detail Material'"></span>
            </x-slot:title>

            <x-slot:description>
                Spesifikasi teknis dan stok operasional batching plant.
            </x-slot:description>

            <template x-if="selected">
                <div class="space-y-5">
                    <!-- Status & Unit Header Card -->
                    <div class="p-4 rounded-lg bg-zinc-50 border border-zinc-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-zinc-500 font-medium">Satuan:</span>
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-white border border-zinc-200 text-zinc-800" x-text="selected.unit"></span>
                        </div>
                        <div>
                            <template x-if="selected.has_stock">
                                <x-ui.badge variant="emerald" dot size="sm">Tersedia</x-ui.badge>
                            </template>
                            <template x-if="!selected.has_stock">
                                <x-ui.badge variant="amber" dot size="sm">Nihil</x-ui.badge>
                            </template>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="space-y-3">
                        <!-- Stok Akhir Teoritis -->
                        <div class="p-3.5 rounded-lg border border-zinc-200 bg-white shadow-2xs">
                            <span class="text-xs font-medium text-zinc-500 block uppercase tracking-wider">
                                Stok Akhir Teoritis
                            </span>
                            <div class="text-xl font-bold font-mono tabular-nums text-zinc-900 mt-1">
                                <span x-text="selected.theoretical_stock_formatted"></span>
                                <span class="text-sm font-normal text-zinc-500" x-text="selected.unit"></span>
                            </div>
                        </div>

                        <!-- Berat Jenis (Densitas) -->
                        <div class="p-3.5 rounded-lg border border-zinc-200 bg-white shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                    Berat Jenis / Densitas
                                </span>
                                <template x-if="selected.is_density_locked">
                                    <span class="text-[10px] text-zinc-400 font-mono italic">Role: Admin Only</span>
                                </template>
                            </div>
                            <div class="mt-1">
                                <template x-if="!selected.is_density_locked && selected.density_formatted">
                                    <div class="text-lg font-bold font-mono tabular-nums text-zinc-900">
                                        <span x-text="selected.density_formatted"></span>
                                        <span class="text-xs font-normal text-zinc-500">kg/m³</span>
                                    </div>
                                </template>
                                <template x-if="selected.is_density_locked">
                                    <div class="flex items-center gap-2 text-zinc-500 text-xs font-mono py-1">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span class="italic text-zinc-400 font-semibold">Terkunci (Admin Only)</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div class="p-3.5 rounded-lg border border-zinc-200 bg-white shadow-2xs">
                            <span class="text-xs font-medium text-zinc-500 block uppercase tracking-wider">
                                Keterangan & Catatan Material
                            </span>
                            <p class="text-xs text-zinc-700 mt-1 leading-relaxed" x-text="selected.notes || '-'"></p>
                        </div>
                    </div>

                    <!-- Quick Action Links -->
                    <div class="pt-2">
                        @auth
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <a href="{{ route('material-receives.index') }}" class="w-full">
                                    <x-ui.button variant="primary" class="w-full text-xs">
                                        Penerimaan Truk
                                    </x-ui.button>
                                </a>
                                <a href="{{ route('sales-orders.index') }}" class="w-full">
                                    <x-ui.button variant="outline" class="w-full text-xs">
                                        Sales Order
                                    </x-ui.button>
                                </a>
                            </div>
                        @else
                            <x-ui.button
                                variant="primary"
                                class="w-full text-xs"
                                @click="drawerOpen = false; $dispatch('open-auth-modal')"
                            >
                                Masuk untuk Input Transaksi
                            </x-ui.button>
                        @endauth
                    </div>
                </div>
            </template>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="drawerOpen = false"
                >
                    Tutup
                </x-ui.button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
</x-layouts.app>
