<x-layouts.app title="Masuk Admin">
    <section class="panel card-tight card-centered admin-login-card">
        <div class="org-brand-inline">
            <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="org-brand-logo">
            <div>
                <p class="org-brand-kicker">RSUD Blambangan</p>
                <p class="org-brand-name">CAT Psikiatri</p>
            </div>
        </div>
        <p class="admin-kicker">Sistem CAT Psikiatri</p>
        <h1 class="admin-title">Masuk Admin</h1>
        <p class="admin-subtitle">Masuk sebagai admin untuk mengelola proses ujian.</p>

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="form-grid">
            @csrf
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="input" autocomplete="email">
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required class="input" autocomplete="current-password">
            </label>
            <button type="submit" class="btn btn-primary">Masuk Admin</button>
            <a href="{{ route('participant.login') }}" class="btn btn-ghost admin-back-link">Kembali ke Login Peserta</a>
        </form>
    </section>
</x-layouts.app>
