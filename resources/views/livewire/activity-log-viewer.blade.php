<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    SECURITY & AUDIT
                </span>
                <span class="text-xs text-zinc-500 font-mono">System Audit Trail</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Log Aktivitas & Audit Trail
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pencatatan kronologis otomatis seluruh tindakan operasional, autentikasi pengguna, dan mutasi data transaksi.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.badge variant="zinc" size="md" class="font-mono">
                Immutable Event Log
            </x-ui.badge>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-lg bg-white border border-zinc-200 shadow-xs">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Rekaman Audit</span>
            <div class="text-2xl font-bold font-mono tabular-nums text-zinc-900 mt-1">
                {{ number_format($totalLogs) }}
            </div>
            <span class="text-[11px] text-zinc-400 mt-0.5 block">Seluruh event tercatat di database</span>
        </div>

        <div class="p-4 rounded-lg bg-white border border-blue-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-blue-700 uppercase tracking-wider">Sesi Login Masuk</span>
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-blue-700 mt-1">
                {{ number_format($loginLogs) }}
            </div>
            <span class="text-[11px] text-blue-600/80 mt-0.5 block">Autentikasi akun berhasil</span>
        </div>

        <div class="p-4 rounded-lg bg-white border border-emerald-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Mutasi Transaksi Data</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-2xl font-bold font-mono tabular-nums text-emerald-700 mt-1">
                {{ number_format($mutationLogs) }}
            </div>
            <span class="text-[11px] text-emerald-600/80 mt-0.5 block">Penambahan, ubah, dan hapus data</span>
        </div>
    </div>

    {{-- Filter Panel --}}
    <x-ui.card
        title="Filter & Pencarian Log"
        description="Saring data riwayat berdasarkan jenis tindakan, user bersangkutan, atau rentang tanggal kejadian."
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search Text --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-zinc-700 mb-1">Cari Deskripsi / User / IP</label>
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Contoh: Sales Order, Palu, 127.0.0.1..."
                    prefix="🔍"
                    :clearable="true"
                    :has-value="!empty($search)"
                    loading-target="search"
                />
            </div>

            {{-- Action Filter --}}
            <div>
                <label class="block text-xs font-medium text-zinc-700 mb-1">Jenis Aksi</label>
                <select
                    wire:model.live="actionFilter"
                    class="w-full h-10 px-3 rounded-md border border-zinc-300 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                >
                    <option value="">Semua Aksi</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="created">Created (Tambah)</option>
                    <option value="updated">Updated (Ubah)</option>
                    <option value="deleted">Deleted (Hapus)</option>
                </select>
            </div>

            {{-- User Filter --}}
            <div>
                <label class="block text-xs font-medium text-zinc-700 mb-1">Pengguna</label>
                <select
                    wire:model.live="userFilter"
                    class="w-full h-10 px-3 rounded-md border border-zinc-300 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                >
                    <option value="">Semua Pengguna</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Reset Button --}}
            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="w-full h-10 px-3 rounded-md border border-zinc-300 text-xs font-medium text-zinc-700 hover:bg-zinc-100 transition-colors"
                >
                    Reset Filter
                </button>
            </div>
        </div>

        {{-- Date Range Sub-filter --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-zinc-100">
            <div>
                <label class="block text-xs font-medium text-zinc-600 mb-1">Dari Tanggal</label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full h-10 px-3 rounded-md border border-zinc-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                />
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-600 mb-1">Sampai Tanggal</label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full h-10 px-3 rounded-md border border-zinc-300 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                />
            </div>
        </div>
    </x-ui.card>

    {{-- Tabel Audit Trail --}}
    <x-ui.card
        title="Catatan Audit Kronologis"
        description="Data log bersifat append-only dan tidak dapat dimanipulasi oleh operator lapangan."
    >
        @php
            $headers = ['Waktu Terjadi', 'Pengguna Terlibat', 'Aksi', 'Rincian Aktivitas', 'Alamat IP'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($logs as $log)
                @php
                    $actionBadge = match ($log->action) {
                        'login' => ['variant' => 'blue', 'label' => 'LOGIN'],
                        'logout' => ['variant' => 'zinc', 'label' => 'LOGOUT'],
                        'created' => ['variant' => 'emerald', 'label' => 'CREATED'],
                        'updated' => ['variant' => 'amber', 'label' => 'UPDATED'],
                        'deleted' => ['variant' => 'red', 'label' => 'DELETED'],
                        default => ['variant' => 'zinc', 'label' => strtoupper($log->action)],
                    };
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Waktu --}}
                    <td class="px-4 py-3 first:pl-5 font-mono tabular-nums text-xs text-zinc-900 whitespace-nowrap">
                        <div class="font-semibold">{{ $log->created_at ? $log->created_at->format('d/m/Y') : '-' }}</div>
                        <div class="text-[11px] text-zinc-500">{{ $log->created_at ? $log->created_at->format('H:i:s') : '-' }} WIB</div>
                    </td>

                    {{-- Pengguna --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($log->user)
                            <div class="font-medium text-xs text-zinc-900">{{ $log->user->name }}</div>
                            <div class="text-[11px] text-zinc-500 font-mono">{{ $log->user->email }}</div>
                        @else
                            <span class="text-xs text-zinc-400 italic">Sistem / Background</span>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge :variant="$actionBadge['variant']" size="sm" class="font-mono text-[10px]">
                            {{ $actionBadge['label'] }}
                        </x-ui.badge>
                    </td>

                    {{-- Deskripsi --}}
                    <td class="px-4 py-3 text-xs text-zinc-800 font-normal">
                        {{ $log->description }}
                    </td>

                    {{-- Alamat IP --}}
                    <td class="px-4 py-3 last:pr-5 font-mono tabular-nums text-[11px] text-zinc-500 whitespace-nowrap">
                        {{ $log->ip_address ?: '127.0.0.1' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Tidak ada log aktivitas yang cocok dengan kriteria filter.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
