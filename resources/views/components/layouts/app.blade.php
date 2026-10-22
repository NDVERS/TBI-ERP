@props([
    'title' => 'TBI - Batching Plant ERP System',
    'header' => null,
    'action' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' — TBI ERP' : 'TBI - Batching Plant ERP' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=tbi2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=tbi2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=tbi2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=tbi2">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=tbi2">
    {{ $head ?? '' }}

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-zinc-50 text-zinc-900 antialiased font-sans flex" x-data="{ sidebarOpen: false, authModalOpen: false }" @open-auth-modal.window="authModalOpen = true">

    <!-- Mobile/Tablet Drawer Backdrop -->
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-zinc-950/40 lg:hidden"
        style="display: none;"
    ></div>

    <!-- Sidebar (Desktop Fixed / Tablet Drawer) -->
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-72 lg:w-64 bg-white border-r border-zinc-200 flex flex-col justify-between transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 shrink-0"
    >
        <!-- Sidebar Header -->
        <div class="flex flex-col">
            <div class="h-16 px-4 flex items-center justify-between border-b border-zinc-100">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group min-w-0">
                    <img src="{{ asset('images/logo.png') }}" alt="TB Beton Indonesia" class="h-9 w-auto max-w-32 object-contain">
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-bold text-zinc-900 leading-tight tracking-tight uppercase">ERP Plant</span>
                    </div>
                </a>

                <!-- Close Drawer Button for Tablet -->
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="lg:hidden p-2 text-zinc-400 hover:text-zinc-600 rounded-md focus:outline-none focus:ring-2 focus:ring-zinc-900"
                    aria-label="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Role Badge Indicator -->
            @auth
                <div class="px-5 py-3 bg-zinc-50/80 border-b border-zinc-100 flex items-center justify-between">
                    <span class="text-xs text-zinc-500 font-medium">Role:</span>
                    @php
                        $userRole = auth()->user()->role ?? 'operator_produksi';
                        $roleLabels = [
                            'admin' => 'Admin (Superuser)',
                            'kasir_logistik' => 'Kasir Logistik',
                            'petugas_gudang' => 'Petugas Gudang',
                            'operator_produksi' => 'Operator Produksi',
                            'surveyor_opname' => 'Surveyor Lapangan',
                            'demo' => 'Demo User',
                        ];
                        $roleVariants = [
                            'admin' => 'zinc',
                            'kasir_logistik' => 'blue',
                            'petugas_gudang' => 'amber',
                            'operator_produksi' => 'emerald',
                            'surveyor_opname' => 'emerald',
                            'demo' => 'zinc',
                        ];
                    @endphp
                    <x-ui.badge :variant="$roleVariants[$userRole] ?? 'zinc'" size="sm">
                        {{ $roleLabels[$userRole] ?? $userRole }}
                    </x-ui.badge>
                </div>
            @endauth

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1 overflow-y-auto max-h-[calc(100vh-14rem)]">
                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'manajemen', 'demo']))
                        <a
                            href="{{ url('/dashboard') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('dashboard*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard Eksekutif</span>
                        </a>
                    @else
                        <a
                            href="{{ url('/') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('/') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Home</span>
                        </a>
                    @endif
                @else
                    <a
                        href="{{ url('/') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('/') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Home</span>
                    </a>
                @endauth

                @auth
                    @php
                        $role = auth()->user()->role;
                    @endphp

                    {{-- Sales Orders (Admin, Kasir Logistik, Demo) --}}
                    @if (in_array($role, ['admin', 'kasir_logistik', 'demo']))
                        <a
                            href="{{ url('/sales-orders') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('sales-orders*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Sales Order (SO)</span>
                        </a>

                        <a
                            href="{{ url('/purchase-orders') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('purchase-orders*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Purchase Order (PO)</span>
                        </a>
                    @endif

                    {{-- Inbound & Kapal (Admin, Petugas Gudang, Demo) --}}
                    @if (in_array($role, ['admin', 'petugas_gudang', 'demo']))
                        <a
                            href="{{ url('/material-receives') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('material-receives*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                            </svg>
                            <span>Penerimaan Truk</span>
                        </a>

                        <a
                            href="{{ url('/vessel-shipments') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('vessel-shipments*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            <span>Pengiriman Kapal</span>
                        </a>
                    @endif

                    {{-- Produksi (Admin, Operator Produksi, Demo) --}}
                    @if (in_array($role, ['admin', 'operator_produksi', 'demo']))
                        <a
                            href="{{ url('/production-dockets') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('production-dockets*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span>Produksi Harian</span>
                        </a>

                        {{-- Armada & Alat Berat (11 TM, Wheel Loader, BBM Solar) --}}
                        <a
                            href="{{ url('/fleet-management') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('fleet-management*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                            <span>Armada &amp; Alat Berat</span>
                        </a>

                        {{-- Buku Konsumsi Dexlite & Pelumas (BBM Solar) --}}
                        <a
                            href="{{ url('/dexlite-consumption') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('dexlite-consumption*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 22h12M4 9h10M14 22V4a2 2 0 00-2-2H6a2 2 0 00-2 2v18M14 13h2a2 2 0 012 2v2a2 2 0 002 2 2 2 0 002-2V9.83a2 2 0 00-.59-1.42L18 5" />
                            </svg>
                            <span>Konsumsi Dexlite &amp; BBM</span>
                        </a>
                    @endif

                    {{-- Stock Opname (Admin, Surveyor Opname, Demo) --}}
                    @if (in_array($role, ['admin', 'surveyor_opname', 'demo']))
                        <a
                            href="{{ url('/stock-opnames') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('stock-opnames*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Stock Opname Fisik</span>
                        </a>
                    @endif

                    {{-- Khusus Admin: Rekonsiliasi Stok & Master Data --}}
                    @if ($role === 'admin')
                        <div class="pt-3 pb-1">
                            <span class="px-3 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                                Manajemen &amp; Audit
                            </span>
                        </div>

                        <a
                            href="{{ url('/reconciliation') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('reconciliation*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Rekonsiliasi Stok</span>
                        </a>

                        <a
                            href="{{ url('/users') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('users*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Manajemen User</span>
                        </a>

                        <a
                            href="{{ url('/audit-logs') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->is('audit-logs*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>Audit Trail / Log</span>
                        </a>

                        <a
                            href="{{ route('master-job-mix') }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors {{ request()->routeIs('master-job-mix*') ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span>Master Job Mix (IP)</span>
                        </a>
                    @endif
                @endauth
            </nav>
        </div>

        <!-- Sidebar Footer / User Profile & Logout -->
        <div class="p-3 border-t border-zinc-200 bg-white">
            @auth
                <div class="flex items-center justify-between p-2 rounded-md bg-zinc-50 border border-zinc-100">
                    <div class="flex flex-col min-w-0 pr-2">
                        <span class="text-xs font-semibold text-zinc-900 truncate">{{ auth()->user()->name }}</span>
                        <span class="text-[11px] text-zinc-500 truncate">{{ auth()->user()->email }}</span>
                    </div>

                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="p-2 text-zinc-500 hover:text-red-600 rounded-md hover:bg-zinc-100 focus:outline-none"
                            title="Keluar"
                            aria-label="Logout"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ url('/login') }}" class="w-full">
                        <x-ui.button variant="primary" class="w-full">Login</x-ui.button>
                    </a>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">

        <!-- Top Header Bar -->
        <header class="h-16 bg-white border-b border-zinc-200 px-4 md:px-6 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                <!-- Hamburger Trigger for Mobile/Tablet (>=44px touch target) -->
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="lg:hidden h-11 w-11 shrink-0 flex items-center justify-center text-zinc-600 hover:text-zinc-900 rounded-md border border-zinc-200 hover:bg-zinc-100 active:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-900"
                    aria-label="Buka Menu Navigasi"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Header Title -->
                <div class="flex flex-col min-w-0">
                    <h1 class="text-base md:text-lg font-semibold text-zinc-900 tracking-tight leading-tight truncate">
                        {{ $header ?? $title }}
                    </h1>
                </div>
            </div>

            <!-- Header Actions Slot -->
            @if ($action)
                <div class="flex items-center gap-2 shrink-0">
                    {{ $action }}
                </div>
            @endif
        </header>

        <!-- Main Body -->
        <main class="flex-1 p-4 md:p-6 lg:p-8 overflow-y-auto">
            <!-- Floating Toast Notifications (Auto-Dismiss) -->
            <div class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0">
                @if (session()->has('success'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 4500)"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="pointer-events-auto p-4 rounded-lg bg-zinc-900 text-white border border-zinc-800 shadow-xl flex items-start gap-3"
                        role="alert"
                    >
                        <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="flex-1 text-xs md:text-sm text-zinc-100 pr-1">
                            {{ session('success') }}
                        </div>
                        <button
                            type="button"
                            @click="show = false"
                            class="text-zinc-400 hover:text-white transition-colors p-1 -mr-1 -mt-1 rounded cursor-pointer"
                            title="Tutup notifikasi"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 5000)"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="pointer-events-auto p-4 rounded-lg bg-zinc-900 text-white border border-zinc-800 shadow-xl flex items-start gap-3"
                        role="alert"
                    >
                        <div class="w-5 h-5 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="flex-1 text-xs md:text-sm text-zinc-100 pr-1">
                            {{ session('error') }}
                        </div>
                        <button
                            type="button"
                            @click="show = false"
                            class="text-zinc-400 hover:text-white transition-colors p-1 -mr-1 -mt-1 rounded cursor-pointer"
                            title="Tutup notifikasi"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
            </div>

            {{ $slot }}
        </main>
    </div>

    <!-- Authentication Guard Modal -->
    <div
        x-show="authModalOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-xs"
        style="display: none;"
    >
        <div
            @click.away="authModalOpen = false"
            class="bg-white rounded-lg border border-zinc-200 shadow-xl max-w-md w-full p-6 space-y-4"
        >
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-md bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">Autentikasi Diperlukan</h3>
                    <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
                        Anda harus masuk ke sistem TBI-ERP terlebih dahulu untuk dapat menginput atau memodifikasi data operasional batching plant.
                    </p>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-zinc-100">
                <x-ui.button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="authModalOpen = false"
                >
                    Tutup
                </x-ui.button>
                <a href="{{ route('login') }}">
                    <x-ui.button
                        variant="primary"
                        size="sm"
                    >
                        Masuk ke ERP
                    </x-ui.button>
                </a>
            </div>
        </div>
    </div>

    <!-- Global Additive Chemical Powder Breakdown Modal -->
    <livewire:additive-breakdown-modal />

    @livewireScripts
</body>
</html>
