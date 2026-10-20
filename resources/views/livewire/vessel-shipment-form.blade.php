<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    LOGISTIK & KAPAL
                </span>
                <span class="text-xs text-zinc-500 font-mono">Barge / Tongkang Material</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Pengiriman Kapal / Tongkang
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan volume muat vs volume bongkar kapal serta kalkulasi otomatis persentase susut kubikasi dan hasil uji lab.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Shrinkage Calculator: Active
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

    {{-- High Shrinkage Warning Notification --}}
    @if (session()->has('warning'))
        <div class="flex items-start gap-3 p-4 rounded-lg bg-red-50 border border-red-200 text-red-900 text-sm shadow-xs">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <div class="font-bold text-red-950">
                    Peringatan Susut Kapal Melebihi Batas Toleransi!
                </div>
                <div class="text-xs text-red-800 mt-0.5">
                    {{ session('warning') }} Segera buat Berita Acara Kerusakan/Susut Muatan (Discrepancy Report) dengan nakhoda kapal.
                </div>
            </div>
        </div>
    @endif

    {{-- Form Section --}}
    <x-ui.card
        title="Form Pencatatan Pengiriman Kapal"
        description="Masukkan volume muat dari pelabuhan asal dan volume aktual saat pembongkaran di jetty batching plant."
    >
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Tanggal Berangkat --}}
                <x-ui.input
                    wire:model="departure_date"
                    label="Tanggal Berangkat (Asal)"
                    name="departure_date"
                    type="date"
                />

                {{-- Tanggal Tiba --}}
                <x-ui.input
                    wire:model="arrival_date"
                    label="Tanggal Tiba (Jetty TBI)"
                    name="arrival_date"
                    type="date"
                />

                {{-- PO Terkait --}}
                <x-ui.select
                    wire:model="purchase_order_id"
                    label="Referensi Purchase Order (Opsional)"
                    name="purchase_order_id"
                    placeholder="-- Pilih No. PO (Jika Ada) --"
                >
                    @foreach ($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }}</option>
                    @endforeach
                </x-ui.select>

                {{-- Volume Muat --}}
                <x-ui.input
                    wire:model.live.debounce.300ms="load_volume"
                    label="Volume Muat / Bill of Lading (m³)"
                    name="load_volume"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    suffix="m³"
                    mono
                    required
                />

                {{-- Volume Bongkar --}}
                <x-ui.input
                    wire:model.live.debounce.300ms="unload_volume"
                    label="Volume Bongkar Aktual (m³)"
                    name="unload_volume"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    suffix="m³"
                    mono
                    required
                />

                {{-- Hasil Lab --}}
                <x-ui.input
                    wire:model="lab_result"
                    label="Hasil Uji Mutu / Laboratorium"
                    name="lab_result"
                    placeholder="Contoh: Lolos Uji Kadar Lumpur < 3%"
                />
            </div>

            {{-- Real-Time Inline Shrinkage Warning / Info --}}
            @if ($this->shrinkagePreview)
                @php
                    $isHigh = (float) $this->shrinkagePreview['shrinkage_percent'] > 2.0;
                @endphp
                <div class="p-3 rounded-lg border text-xs flex items-center justify-between gap-3 {{ $isHigh ? 'bg-red-50 border-red-200 text-red-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900' }}">
                    <div class="flex items-center gap-2">
                        @if ($isHigh)
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="font-bold">
                                PERINGATAN SUSUT TINGGI: Estimasi susut mencapai {{ number_format((float) $this->shrinkagePreview['shrinkage_percent'], 2, ',', '.') }}% (Selisih: {{ number_format((float) $this->shrinkagePreview['volume_diff'], 2, ',', '.') }} m³), melebihi ambang batas toleransi wajar (&le; 2.0%).
                            </span>
                        @else
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>
                                Estimasi susut dalam batas toleransi wajar: <strong class="font-mono">{{ number_format((float) $this->shrinkagePreview['shrinkage_percent'], 2, ',', '.') }}%</strong> (Selisih: {{ number_format((float) $this->shrinkagePreview['volume_diff'], 2, ',', '.') }} m³).
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end pt-3 border-t border-zinc-100">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto"
                >
                    <span wire:loading.remove wire:target="save">Simpan Pengiriman Kapal</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menghitung Susut Kapal...
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Daftar Pengiriman Kapal --}}
    <x-ui.card
        title="Riwayat Kedatangan Kapal & Tongkang"
        description="Monitoring susut material laut dan verifikasi kesesuaian volume bongkar."
    >
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                placeholder="Cari hasil lab atau no. PO..."
                prefix="🔍"
            />
        </div>

        @php
            $headers = ['Tgl Tiba', 'Tgl Berangkat', 'Ref. PO', 'Vol. Muat', 'Vol. Bongkar', 'Selisih Susut', 'Susut (%)', 'Hasil Uji Lab', 'Status'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($shipments as $shipment)
                @php
                    $shrinkage = (float) $shipment->shrinkage_percent;
                    $statusVariant = match (true) {
                        $shrinkage <= 2.0 => 'emerald',
                        $shrinkage <= 5.0 => 'amber',
                        default => 'red',
                    };
                    $statusLabel = match (true) {
                        $shrinkage <= 2.0 => 'Wajar',
                        $shrinkage <= 5.0 => 'Perhatian',
                        default => 'Susut Tinggi',
                    };
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Tgl Tiba --}}
                    <td class="px-4 py-3 first:pl-5 font-mono text-zinc-900 whitespace-nowrap">
                        {{ $shipment->arrival_date ? $shipment->arrival_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Tgl Berangkat --}}
                    <td class="px-4 py-3 font-mono text-zinc-500 whitespace-nowrap">
                        {{ $shipment->departure_date ? $shipment->departure_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Ref. PO --}}
                    <td class="px-4 py-3 font-mono text-xs text-zinc-700 whitespace-nowrap">
                        {{ $shipment->purchaseOrder->po_number ?? '-' }}
                    </td>

                    {{-- Vol. Muat --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                        {{ number_format((float) $shipment->load_volume, 2, ',', '.') }} m³
                    </td>

                    {{-- Vol. Bongkar --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-900 whitespace-nowrap">
                        {{ number_format((float) $shipment->unload_volume, 2, ',', '.') }} m³
                    </td>

                    {{-- Selisih Susut --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-semibold text-zinc-800 whitespace-nowrap">
                        {{ number_format((float) $shipment->volume_diff, 2, ',', '.') }} m³
                    </td>

                    {{-- Susut (%) --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-bold text-xs whitespace-nowrap">
                        {{ number_format((float) $shipment->shrinkage_percent, 2, ',', '.') }}%
                    </td>

                    {{-- Hasil Uji Lab --}}
                    <td class="px-4 py-3 text-xs text-zinc-600">
                        {{ $shipment->lab_result ?: '-' }}
                    </td>

                    {{-- Status Badge --}}
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap">
                        <x-ui.badge :variant="$statusVariant" size="sm">
                            {{ $statusLabel }}
                        </x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada data pengiriman kapal / tongkang.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($shipments->hasPages())
            <div class="mt-4">
                {{ $shipments->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
