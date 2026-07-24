<x-layouts.app title="Masuk Sistem">
    <section class="auth-split">
        <div class="auth-split-brand">
            <div class="auth-split-brand-inner">
                <div class="auth-split-brand-header">
                    <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="auth-split-logo">
                    <div class="auth-split-brand-header-text">
                        <p class="auth-split-kicker">RSUD Blambangan</p>
                        <h1 class="auth-split-title">Sistem CAT Psikiatri</h1>
                    </div>
                </div>
                <p class="auth-split-desc">
                    Computer Assisted Test untuk pemeriksaan psikiatri. Masuk menggunakan akun peserta
                    atau akun admin untuk melanjutkan.
                </p>
            </div>
        </div>

        <div class="auth-split-form">
            <div class="auth-split-form-inner">
                <p class="auth-split-form-kicker">Selamat Datang</p>
                <h2 class="title-xl">Masuk ke Akun Anda</h2>
                <p class="subtitle">Gunakan username dan password. Sistem akan otomatis mengarahkan Anda sebagai peserta atau admin.</p>

                @if ($errors->any())
                    <div class="alert error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('participant.login.submit') }}" class="form-grid">
                    @csrf
                    <label class="field">
                        <span>Username</span>
                        <input type="text" name="username" value="{{ old('username') }}" required autocomplete="username" class="input">
                    </label>
                    <label class="field">
                        <span>Password</span>
                        <div class="password-input-wrap">
                            <input
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="input password-input"
                                data-password-input
                            >
                            <button
                                type="button"
                                class="password-toggle-btn"
                                aria-label="Lihat password"
                                aria-pressed="false"
                                data-password-toggle
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true" data-password-icon></svg>
                            </button>
                        </div>
                    </label>
                    <button type="submit" class="btn btn-primary">Masuk Sistem</button>
                </form>
            </div>
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
