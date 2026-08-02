@props([
    'title' => 'Panel Admin',
    'heading' => 'Panel Admin',
    'subtitle' => null,
])

@php
    $currentAdmin = auth()->user();
    $initials = $currentAdmin
        ? collect(explode(' ', trim((string) $currentAdmin->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('')
        : 'A';
@endphp

<x-layouts.app :title="$title" :full-bleed="true">
    {{-- Main Container --}}
    <div
        x-data="{ 
            menuOpen: false, 
            collapsed: (localStorage.getItem('admin_sidebar_collapsed') === '1') 
        }"
        class="admin-shell"
    >
        {{-- ============================================ --}}
        {{-- SIDEBAR --}}
        {{-- ============================================ --}}
        <aside 
            class="admin-topbar" 
            :class="[
                menuOpen ? 'is-open' : '', 
                collapsed ? 'is-collapsed' : ''
            ]"
        >
            {{-- Sidebar Header --}}
            <div class="admin-topbar-row">
                {{-- Brand / Logo --}}
                <div class="admin-brand">
                    <img 
                        src="{{ asset('assets/logo-rsud-blambangan.png') }}" 
                        alt="Logo RSUD Blambangan" 
                        class="admin-brand-logo"
                    >
                    <div class="admin-brand-meta">
                        <p class="admin-brand-mark">RSUD Blambangan</p>
                        <strong>Psikiatri CAT &middot; Panel Admin</strong>
                    </div>
                </div>

                {{-- Tombol Collapse Sidebar --}}
                <button
                    type="button"
                    class="admin-sidebar-collapse-btn group relative"
                    @click="collapsed = !collapsed; localStorage.setItem('admin_sidebar_collapsed', collapsed ? '1' : '0')"
                    :aria-expanded="String(!collapsed)"
                    :title="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                    :aria-label="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"
                >
                    <svg 
                    class="admin-sidebar-collapse-icon" 
                    :class="collapsed ? 'is-collapsed' : ''" 
                    viewBox="0 0 24 24" 
                    role="presentation" 
                    focusable="false"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M9 3v18"/>
                </svg>
                    
                    {{-- Tooltip --}}
                    <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 pointer-events-none">
                        <span x-text="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'"></span>
                    </span>
                </button>
            </div>

            {{-- Sidebar Navigation --}}
            <nav 
                id="admin-topbar-nav" 
                class="admin-topbar-nav" 
                :class="menuOpen ? 'is-open' : ''" 
                aria-label="Navigasi Admin"
            >
                @include('admin.partials.nav')
            </nav>

            {{-- Sidebar Footer --}}
            <div class="admin-sidebar-footer">
                <form method="POST" action="{{ route('admin.logout') }}" class="admin-logout">
                    @csrf
                    <button 
                        type="submit" 
                        class="btn btn-ghost admin-logout-btn" 
                        title="Keluar Admin" 
                        aria-label="Keluar Admin"
                    >
                        <span class="admin-nav-link-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <path d="M10 17l5-5-5-5"/>
                                <path d="M15 12H3"/>
                            </svg>
                        </span>
                        <span class="admin-nav-link-label">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

    
        <div
            class="admin-nav-backdrop-overlay"
            :class="menuOpen ? 'is-visible' : ''"
            @click="menuOpen = false"
        ></div>

        
        <div class="admin-main">
            {{-- Top Navbar --}}
            <header class="admin-navbar">
                {{-- Mobile Toggle --}}
                <button
                    type="button"
                    class="admin-mobile-toggle"
                    @click="menuOpen = !menuOpen"
                    :aria-expanded="String(menuOpen)"
                    aria-controls="admin-topbar-nav"
                    aria-label="Buka menu navigasi"
                >
                    <span class="admin-mobile-toggle-bar" :class="menuOpen ? 'is-open' : ''"></span>
                </button>

                {{-- Kicker --}}
                <p class="admin-navbar-kicker">Sistem CAT Psikiatri</p>

                {{-- Profile --}}
                <a
                    href="{{ route('admin.settings.index', ['tab' => 'profile']) }}"
                    class="admin-navbar-profile"
                    title="Pengaturan Profil"
                    aria-label="Buka pengaturan profil"
                >
                    <span class="admin-navbar-profile-meta">
                        <span class="admin-navbar-profile-name">
                            {{ $currentAdmin->name ?? 'Admin' }}
                        </span>
                        <span class="admin-navbar-profile-role">Administrator</span>
                    </span>
                    <span class="admin-navbar-avatar" aria-hidden="true">
                        {{ $initials }}
                    </span>
                </a>
            </header>

            {{-- Content --}}
            <section class="admin-content">
                {{-- Page Header --}}
                <div class="admin-page-header">
                    <div class="admin-page-header-info">
                        <p class="admin-kicker">Sistem CAT Psikiatri</p>
                        <h1 class="admin-title">{{ $heading }}</h1>
                        @if ($subtitle)
                            <p class="admin-subtitle">{{ $subtitle }}</p>
                        @endif
                    </div>

                    @isset($actions)
                        <div class="admin-actions">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>

                {{-- Alert Messages --}}
                @if (session('success'))
                    <div class="alert success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert error">{{ $errors->first() }}</div>
                @endif

                {{-- Main Slot --}}
                {{ $slot }}
            </section>
        </div>
    </div>
</x-layouts.app>
