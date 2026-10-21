<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-zinc-900 text-white font-mono">
                    ADMIN KEAMANAN
                </span>
                <span class="text-xs text-zinc-500 font-mono">Sistem Hak Akses RBAC</span>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 mt-1">
                Manajemen Pengguna & Otorisasi
            </h1>
            <p class="text-xs md:text-sm text-zinc-500">
                Pengelolaan akun operasional, pembagian role berbasis tugas (RBAC), reset kredensial, dan kontrol akses sistem.
            </p>
        </div>

        <div>
            <button
                type="button"
                wire:click="openCreateModal"
                class="inline-flex items-center gap-1.5 h-10 px-4 rounded-md bg-zinc-900 text-white text-xs md:text-sm font-medium hover:bg-zinc-800 transition-colors shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </div>


    {{-- Tabel Pengguna --}}
    <x-ui.card
        title="Daftar Pengguna Aktif"
        description="Akun operator, surveyor, kasir, gudang, dan administrator yang terdaftar di database."
    >
        <div class="mb-4 max-w-sm">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                placeholder="Cari nama, email, atau role..."
                prefix="🔍"
                :clearable="true"
                :has-value="!empty($search)"
                loading-target="search"
            />
        </div>

        @php
            $headers = ['Nama Pengguna', 'Email Resmi', 'Role RBAC', 'Status Akun', 'Tgl Dibuat', 'Aksi'];
        @endphp

        <x-ui.table :headers="$headers">
            @forelse ($users as $u)
                @php
                    $roleBadges = [
                        'admin' => ['label' => 'Admin (Superuser)', 'variant' => 'zinc'],
                        'kasir_logistik' => ['label' => 'Kasir Logistik', 'variant' => 'blue'],
                        'petugas_gudang' => ['label' => 'Petugas Gudang', 'variant' => 'amber'],
                        'operator_produksi' => ['label' => 'Operator Batching', 'variant' => 'emerald'],
                        'surveyor_opname' => ['label' => 'Surveyor Lapangan', 'variant' => 'emerald'],
                        'manajemen' => ['label' => 'Manajemen / Direksi', 'variant' => 'zinc'],
                    ];
                    $badgeInfo = $roleBadges[$u->role] ?? ['label' => $u->role, 'variant' => 'zinc'];
                @endphp
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    {{-- Nama --}}
                    <td class="px-4 py-3 first:pl-5 font-semibold text-zinc-900 whitespace-nowrap text-sm">
                        {{ $u->name }}
                        @if ($u->id === auth()->id())
                            <span class="ml-1.5 text-[10px] text-zinc-500 font-mono bg-zinc-100 px-1.5 py-0.5 rounded border border-zinc-200">
                                Sesi Anda
                            </span>
                        @endif
                    </td>

                    {{-- Email --}}
                    <td class="px-4 py-3 font-mono text-zinc-600 text-xs whitespace-nowrap">
                        {{ $u->email }}
                    </td>

                    {{-- Role --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <x-ui.badge :variant="$badgeInfo['variant']" size="sm">
                            {{ $badgeInfo['label'] }}
                        </x-ui.badge>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($u->is_active)
                            <x-ui.badge variant="emerald" dot size="sm">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="red" dot size="sm">Nonaktif</x-ui.badge>
                        @endif
                    </td>

                    {{-- Tgl Dibuat --}}
                    <td class="px-4 py-3 font-mono text-zinc-500 text-xs whitespace-nowrap">
                        {{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}
                    </td>

                    {{-- Aksi --}}
                    <td class="px-4 py-3 last:pr-5 whitespace-nowrap text-xs">
                        <div class="flex items-center gap-1.5">
                            {{-- Edit Button --}}
                            <button
                                type="button"
                                wire:click="openEditModal({{ $u->id }})"
                                class="px-2.5 py-1 rounded border border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-700 font-medium transition-colors"
                            >
                                Edit
                            </button>

                            {{-- Reset Password Button --}}
                            <button
                                type="button"
                                wire:click="openResetPasswordModal({{ $u->id }})"
                                class="px-2.5 py-1 rounded border border-zinc-200 bg-white hover:bg-zinc-100 text-zinc-700 font-medium transition-colors"
                                title="Reset Kata Sandi"
                            >
                                Reset Pass
                            </button>

                            {{-- Toggle Status Button --}}
                            @if ($u->id !== auth()->id())
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $u->id }})"
                                    class="px-2.5 py-1 rounded border font-medium transition-colors {{ $u->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}"
                                >
                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-5 py-8 text-center text-zinc-500 text-sm">
                        Tidak ada pengguna yang cocok dengan pencarian.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if ($users->hasPages())
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Modal Tambah / Edit Pengguna --}}
    @if ($showModal)
        <div
            @keydown.escape.window="$wire.closeModal()"
            wire:click.self="closeModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-zinc-950/50 flex items-center justify-center p-4 cursor-pointer"
        >
            <div class="bg-white rounded-lg border border-zinc-200 shadow-xl max-w-lg w-full p-6 space-y-5 cursor-default">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                    <h3 class="text-base font-bold text-zinc-900">
                        {{ $isEditing ? 'Edit Profil & Hak Akses Pengguna' : 'Tambah Pengguna Baru' }}
                    </h3>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="text-zinc-400 hover:text-zinc-600 p-1 rounded-md"
                    >
                        ✕
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    {{-- Nama --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Contoh: Budi Santoso"
                            class="w-full h-10 px-3 rounded-md border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        />
                        @error('name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Email Resmi <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="nama@tbi.com"
                            class="w-full h-10 px-3 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        />
                        @error('email')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Role / Peran Sistem <span class="text-red-500">*</span>
                        </label>
                        <select
                            wire:model="role"
                            class="w-full h-10 px-3 rounded-md border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            required
                        >
                            <option value="admin">Admin (Hak Akses Penuh / Superuser)</option>
                            <option value="kasir_logistik">Kasir Logistik (Sales Order & PO)</option>
                            <option value="petugas_gudang">Petugas Gudang (Inbound Truk & Kapal)</option>
                            <option value="operator_produksi">Operator Produksi (Docket Batching)</option>
                            <option value="surveyor_opname">Surveyor Lapangan (Stock Opname Tablet)</option>
                            <option value="manajemen">Manajemen / Direksi (Dashboard Eksekutif)</option>
                            <option value="demo">Akun Demo (Evaluasi / Terbatas)</option>
                        </select>
                        @error('role')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password (Wajib untuk user baru, opsional saat edit) --}}
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">
                            Kata Sandi {{ $isEditing ? '(Kosongkan jika tidak diubah)' : '*' }}
                        </label>
                        <input
                            type="password"
                            wire:model="password"
                            placeholder="••••••••"
                            class="w-full h-10 px-3 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                            {{ $isEditing ? '' : 'required' }}
                        />
                        @error('password')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status Aktif --}}
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            wire:model="is_active"
                            id="is_active_input"
                            class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                        />
                        <label for="is_active_input" class="text-xs font-medium text-zinc-700 cursor-pointer">
                            Akun Aktif (Bisa login ke sistem)
                        </label>
                    </div>

                    {{-- Tombol Aksi Modal --}}
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-100">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="h-10 sm:h-9 px-4 rounded-md border border-zinc-300 text-xs font-medium text-zinc-700 hover:bg-zinc-50 active:bg-zinc-100 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="h-10 sm:h-9 px-4 rounded-md bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 disabled:opacity-50 transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $isEditing ? 'Simpan Perubahan' : 'Buat Pengguna' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Menyimpan...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Reset Password --}}
    @if ($showPasswordModal)
        <div
            @keydown.escape.window="$wire.closePasswordModal()"
            wire:click.self="closePasswordModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-zinc-950/50 flex items-center justify-center p-4 cursor-pointer"
        >
            <div class="bg-white rounded-lg border border-zinc-200 shadow-xl max-w-sm w-full p-5 space-y-4 cursor-default">
                <div class="flex items-center justify-between pb-2 border-b border-zinc-100">
                    <h3 class="text-sm font-bold text-zinc-900">
                        Reset Kata Sandi Pengguna
                    </h3>
                    <button
                        type="button"
                        wire:click="closePasswordModal"
                        class="text-zinc-400 hover:text-zinc-600"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-3">
                    <p class="text-xs text-zinc-500">
                        Masukkan password baru untuk akun ini (minimal 6 karakter):
                    </p>
                    <input
                        type="text"
                        wire:model="newPassword"
                        class="w-full h-10 px-3 rounded-md border border-zinc-300 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 bg-white"
                        placeholder="Password baru"
                    />
                    @error('newPassword')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                    <button
                        type="button"
                        wire:click="closePasswordModal"
                        class="h-10 sm:h-9 px-3.5 rounded-md border border-zinc-300 text-xs font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="resetPassword"
                        wire:loading.attr="disabled"
                        wire:target="resetPassword"
                        class="h-10 sm:h-9 px-3.5 rounded-md bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 disabled:opacity-50 transition-colors flex items-center gap-1.5 cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="resetPassword">Konfirmasi Reset</span>
                        <span wire:loading wire:target="resetPassword" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Mereset...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
