@php
    use App\Models\Setting;
    $companyName = Setting::get('company_name', 'GiftCardBot');
    $logoPath = Setting::get('logo_path');
    $pageTitleSuffix = Setting::get('page_title', 'GiftCardBot');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? 'Dashboard') }} — {{ $pageTitleSuffix }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-gray-900 antialiased">
    <div x-data="{ mobileOpen: false }" class="min-h-screen flex flex-col lg:flex-row">

        {{-- Mobile header --}}
        <div class="lg:hidden flex items-center justify-between px-4 h-14 bg-white border-b border-gray-200 sticky top-0 z-50">
            <a href="{{ route('admin.dashboard') }}" class="font-semibold text-sm tracking-tight">
                @if($logoPath)
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $companyName }}" class="h-6 w-auto">
                @else
                    {{ $companyName }}
                @endif
            </a>
            <button @click="mobileOpen = !mobileOpen" class="p-2 -mr-2 text-gray-500">
                <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Mobile sidebar --}}
        <aside x-show="mobileOpen" x-cloak x-transition:enter="transition-transform duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-0 z-40 lg:relative lg:translate-x-0 lg:flex lg:w-56 lg:shrink-0 lg:flex-col">
            <div class="absolute inset-0 bg-black/30 lg:hidden" @click="mobileOpen = false"></div>
            <div class="relative w-56 bg-primary-900 text-white flex flex-col h-full lg:min-h-screen">
                <div class="flex items-center h-14 px-5 border-b border-primary-700/50">
                    @if($logoPath)
                        <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $companyName }}" class="h-6 w-auto brightness-0 invert">
                    @else
                        <span class="font-semibold text-sm tracking-tight">{{ $companyName }}</span>
                    @endif
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                    <x-sidebar-link :route="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="dashboard">Dashboard</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.ledger')" :active="request()->routeIs('admin.ledger')" icon="ledger">Ledger</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.trades')" :active="request()->routeIs('admin.trades')" icon="trades">Trades</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.customers')" :active="request()->routeIs('admin.customers')" icon="customers">Customers</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.rates')" :active="request()->routeIs('admin.rates')" icon="rates">Rates</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.whatsapp')" :active="request()->routeIs('admin.whatsapp')" icon="whatsapp">WhatsApp</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.services')" :active="request()->routeIs('admin.services')" icon="services">Services</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.settings')" :active="request()->routeIs('admin.settings')" icon="settings">Settings</x-sidebar-link>
                </nav>
                <div class="border-t border-primary-700/50 px-3 py-3">
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-primary-300 hover:text-white hover:bg-primary-700/50 transition-colors text-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Logout
                        </button>
                    </form>
                    <p class="text-center text-primary-400 text-[10px] mt-4 tracking-widest uppercase">{{ $companyName }}</p>
                </div>
            </div>
        </aside>

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:w-56 lg:shrink-0 lg:flex-col">
            <div class="bg-primary-900 text-white flex flex-col h-full lg:min-h-screen">
                <div class="flex items-center h-14 px-5 border-b border-primary-700/50">
                    @if($logoPath)
                        <img src="{{ asset('storage/' . $logoPath) }}" alt="{{ $companyName }}" class="h-6 w-auto brightness-0 invert">
                    @else
                        <span class="font-semibold text-sm tracking-tight">{{ $companyName }}</span>
                    @endif
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
                    <x-sidebar-link :route="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="dashboard">Dashboard</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.ledger')" :active="request()->routeIs('admin.ledger')" icon="ledger">Ledger</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.trades')" :active="request()->routeIs('admin.trades')" icon="trades">Trades</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.customers')" :active="request()->routeIs('admin.customers')" icon="customers">Customers</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.rates')" :active="request()->routeIs('admin.rates')" icon="rates">Rates</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.whatsapp')" :active="request()->routeIs('admin.whatsapp')" icon="whatsapp">WhatsApp</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.services')" :active="request()->routeIs('admin.services')" icon="services">Services</x-sidebar-link>
                    <x-sidebar-link :route="route('admin.settings')" :active="request()->routeIs('admin.settings')" icon="settings">Settings</x-sidebar-link>
                </nav>
                <div class="border-t border-primary-700/50 px-3 py-3">
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-primary-300 hover:text-white hover:bg-primary-700/50 transition-colors text-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Logout
                        </button>
                    </form>
                    <p class="text-center text-primary-400 text-[10px] mt-4 tracking-widest uppercase">{{ $companyName }}</p>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-h-0">
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
                {{ $slot }}
            </main>
            <footer class="text-center py-4 text-[10px] text-gray-400 tracking-widest uppercase border-t border-gray-100 lg:hidden">
                {{ $companyName }}
            </footer>
        </div>
    </div>
</body>
</html>
