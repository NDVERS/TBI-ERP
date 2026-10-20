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

    <div class="flex flex-col gap-6 max-w-7xl mx-auto">
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

        <!-- Master Materials Table Section (Demo of Table & Badge) -->
        <x-ui.card
            title="Daftar 10 Material Utama & Stok Awal Riil"
            description="Data riil dari Sheet Data Master (TBI - ERP.xlsx). Kolom densitas bersifat rahasia dan hanya terlihat oleh role admin."
        >
            <x-ui.table :headers="['No', 'Nama Material', 'Satuan', 'Berat Jenis (kg/m³)', 'Stok Awal Bulan', 'Keterangan', 'Status']">
                @forelse ($materials as $index => $material)
                    <tr class="hover:bg-zinc-50/70 transition-colors">
                        <td class="px-4 py-3.5 first:pl-5 font-mono tabular-nums text-xs text-zinc-500">
                            {{ $index + 1 }}
                        </td>
                        <td class="px-4 py-3.5 font-semibold text-zinc-900">
                            {{ $material->name }}
                        </td>
                        <td class="px-4 py-3.5 text-zinc-600 font-mono text-xs">
                            <span class="px-2 py-0.5 rounded bg-zinc-100 border border-zinc-200">
                                {{ $material->unit }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 font-mono tabular-nums text-zinc-800">
                            @if (auth()->user()?->role === 'admin')
                                {{ $material->density_kg_m3 ? number_format($material->density_kg_m3, 2, ',', '.') : '-' }}
                            @else
                                <span class="text-xs text-zinc-400 font-mono italic">Terkunci (Admin Only)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 font-mono tabular-nums font-semibold text-zinc-900">
                            {{ number_format($material->initial_stock_kg, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-zinc-500 text-xs">
                            {{ $material->notes ?? '-' }}
                        </td>
                        <td class="px-4 py-3.5 last:pr-5">
                            @if ($material->initial_stock_kg > 0)
                                <x-ui.badge variant="emerald" dot size="sm">Tersedia</x-ui.badge>
                            @else
                                <x-ui.badge variant="amber" dot size="sm">Nihil</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500 text-sm">
                            Belum ada data material. Silakan jalankan `php artisan db:seed`.
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</x-layouts.app>
