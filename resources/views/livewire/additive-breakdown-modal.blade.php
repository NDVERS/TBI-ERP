<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="close">
            {{-- Backdrop Zinc Anti-Slop --}}
            <div
                class="fixed inset-0 bg-zinc-950/40 backdrop-blur-xs transition-opacity"
                wire:click="close"
            ></div>

            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl border border-zinc-200 overflow-hidden p-6 space-y-4">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-900 font-mono text-sm">
                                🧪
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900">
                                    Detail Serbuk Kimia Additive
                                </h3>
                                <p class="text-xs text-zinc-500">
                                    Breakdown 4 komponen serbuk kimia aktif pembentuk Admixture SBTJM2
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="close"
                            class="text-zinc-400 hover:text-zinc-600 p-1.5 rounded-md hover:bg-zinc-100 transition-colors"
                            aria-label="Tutup"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Summary Ringkasan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 rounded-md bg-zinc-50 border border-zinc-200 flex flex-col justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Total Sisa Serbuk Kimia (Additive)
                            </span>
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-2xl font-bold font-mono tabular-nums text-zinc-900">
                                    {{ number_format($totalPowderStock, 2, ',', '.') }}
                                </span>
                                <span class="text-xs font-semibold text-zinc-600 font-mono">Kg</span>
                            </div>
                            <span class="text-[11px] text-zinc-500 mt-0.5">
                                Terkonsolidasi dalam pos master <strong>Additive</strong>
                            </span>
                        </div>

                        <div class="p-3 rounded-md bg-zinc-50 border border-zinc-200 flex flex-col justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                Resep Standar Batch Mixing Lab
                            </span>
                            <div class="mt-1 flex items-baseline gap-1.5 font-mono text-xs text-zinc-800">
                                <span class="font-bold text-zinc-900">1.825 Kg</span>
                                <span class="text-zinc-500">Powder (36.5%)</span>
                                <span class="text-zinc-400">+</span>
                                <span class="font-bold text-zinc-900">3.175 L</span>
                                <span class="text-zinc-500">Air</span>
                            </div>
                            <span class="text-[11px] text-zinc-500 mt-0.5">
                                Menghasilkan <strong>5.000 Liter</strong> Admixture Siap Pakai (SBTJM2)
                            </span>
                        </div>
                    </div>

                    {{-- Tabel Breakdown Powder --}}
                    <div class="overflow-x-auto rounded-md border border-zinc-200">
                        <table class="w-full text-left text-xs divide-y divide-zinc-200">
                            <thead class="bg-zinc-50 text-zinc-600 font-semibold font-mono uppercase text-[11px]">
                                <tr>
                                    <th class="px-3 py-2.5">Bahan Kimia</th>
                                    <th class="px-3 py-2.5 text-right">Resep (5.000 L)</th>
                                    <th class="px-3 py-2.5 text-right">Rasio</th>
                                    <th class="px-3 py-2.5 text-right">Sisa Stok (Kg)</th>
                                    <th class="px-3 py-2.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white">
                                @forelse ($powders as $powder)
                                    @php
                                        $stock = (float) $powder->stock_kg;
                                        $recipe = (float) $powder->recipe_quantity_kg;
                                        $isLow = $stock < $recipe;
                                    @endphp
                                    <tr class="hover:bg-zinc-50/70 transition-colors">
                                        <td class="px-3 py-2.5">
                                            <div class="font-semibold text-zinc-900">{{ $powder->name }}</div>
                                            <div class="text-[10px] font-mono text-zinc-400">{{ $powder->code }} • {{ $powder->notes }}</div>
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono tabular-nums text-zinc-700 whitespace-nowrap">
                                            {{ number_format($recipe, 2, ',', '.') }} Kg
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono tabular-nums text-zinc-600 whitespace-nowrap">
                                            {{ number_format((float) $powder->standard_percentage, 1, ',', '.') }}%
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono tabular-nums font-bold text-zinc-900 whitespace-nowrap">
                                            {{ number_format($stock, 2, ',', '.') }} Kg
                                        </td>
                                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                            @if ($stock <= 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800 border border-red-200">
                                                    Habis
                                                </span>
                                            @elseif ($isLow)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                    &lt; 1 Batch
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    Tersedia
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-zinc-500">
                                            Data serbuk kimia belum diinisialisasi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Info Audit Box --}}
                    <div class="p-3 rounded-md bg-zinc-50 border border-zinc-200 text-xs text-zinc-600 flex items-start gap-2">
                        <span class="text-sm">ℹ️</span>
                        <div>
                            Semua serbuk kimia ini disimpan dan dihitung otomatis saat proses <strong>Olah Lab: Konversi Additive &rarr; SBTJM2</strong> pada modul Penerimaan Material. Pemakaian aktual akan langsung memotong sisa stok powder masing-masing.
                        </div>
                    </div>

                    {{-- Modal Actions --}}
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-200">
                        <a
                            href="{{ route('material-receives.index') }}"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-700 hover:text-zinc-900 underline"
                        >
                            <span>Buka Modul Olah Lab &rarr;</span>
                        </a>

                        <button
                            type="button"
                            wire:click="close"
                            class="px-4 py-2 text-xs font-semibold rounded-md border border-zinc-300 bg-white hover:bg-zinc-50 text-zinc-800 shadow-2xs transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
