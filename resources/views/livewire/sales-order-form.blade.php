<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900">
                Sales Order (SO)
            </h1>
            <p class="text-xs md:text-sm text-zinc-500 mt-1">
                Pencatatan pesanan beton cor ready-mix dan kalkulasi otomatis estimasi kebutuhan material & armada di server.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Formula: Server-Encap (Zero Exposure)
            </x-ui.badge>
        </div>
    </div>


    {{-- Form Section: Input Sales Order Baru --}}
    <x-ui.card
        title="Input Sales Order Baru"
        description="Lengkapi detail pemesanan volume beton cor dan jarak pengiriman lokasi proyek."
    >
        <form wire:submit.prevent="save" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Tanggal Pesanan --}}
                <x-ui.input
                    wire:model="order_date"
                    label="Tanggal Pesanan"
                    name="order_date"
                    type="date"
                    required
                />

                {{-- Customer Dropdown --}}
                <x-ui.select
                    wire:model="customer_id"
                    label="Customer / Rekanan"
                    name="customer_id"
                    placeholder="-- Pilih Customer --"
                    required
                >
                    @foreach ($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                    @endforeach
                </x-ui.select>

                {{-- Lokasi Proyek --}}
                <x-ui.input
                    wire:model="location"
                    label="Lokasi Proyek"
                    name="location"
                    placeholder="Contoh: Jl. Trans Sulawesi Km 15"
                    required
                />

                {{-- Mutu Beton --}}
                <x-ui.select
                    wire:model="job_mix_id"
                    label="Mutu Beton (Job Mix)"
                    name="job_mix_id"
                    placeholder="-- Pilih Mutu Beton --"
                    required
                >
                    <optgroup label="Seri K (K-Series)">
                        @foreach ($kSeriesJobMixes as $jm)
                            <option value="{{ $jm->id }}">{{ $jm->mutu_code }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Seri FC & FS (Megapascal & Flexural)">
                        @foreach ($fcFsJobMixes as $jm)
                            <option value="{{ $jm->id }}">{{ $jm->mutu_code }}</option>
                        @endforeach
                    </optgroup>
                </x-ui.select>

                {{-- Volume m3 --}}
                <x-ui.input
                    wire:model="volume_m3"
                    label="Volume Pesanan"
                    name="volume_m3"
                    type="number"
                    step="0.1"
                    min="0.1"
                    placeholder="0.0"
                    suffix="m³"
                    mono
                    required
                />

                {{-- Jarak Trip KM --}}
                <x-ui.input
                    wire:model="distance_km"
                    label="Jarak Lokasi (KM)"
                    name="distance_km"
                    type="number"
                    step="0.1"
                    min="0"
                    placeholder="0.0"
                    suffix="KM"
                    mono
                    required
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    class="w-full sm:w-auto"
                >
                    <span wire:loading.remove wire:target="save">Simpan Sales Order</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses di Server...
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Daftar Sales Order --}}
    <x-ui.card
        title="Daftar Sales Order Terdaftar"
        description="Riwayat pemesanan volume beton cor siap produksi batching plant."
    >
        {{-- Search Bar --}}
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari No. SO, customer, mutu, lokasi..."
                prefix="🔍"
                :clearable="true"
                :has-value="!empty($search)"
                loading-target="search"
            />
        </div>

        @php
            $headers = ['No. SO', 'Tanggal', 'Customer', 'Lokasi Proyek', 'Mutu Beton', 'Target Vol', 'Realisasi & Status', 'Jarak'];
            if (auth()->user()?->role === 'admin') {
                $headers[] = 'Estimasi Kebutuhan (Admin)';
            }
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($salesOrders as $so)
                @php
                    $target = (float) $so->volume_m3;
                    $delivered = (float) $so->delivered_volume_m3;
                    $soStatus = $so->status ?? 'aktif';
                    $statusVariant = match ($soStatus) {
                        'selesai' => 'emerald',
                        'parsial' => 'amber',
                        default => 'blue',
                    };
                    $statusLabel = match ($soStatus) {
                        'selesai' => 'Selesai',
                        'parsial' => 'Parsial',
                        default => 'Aktif',
                    };
                    $pct = $target > 0 ? min(100, round(($delivered / $target) * 100)) : 0;
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- No. SO --}}
                    <td class="px-4 py-3 first:pl-5 font-mono font-semibold text-zinc-900 whitespace-nowrap">
                        {{ $so->so_number }}
                    </td>

                    {{-- Tanggal --}}
                    <td class="px-4 py-3 font-mono text-zinc-600 whitespace-nowrap">
                        {{ $so->order_date ? $so->order_date->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Customer --}}
                    <td class="px-4 py-3 font-medium text-zinc-900">
                        {{ $so->customer->name ?? '-' }}
                    </td>

                    {{-- Lokasi --}}
                    <td class="px-4 py-3 text-zinc-600">
                        {{ $so->location }}
                    </td>

                    {{-- Mutu Beton --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge variant="zinc">
                            {{ $so->jobMix->mutu_code ?? '-' }}
                        </x-ui.badge>
                    </td>

                    {{-- Target Volume --}}
                    <td class="px-4 py-3 font-mono tabular-nums font-semibold text-zinc-900 whitespace-nowrap">
                        {{ number_format($target, 2, ',', '.') }} m³
                    </td>

                    {{-- Realisasi & Status --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <x-ui.badge :variant="$statusVariant" size="sm">
                                {{ $statusLabel }}
                            </x-ui.badge>
                            <span class="text-xs font-mono font-bold tabular-nums text-zinc-700">
                                {{ number_format($delivered, 2, ',', '.') }} m³
                            </span>
                        </div>
                        <div class="w-24 bg-zinc-100 rounded-full h-1 mt-1 overflow-hidden">
                            <div class="bg-zinc-800 h-1 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </td>

                    {{-- Jarak --}}
                    <td class="px-4 py-3 font-mono tabular-nums text-zinc-600 whitespace-nowrap">
                        {{ number_format((float) $so->distance_km, 1, ',', '.') }} KM
                    </td>

                    {{-- Khusus Admin: Kolom Estimasi Material Tertutup --}}
                    @if (auth()->user()?->role === 'admin')
                        <td class="px-4 py-3 last:pr-5 text-xs font-mono text-zinc-700 whitespace-nowrap">
                            <details class="cursor-pointer">
                                <summary class="text-zinc-600 hover:text-zinc-900 underline font-sans text-xs">
                                    Lihat Estimasi Server
                                </summary>
                                <div class="p-2 mt-1 rounded bg-zinc-100 text-[11px] space-y-0.5 border border-zinc-200">
                                    <div>Semen: {{ number_format((float) $so->est_semen_kg, 2) }} Kg</div>
                                    <div>Pasir Palu: {{ number_format((float) $so->est_pasir_palu_kg, 2) }} Kg</div>
                                    <div>Pasir Lokal: {{ number_format((float) $so->est_pasir_lokal_kg, 2) }} Kg</div>
                                    <div>Split 1/2: {{ number_format((float) $so->est_split_1_2_kg, 2) }} Kg</div>
                                    <div>Split 2/3: {{ number_format((float) $so->est_split_2_3_kg, 2) }} Kg</div>
                                    <div>Additive: {{ number_format((float) $so->est_additive_liter, 2) }} L</div>
                                    <div class="font-bold text-zinc-900">Solar Armada: {{ number_format((float) $so->est_solar_liter, 2) }} L</div>
                                </div>
                            </details>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Belum ada data Sales Order yang tersimpan.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($salesOrders->hasPages())
            <div class="mt-4">
                {{ $salesOrders->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
