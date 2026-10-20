<x-layouts.app title="Login Pengguna">
    <x-slot:head>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    </x-slot:head>

    <div class="min-h-[75vh] flex flex-col justify-center items-center py-6 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex items-center justify-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-md bg-zinc-900 text-white font-bold flex items-center justify-center text-base tracking-wider">
                    TBI
                </div>
                <div class="flex flex-col">
                    <span class="text-lg font-bold text-zinc-900 leading-tight tracking-tight">TBI ERP</span>
                    <span class="text-xs text-zinc-500 font-mono">Batching Plant System</span>
                </div>
            </div>
            <h2 class="text-center text-xl font-bold tracking-tight text-zinc-900 mt-2">
                Masuk ke Sistem Operasional
            </h2>
            <p class="mt-1 text-center text-xs text-zinc-500">
                Pilih profil peran atau gunakan email & password resmi.
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
            <x-ui.card>
                {{-- Quick Login by Role (For Testing & Fast Access) --}}
                <div class="mb-6 pb-6 border-b border-zinc-100">
                    <span class="block text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-2">
                        Akses Cepat Pengujian (Role RBAC):
                    </span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="quick_role" value="admin">
                            <button type="submit" class="w-full py-2 px-2.5 rounded border border-zinc-200 bg-zinc-50 hover:bg-zinc-100 text-left transition-colors">
                                <div class="font-bold text-zinc-900">Admin</div>
                                <div class="text-[10px] text-zinc-500">Superuser / Hak Penuh</div>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="quick_role" value="kasir_logistik">
                            <button type="submit" class="w-full py-2 px-2.5 rounded border border-zinc-200 bg-zinc-50 hover:bg-zinc-100 text-left transition-colors">
                                <div class="font-bold text-zinc-900">Kasir Logistik</div>
                                <div class="text-[10px] text-zinc-500">Sales Order & PO</div>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="quick_role" value="surveyor_opname">
                            <button type="submit" class="w-full py-2 px-2.5 rounded border border-zinc-200 bg-zinc-50 hover:bg-zinc-100 text-left transition-colors">
                                <div class="font-bold text-zinc-900">Surveyor Lapangan</div>
                                <div class="text-[10px] text-zinc-500">Opname Tablet</div>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <input type="hidden" name="quick_role" value="operator_produksi">
                            <button type="submit" class="w-full py-2 px-2.5 rounded border border-zinc-200 bg-zinc-50 hover:bg-zinc-100 text-left transition-colors">
                                <div class="font-bold text-zinc-900">Operator Batching</div>
                                <div class="text-[10px] text-zinc-500">Docket Produksi</div>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Standard Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <x-ui.input
                        type="email"
                        name="email"
                        label="Email Resmi"
                        :value="old('email')"
                        placeholder="contoh: admin@tbi.com"
                        required
                    />

                    <x-ui.input
                        type="password"
                        name="password"
                        label="Password"
                        placeholder="••••••••"
                        required
                    />

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-zinc-600">
                            <input type="checkbox" name="remember" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span>Ingat sesi masuk</span>
                        </label>
                        <span class="text-zinc-400 font-mono text-[11px]">Pass default: password</span>
                    </div>

                    <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                        Masuk ke ERP
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
