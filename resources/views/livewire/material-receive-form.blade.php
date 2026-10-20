<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    LOGISTIK & GUDANG
                </span>
                <span class="text-xs text-zinc-500 font-mono">Inbound Truck Scale</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Penerimaan Material Truk
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan kedatangan armada truk material mentah dan konversi otomatis volume ke berat kilogram di server.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Density-Engine: Auto-Converted
            </x-ui.badge>
        </div>
    </div>

    {{-- Success Notification --}}
    @if (session()->has('success'))
        <div class="flex items-center gap-3 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="font-medium">
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Form Penerimaan Truk --}}
    <x-ui.card
        title="Form Catat Penerimaan Material Truk"
        description="Lengkapi detail surat jalan / nomor RI dan volume kubikasi material yang masuk."
    >
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Tanggal --}}
                <x-ui.input
                    wire:model="receive_date"
                    label="Tanggal Penerimaan"
                    name="receive_date"
                    type="date"
                    required
                />

                {{-- Jam Masuk --}}
                <x-ui.input
                    wire:model="time_in"
                    label="Jam Masuk"
                    name="time_in"
                    type="time"
                    required
                />

                {{-- No. RI --}}
                <x-ui.input
                    wire:model="ri_no"
                    label="Nomor RI / Tiket"
                    name="ri_no"
                    placeholder="Contoh: RI-2610-001"
                    mono
                    required
                />

                {{-- No. Polisi Truk --}}
                <x-ui.input
                    wire:model="vehicle_no"
                    label="No. Polisi Truk"
                    name="vehicle_no"
                    placeholder="Contoh: DN 8888 BB"
                    mono
                    required
                />

                {{-- Supplier --}}
                <div class="sm:col-span-2">
                    <x-ui.select
                        wire:model="supplier_id"
                        label="Supplier / Vendor"
                        name="supplier_id"
                        placeholder="-- Pilih Supplier --"
                        required
                    >
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                {{-- Material --}}
                <x-ui.select
                    wire:model="material_id"
                    label="Material Diterima"
                    name="material_id"
                    placeholder="-- Pilih Material --"
                    required
                >
                    @foreach ($materials as $mat)
                        <option value="{{ $mat->id }}">
                            {{ $mat->name }} ({{ $mat->unit }})
                        </option>
                    @endforeach
                </x-ui.select>

                {{-- Volume m3 --}}
                <x-ui.input
                    wire:model="volume_m3"
                    label="Volume Masuk (m³)"
                    name="volume_m3"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    suffix="m³"
                    mono
                    required
                />
            </div>

            <div class="flex items-center justify-end pt-3 border-t border-zinc-100">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto"
                >
                    <span wire:loading.remove wire:target="save">Simpan Penerimaan Material</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menghitung & Menyimpan...
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Daftar Penerimaan Truk --}}
    <x-ui.card
        title="Riwayat Penerimaan Material Truk"
        description="Data logistik material masuk yang telah terekam dan terkonversi ke berat kilogram."
    >
        {{-- Search Bar --}}
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                placeholder="Cari No. RI, No. Truk, supplier, material..."
                prefix="🔍"
            />
        </div>

        @php
            $headers = ['Tanggal', 'Jam', 'No. RI', 'No. Polisi', 'Supplier', 'Material', 'Volume (m³)', 'Total Berat (Kg)'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($receives as $rec)
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Tanggal --}}
                    <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                        {{ $rec->receive_date ? $rec->receive_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Jam --}}
                    <td class="px-4 py-3 font-mono text-zinc-600 whitespace-nowrap">
                        {{ $rec->time_in ? substr($rec->time_in, 0, 5) : '-' }}
                    </td>

                    {{-- No. RI --}}
                    <td class="px-4 py-3 font-mono font-semibold text-zinc-900 whitespace-nowrap">
                        {{ $rec->ri_no }}
                    </td>

                    {{-- No. Truk --}}
                    <td class="px-4 py-3 font-mono text-zinc-700 whitespace-nowrap">
                        {{ $rec->vehicle_no }}
                    </td>

                    {{-- Supplier --}}
                    <td class="px-4 py-3 font-medium text-zinc-900">
                        {{ $rec->supplier->name ?? '-' }}
                    </td>

                    {{-- Material --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge variant="zinc">
                            {{ $rec->material->name ?? '-' }}
                        </x-ui.badge>
                    </td>

                    {{-- Volume m3 --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-900 whitespace-nowrap">
                        {{ number_format((float) $rec->volume_m3, 2, ',', '.') }} m³
                    </td>

                    {{-- Total Berat Kg --}}
                    <td class="px-4 py-3 last:pr-5 font-mono tabular-nums font-bold text-zinc-900 whitespace-nowrap">
                        {{ number_format((float) $rec->total_weight_kg, 2, ',', '.') }} Kg
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada catatan penerimaan material truk.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($receives->hasPages())
            <div class="mt-4">
                {{ $receives->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
