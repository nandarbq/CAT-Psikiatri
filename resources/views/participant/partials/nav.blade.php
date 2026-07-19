<header class="participant-header">
    <div class="org-brand-inline participant-brand">
        <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="org-brand-logo">
        <div>
            <p class="org-brand-kicker">RSUD Blambangan</p>
            <p class="org-brand-name">Portal Peserta CAT Psikiatri</p>
        </div>
    </div>
    <nav class="participant-nav" aria-label="Navigasi Peserta">
        <a href="{{ route('participant.dashboard') }}" class="participant-nav-link {{ request()->routeIs('participant.dashboard') ? 'is-active' : '' }}">
            Beranda
        </a>
        <a href="{{ route('participant.profile.edit') }}" class="participant-nav-link {{ request()->routeIs('participant.profile.*') ? 'is-active' : '' }}">
            Ubah Data Diri
        </a>
        <a href="{{ route('exam.access') }}" class="participant-nav-link {{ request()->routeIs('exam.access') ? 'is-active' : '' }}">
            Akses Ujian
        </a>
    </nav>
</header>
