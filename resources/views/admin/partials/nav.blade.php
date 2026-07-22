<a href="{{ route('admin.dashboard') }}" class="admin-nav-link admin-nav-link-dashboard {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" title="Dashboard">
    <span class="admin-nav-link-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
            <path d="M3 13h8V3H3v10zM13 21h8V11h-8v10zM13 3v6h8V3h-8zM3 21h8v-6H3v6z"></path>
        </svg>
    </span>
    <span class="admin-nav-link-label">Dashboard</span>
</a>

<div class="admin-nav-group">
    <span class="admin-nav-section">Operasional</span>
    <a href="{{ route('admin.results') }}" class="admin-nav-link {{ request()->routeIs('admin.results*') ? 'is-active' : '' }}" title="Hasil Tes">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M4 12h4v8H4zM10 8h4v12h-4zM16 4h4v16h-4z"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Hasil Tes</span>
    </a>
    <a href="{{ route('admin.participants.index') }}" class="admin-nav-link {{ request()->routeIs('admin.participants*') ? 'is-active' : '' }}" title="Peserta">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zM8.5 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM6.5 17a3.5 3.5 0 0 1 7 0M14 8h4M14 12h4M14 16h3"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Peserta</span>
    </a>
    <a href="{{ route('admin.sessions.index') }}" class="admin-nav-link {{ request()->routeIs('admin.sessions*') ? 'is-active' : '' }}" title="Sesi Tes">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M8 2v3M16 2v3M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Sesi Tes</span>
    </a>
    <a href="{{ route('admin.instructions.index') }}" class="admin-nav-link {{ request()->routeIs('admin.instructions*') ? 'is-active' : '' }}" title="Panduan Tes">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M12 18h.01M12 14v-4M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20z"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Panduan Tes</span>
    </a>
    <a href="{{ route('admin.tokens.index') }}" class="admin-nav-link {{ request()->routeIs('admin.tokens*') ? 'is-active' : '' }}" title="Token Tes">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M14 8a4 4 0 1 0-3.9 5h1.4l2.5 2.5v1.5h1.5v1.5H17V20h2v-2l2-2v-2h-2l-2-2h-3a4 4 0 0 0 0-4z"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Token Tes</span>
    </a>
</div>

<div class="admin-nav-group">
    <span class="admin-nav-section">Materi Tes</span>
    <a href="{{ route('admin.modules.index') }}" class="admin-nav-link {{ request()->routeIs('admin.modules*') || request()->routeIs('admin.questions*') ? 'is-active' : '' }}" title="Pustaka Soal">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M6 2h9l5 5v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zM14 2v6h6M8 12h8M8 16h8M8 8h3"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Pustaka Soal</span>
    </a>
    <a href="{{ route('admin.simulation.index') }}" class="admin-nav-link {{ request()->routeIs('admin.simulation*') ? 'is-active' : '' }}" title="Simulasi Tes">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M10 3h4M9 3h6M9 8h6M7 8h10M8 8l1 12h6l1-12M6 20h12"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Simulasi Tes</span>
    </a>
</div>

<div class="admin-nav-group">
    <span class="admin-nav-section">Manajemen</span>
    <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users*') ? 'is-active' : '' }}" title="Manajemen Admin" aria-label="Manajemen Admin">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11A3.5 3.5 0 1 0 8.5 4a3.5 3.5 0 0 0 0 7zM23 21v-2a4 4 0 0 0-3-3.87M16 4.13a3.5 3.5 0 0 1 0 6.74"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Admin</span>
    </a>
    <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings*') ? 'is-active' : '' }}" title="Pengaturan">
        <span class="admin-nav-link-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM19.4 15a1.7 1.7 0 0 0 .34 1.86l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .17 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1-.17 1.7 1.7 0 0 0-1.86.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.17-1 1.7 1.7 0 0 0-1.55-1H2.8a2 2 0 1 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0 .17-1 1.7 1.7 0 0 0-.34-1.86l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.17 1.7 1.7 0 0 0 1-1.55V2.8a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1 .17 1.7 1.7 0 0 0 1.86-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c0 .35.06.69.17 1a1.7 1.7 0 0 0 1.55 1h.09a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.55 1z"></path>
            </svg>
        </span>
        <span class="admin-nav-link-label">Pengaturan</span>
    </a>
</div>
