<x-layouts.app title="404 — Halaman Tidak Ditemukan">
    <div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center space-y-6">
            {{-- Icon & Badge Status --}}
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-zinc-100 border border-zinc-200 text-zinc-800 mx-auto shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="space-y-2">
                <span class="inline-flex items-center font-mono text-xs font-semibold px-2.5 py-0.5 rounded border border-zinc-300 bg-zinc-100 text-zinc-700">
                    HTTP 404 · NOT FOUND
                </span>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900">
                    Halaman Tidak Ditemukan
                </h1>
                <p class="text-xs sm:text-sm text-zinc-500 max-w-sm mx-auto leading-relaxed">
                    {{ $exception->getMessage() ?: 'Alamat URL yang Anda tuju tidak ditemukan atau telah dipindahkan ke tautan lain.' }}
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5">
                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'manajemen', 'demo']))
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                            <x-ui.button variant="primary" size="sm" class="w-full justify-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Ke Dashboard Eksekutif
                            </x-ui.button>
                        </a>
                    @else
                        <a href="{{ url('/') }}" class="w-full sm:w-auto">
                            <x-ui.button variant="primary" size="sm" class="w-full justify-center">
                                Ke Halaman Utama
                            </x-ui.button>
                        </a>
                    @endif
                @else
                    <a href="{{ url('/') }}" class="w-full sm:w-auto">
                        <x-ui.button variant="primary" size="sm" class="w-full justify-center">
                            Ke Halaman Utama
                        </x-ui.button>
                    </a>
                @endauth

                <x-ui.button
                    type="button"
                    variant="outline"
                    size="sm"
                    onclick="window.history.back()"
                    class="w-full sm:w-auto"
                >
                    Kembali ke Halaman Sebelumnya
                </x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.app>
