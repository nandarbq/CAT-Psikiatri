<x-layouts.app title="Masuk Sistem" :full-bleed="true">
    <section class="auth-hero">
        <div class="auth-hero-card">
            <div class="auth-hero-brand">
                <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="auth-hero-logo">
                <p class="auth-hero-kicker">RSUD Blambangan</p>
                <h1 class="auth-hero-title">Sistem CAT Psikiatri</h1>
            </div>

            <p class="auth-hero-subtitle">Masuk ke akun Anda</p>

            @if ($errors->any())
                <div class="auth-hero-alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('participant.login.submit') }}" class="auth-hero-form">
                @csrf
                <label class="auth-hero-field">
                    <span class="auth-hero-field-label">Username</span>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        required
                        autocomplete="username"
                        class="auth-hero-input"
                        placeholder="Masukkan username"
                    >
                </label>
                <label class="auth-hero-field">
                    <span class="auth-hero-field-label">Password</span>
                    <div class="auth-hero-password-wrap">
                        <input
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="auth-hero-input auth-hero-password-input"
                            placeholder="Masukkan password"
                            data-password-input
                        >
                        <button
                            type="button"
                            class="auth-hero-password-toggle"
                            aria-label="Lihat password"
                            aria-pressed="false"
                            data-password-toggle
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true" data-password-icon></svg>
                        </button>
                    </div>
                </label>
                <button type="submit" class="auth-hero-submit">Masuk Sistem</button>
            </form>

            <p class="auth-hero-footnote">Gunakan akun peserta atau admin. Sistem akan mengarahkan otomatis.</p>
        </div>
    </section>

    <script>
        (() => {
            const input = document.querySelector('[data-password-input]');
            const toggle = document.querySelector('[data-password-toggle]');
            if (!input || !toggle) return;

            const icon = toggle.querySelector('[data-password-icon]');
            if (!icon) return;

            const eyeMarkup = `
                <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
            const eyeOffMarkup = `
                <path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"></path>
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M4 4l16 16"></path>
            `;
            const setIcon = (isRevealed) => {
                icon.innerHTML = isRevealed ? eyeMarkup : eyeOffMarkup;
            };

            setIcon(false);

            toggle.addEventListener('click', () => {
                const reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                toggle.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Lihat password');
                toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                setIcon(reveal);
            });
        })();
    </script>
</x-layouts.app>
