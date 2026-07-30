@props([
    'participant',
    'eyebrow' => 'Portal Peserta',
    'heading',
    'description' => null,
])

@php
    $profileComplete = filled($participant->nik)
        && filled($participant->name)
        && filled($participant->birth_place)
        && filled($participant->birth_date)
        && filled($participant->address)
        && filled($participant->purpose);

    $initials = collect(explode(' ', trim((string) $participant->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $navItems = [
        [
            'route' => 'participant.dashboard',
            'pattern' => 'participant.dashboard',
            'label' => 'Beranda',
        ],
        [
            'route' => 'participant.profile.edit',
            'pattern' => 'participant.profile.*',
            'label' => 'Data Diri',
        ],
        [
            'route' => 'exam.access',
            'pattern' => 'exam.access',
            'label' => 'Akses Tes',
        ],
    ];

    $onAccessPage = request()->routeIs('exam.access');
@endphp

<div class="ppage">
    <header class="ppage-topbar">
        <div class="ppage-topbar-inner">
            <div class="ppage-brand">
                <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="ppage-brand-logo">
                <div class="ppage-brand-text">
                    <p class="ppage-brand-kicker">RSUD Blambangan</p>
                    <p class="ppage-brand-name">CAT Psikiatri</p>
                </div>
            </div>

            <nav class="ppage-tabs" aria-label="Navigasi Peserta">
                @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="ppage-tab {{ request()->routeIs($item['pattern']) ? 'is-active' : '' }}"
                    >
                        <span class="ppage-tab-icon" aria-hidden="true">
                            @if ($item['route'] === 'participant.dashboard')
                                <svg viewBox="0 0 20 20" fill="none"><path d="M3 9.5 10 4l7 5.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 8.5V16h10V8.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @elseif ($item['route'] === 'participant.profile.edit')
                                <svg viewBox="0 0 20 20" fill="none"><circle cx="10" cy="7" r="3" stroke="currentColor"/><path d="M4 16.5c1.2-2.8 3.4-4 6-4s4.8 1.2 6 4" stroke="currentColor" stroke-linecap="round"/></svg>
                            @else
                                <svg viewBox="0 0 20 20" fill="none"><circle cx="7" cy="13" r="3" stroke="currentColor"/><path d="M9.2 10.8 15 5m0 0h-3m3 0v3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @endif
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="ppage-profile">
                <span class="ppage-avatar" aria-hidden="true">{{ $initials ?: 'P' }}</span>
                <span class="ppage-profile-name" title="{{ $participant->name }}">{{ $participant->name }}</span>
                <form method="POST" action="{{ route('participant.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost ppage-logout" title="Keluar" aria-label="Keluar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="ppage-main">
        <section class="ppage-hero">
            <div class="ppage-hero-text">
                <p class="ppage-hero-eyebrow">{{ $eyebrow }}</p>
                <h1 class="ppage-hero-title">{{ $heading }}</h1>
                @if ($description)
                    <p class="ppage-hero-desc">{{ $description }}</p>
                @endif
            </div>

            <ol class="ppage-steps" aria-label="Tahapan mengikuti tes">
                <li class="ppage-step {{ $profileComplete ? 'is-done' : 'is-current' }}">
                    <span class="ppage-step-dot">{{ $profileComplete ? '✓' : '1' }}</span>
                    <span class="ppage-step-label">Data Diri</span>
                </li>
                <li class="ppage-step {{ $onAccessPage ? 'is-current' : ($profileComplete ? 'is-done' : '') }}">
                    <span class="ppage-step-dot">{{ (! $onAccessPage && $profileComplete) ? '✓' : '2' }}</span>
                    <span class="ppage-step-label">Akses Sesi</span>
                </li>
                <li class="ppage-step">
                    <span class="ppage-step-dot">3</span>
                    <span class="ppage-step-label">Mulai Ujian</span>
                </li>
            </ol>
        </section>

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        {{ $slot }}
    </main>
</div>
