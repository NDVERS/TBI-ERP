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
            <button
                type="button"
                wire:click="openConversionModal"
                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-md bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-2xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <span>🧪 Olah Lab: Konversi Additive &rarr; SBTJM2</span>
            </button>
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Density-Engine: Auto-Converted
            </x-ui.badge>
        </div>
    </div>


    {{-- Form Penerimaan Truk --}}
    <x-ui.card
        title="Form Catat Penerimaan Material Truk"
        description="Lengkapi detail surat jalan / nomor RI, volume kubikasi, dan berat jenis (density) material yang masuk."
    >
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Referensi No. PO (Opsional) --}}
                <div class="md:col-span-3">
                    <x-ui.select
                        wire:model.live="purchase_order_id"
                        label="Referensi No. PO (Opsional / Bila dari Pembongkaran Tongkang PO)"
                        name="purchase_order_id"
                        placeholder="-- Bukan dari PO / Armada Lokal Mandiri --"
                    >
                        @foreach ($activePOs as $activePo)
                            <option value="{{ $activePo->id }}">
                                [{{ $activePo->po_number }}] - {{ $activePo->supplier->name ?? 'Supplier' }} ({{ $activePo->material_type_input ?: ($activePo->material->name ?? 'Material') }} - {{ number_format((float) $activePo->qty_ordered, 2, ',', '.') }} {{ $activePo->unit }}) — Status: {{ $activePo->status }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    @if ($purchase_order_id)
                        <div class="mt-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-lg bg-zinc-50 border border-zinc-200">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-bold text-zinc-900">PO Terhubung:</span>
                                <span class="text-zinc-600">Supplier dan Material otomatis terisi dari PO terpilih.</span>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-900 select-none bg-white px-2.5 py-1.5 rounded border border-zinc-200 shadow-2xs hover:bg-zinc-100 transition-colors">
                                <input
                                    type="checkbox"
                                    wire:model="is_final_unloading"
                                    class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 h-4 w-4"
                                />
                                <span>Tandai Pengiriman PO Ini Selesai Seluruhnya (Muatan Terakhir / Final Unloading)</span>
                            </label>
                        </div>
                    @endif
                </div>

                {{-- Tanggal Penerimaan --}}
                <x-ui.input
                    wire:model="receive_date"
                    label="Tanggal Penerimaan"
                    name="receive_date"
                    type="date"
                    required
                />

                {{-- Nomor RI / Tiket --}}
                <x-ui.input
                    wire:model="ri_no"
                    label="Nomor RI / Tiket"
                    name="ri_no"
                    placeholder="Contoh: RI-2610-001"
                    mono
                    required
                />

                {{-- No. Polisi Truk --}}
                <div>
                    <x-ui.input
                        wire:model="vehicle_no"
                        label="No. Polisi Truk"
                        name="vehicle_no"
                        placeholder="{{ $isInternalMaterial ? 'INTERNAL' : 'Contoh: DN 8888 BB' }}"
                        mono
                        :disabled="$isInternalMaterial"
                        required
                    />
                    @if ($isInternalMaterial)
                        <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                            *Produksi internal lab TBI (Non-Ekspedisi: INTERNAL).
                        </p>
                    @endif
                </div>

                {{-- Supplier / Vendor --}}
                <div>
                    <x-ui.select
                        wire:model="supplier_id"
                        label="Supplier / Vendor"
                        name="supplier_id"
                        placeholder="-- Pilih Supplier --"
                        :disabled="$isInternalMaterial"
                        required
                    >
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </x-ui.select>
                    @if ($isInternalMaterial)
                        <p class="mt-1 text-[11px] text-zinc-500 font-mono">
                            *Otomatis terkunci ke TBI Plant (Produksi Internal Lab).
                        </p>
                    @endif
                </div>

                {{-- Material Diterima --}}
                <x-ui.select
                    wire:model.live="material_id"
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

                {{-- Density / Berat Jenis (kg/m3) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700">
                            Density (kg/m³) <span class="text-rose-500">*</span>
                        </label>
                        @if ($masterDensity !== null)
                            <div class="flex items-center gap-1.5 text-[11px]">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-zinc-100 text-zinc-700 border border-zinc-200">
                                    Master: {{ number_format($masterDensity, 2, ',', '.') }}
                                </span>
                                @if ((float) str_replace(',', '.', (string) $density) !== (float) $masterDensity)
                                    <button
                                        type="button"
                                        wire:click="resetDensityToMaster"
                                        class="text-zinc-500 hover:text-zinc-900 underline text-[10px]"
                                        title="Kembalikan ke nilai master data"
                                    >
                                        Reset
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                    <x-ui.input
                        wire:model.live.debounce.300ms="density"
                        name="density"
                        type="text"
                        placeholder="Contoh: 1658"
                        suffix="kg/m³"
                        mono
                        required
                    />
                    <p class="mt-1 text-[11px] text-zinc-500">
                        Otomatis terisi dari master, dapat diedit manual jika density armada bervariasi.
                    </p>
                </div>

                {{-- Volume m3 --}}
                <div>
                    <x-ui.input
                        wire:model.live.debounce.300ms="volume_m3"
                        label="Volume Masuk (m³)"
                        name="volume_m3"
                        type="text"
                        placeholder="0.00"
                        suffix="m³"
                        mono
                        required
                    />
                    <p class="mt-1 text-[11px] text-zinc-500">
                        Mendukung pemisah desimal koma (,) atau titik (.).
                    </p>
                </div>

                {{-- Real-Time Calculation Preview Card --}}
                <div class="md:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50/70 p-3.5 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                        <span class="font-medium flex items-center gap-1.5 text-zinc-700">
                            <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Kalkulasi Otomatis Berat Aktual (Volume × Density)
                        </span>
                        <span class="font-mono text-[11px] text-zinc-400">Live Server Preview</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2 pt-1 border-t border-zinc-200/60">
                        <div class="font-mono text-xs text-zinc-600 flex items-center gap-1.5">
                            <span>{{ $volume_m3 ? number_format((float) str_replace(',', '.', (string) $volume_m3), 2, ',', '.') : '0,00' }} m³</span>
                            <span class="text-zinc-400">×</span>
                            <span>{{ $density ? number_format((float) str_replace(',', '.', (string) $density), 2, ',', '.') : '0,00' }} kg/m³</span>
                            <span class="text-zinc-400">=</span>
                        </div>

                        <div class="text-right">
                            <span class="font-mono tabular-nums text-xl md:text-2xl font-bold text-zinc-900">
                                {{ number_format($this->totalWeight, 2, ',', '.') }}
                            </span>
                            <span class="text-xs font-semibold text-zinc-600 ml-1">Kg</span>
                            <span class="text-xs text-zinc-500 ml-2 font-mono">({{ number_format($this->totalWeight / 1000, 2, ',', '.') }} Ton)</span>
                        </div>
                    </div>
                </div>
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
                :clearable="true"
                :has-value="!empty($search)"
                loading-target="search"
            />
        </div>

        @php
            $headers = ['Tanggal', 'No. RI', 'Ref. PO', 'No. Polisi', 'Supplier', 'Material', 'Volume (m³)', 'Density (kg/m³)', 'Total Berat (Kg)'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($receives as $rec)
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Tanggal --}}
                    <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                        <div>{{ $rec->receive_date ? $rec->receive_date->format('d/m/Y') : '-' }}</div>
                        <div class="text-[11px] text-zinc-400 font-mono">{{ $rec->created_at ? $rec->created_at->format('H:i') : '' }}</div>
                    </td>

                    {{-- No. RI --}}
                    <td class="px-4 py-3 font-mono font-semibold text-zinc-900 whitespace-nowrap">
                        {{ $rec->ri_no }}
                    </td>

                    {{-- Ref. PO --}}
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                        @if ($rec->purchaseOrder)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-800 border border-zinc-300">
                                {{ $rec->purchaseOrder->po_number }}
                            </span>
                        @else
                            <span class="text-zinc-400">-</span>
                        @endif
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

                    {{-- Density Digunakan --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 whitespace-nowrap">
                        {{ number_format((float) $rec->density_used, 2, ',', '.') }} kg/m³
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

    {{-- Riwayat Konversi Lab: Additive -> SBTJM2 --}}
    @if ($recentConversions->isNotEmpty())
        <x-ui.card
            title="Riwayat Konversi Lab: Additive (Kg) → SBTJM2 (Liter)"
            description="Log formulasi pengolahan bahan kimia internal laboratorium batching plant TBI."
        >
            @php
                $convHeaders = ['Batch Lab', 'Tanggal Olah', 'Additive Digunakan (-)', 'Hasil SBTJM2 (+)', 'No. RI Penerimaan', 'Petugas / Dicatat Oleh', 'Catatan'];
            @endphp

            <x-ui.table :headers="$convHeaders">
                @foreach ($recentConversions as $conv)
                    <tr class="hover:bg-zinc-50/80 transition-colors">
                        <td class="px-4 py-3 first:pl-5 font-mono font-semibold text-xs text-zinc-900 whitespace-nowrap">
                            {{ $conv->conversion_no }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600 whitespace-nowrap">
                            {{ $conv->conversion_date ? $conv->conversion_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-red-600 font-semibold tabular-nums whitespace-nowrap">
                            -{{ number_format((float) $conv->additive_used_kg, 2, ',', '.') }} Kg
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-emerald-700 font-bold tabular-nums whitespace-nowrap">
                            +{{ number_format((float) $conv->sbtjm2_produced_liter, 2, ',', '.') }} Liter
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-800 whitespace-nowrap">
                            {{ $conv->materialReceive->ri_no ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 whitespace-nowrap">
                            {{ $conv->creator->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3 last:pr-5 text-xs text-zinc-500 max-w-xs truncate">
                            {{ $conv->notes ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    @endif

    {{-- Modal Konversi Lab (Additive Kg -> SBTJM2 Liter) --}}
    @if ($showConversionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <!-- Backdrop Zinc Anti-Slop -->
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-xs transition-opacity" wire:click="closeConversionModal"></div>

            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl border border-zinc-200 overflow-hidden p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-md bg-zinc-100 text-zinc-900 font-mono text-sm">🧪</span>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900">Produksi Internal Lab: Konversi Additive</h3>
                                <p class="text-xs text-zinc-500">Formulasi pengolahan Additive mentah (Kg) menjadi cairan siap pakai SBTJM2 (Liter).</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeConversionModal"
                            class="text-zinc-400 hover:text-zinc-600 p-1 rounded-md hover:bg-zinc-100 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveConversion" class="space-y-4">
                        {{-- Tanggal Mixing / Olah --}}
                        <x-ui.input
                            wire:model="conv_date"
                            label="Tanggal Mixing / Olah Lab"
                            name="conv_date"
                            type="date"
                            required
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Jumlah Bahan Baku Additive Digunakan --}}
                            <div>
                                <x-ui.input
                                    wire:model="conv_additive_kg"
                                    label="Bahan Baku Additive Digunakan"
                                    name="conv_additive_kg"
                                    suffix="Kg"
                                    placeholder="Contoh: 150"
                                    type="number"
                                    step="0.01"
                                    required
                                />
                                <span class="text-[11px] text-zinc-400 mt-1 block">
                                    *Memotong stok teoritis Additive
                                </span>
                            </div>

                            {{-- Hasil Jadi SBTJM2 Diproduksi --}}
                            <div>
                                <x-ui.input
                                    wire:model="conv_sbtjm2_liter"
                                    label="Hasil Jadi SBTJM2 Diproduksi"
                                    name="conv_sbtjm2_liter"
                                    suffix="Liter"
                                    placeholder="Contoh: 150"
                                    type="number"
                                    step="0.01"
                                    required
                                />
                                <span class="text-[11px] text-zinc-400 mt-1 block">
                                    *Menambah stok teoritis SBTJM2
                                </span>
                            </div>
                        </div>

                        {{-- Keterangan / Batch Racikan Lab --}}
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 mb-1">
                                Keterangan / Batch Racikan Lab
                            </label>
                            <textarea
                                wire:model="conv_notes"
                                rows="2"
                                class="w-full rounded-md border border-zinc-300 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-zinc-900"
                                placeholder="Contoh: Batch racikan lab #04, formula retarder slump tinggi..."
                            ></textarea>
                        </div>

                        {{-- Box Transparansi Audit Mutasi --}}
                        <div class="p-3 rounded-md bg-zinc-50 border border-zinc-200 text-xs text-zinc-600 space-y-1">
                            <div class="font-semibold text-zinc-800 flex items-center gap-1.5">
                                <span>⚙️</span> Dampak Mutasi Otomatis:
                            </div>
                            <div>• Memotong stok teoritis <strong class="text-zinc-900">Additive</strong> sebesar Kg yang digunakan (dicatat sebagai pemakaian internal).</div>
                            <div>• Menambah stok teoritis <strong class="text-zinc-900">SBTJM2</strong> sebesar Liter hasil jadi (supplier: <strong class="text-zinc-900">TBI Plant</strong>, armada: <strong class="text-zinc-900">INTERNAL</strong>).</div>
                        </div>

                        {{-- Modal Action Buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200">
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                wire:click="closeConversionModal"
                            >
                                Batal
                            </x-ui.button>
                            <x-ui.button
                                type="submit"
                                variant="primary"
                                size="sm"
                            >
                                Simpan & Eksekusi Mutasi
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
