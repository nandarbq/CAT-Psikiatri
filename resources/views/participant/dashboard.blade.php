<x-layouts.app title="Dashboard Peserta">
    <section class="panel participant-panel">
        @include('participant.partials.nav')

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if ($hasIncompleteProfile)
            <div class="alert warning">
                Data diri Anda belum lengkap. Mohon cek dan lengkapi NIK, Nama, TTL, Alamat, serta Keperluan sebelum akses ujian.
            </div>
        @endif

        <div class="participant-layout">
            <article class="participant-identity-card">
                @php
                    $initials = collect(explode(' ', trim((string) $participant->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('');
                    $birthPlace = trim((string) ($participant->birth_place ?? ''));
                    $birthDate = optional($participant->birth_date)->format('d-m-Y');
                    $ttl = $birthPlace !== '' && $birthDate !== null
                        ? "{$birthPlace}, {$birthDate}"
                        : ($birthPlace !== '' ? $birthPlace : ($birthDate !== null ? $birthDate : '-'));
                @endphp
                <span class="participant-avatar-circle" aria-hidden="true">{{ $initials ?: 'P' }}</span>
                <h1 class="participant-identity-name">{{ $participant->name }}</h1>
                <p class="participant-identity-hint">Selamat datang kembali. Pastikan data diri Anda sudah benar.</p>

                <dl class="participant-meta">
                    <div><dt>NIK</dt><dd>{{ $participant->nik ?: '-' }}</dd></div>
                    <div><dt>TTL</dt><dd>{{ $ttl }}</dd></div>
                    <div><dt>Alamat</dt><dd>{{ $participant->address ?: '-' }}</dd></div>
                    <div><dt>Keperluan</dt><dd>{{ $participant->purpose ?: '-' }}</dd></div>
                </dl>

                <a href="{{ route('participant.profile.edit') }}" class="btn btn-ghost participant-identity-edit">Ubah Data Diri</a>
            </article>

            <div class="participant-main-column">
                <article class="participant-card participant-card-status">
                    <h2>Status Akses Ujian</h2>
                    <div class="participant-status-box is-{{ $accessTone }}">
                        <div class="participant-status-head">
                            <span class="participant-status-icon" aria-hidden="true"></span>
                            <p class="participant-status-label">
                                {{ $hasExamAccess ? 'Akses tersedia' : 'Akses belum tersedia' }}
                            </p>
                        </div>
                        <p class="participant-status-text">{{ $accessText }}</p>
                    </div>
                    <a href="{{ route('exam.access') }}" class="btn btn-primary participant-card-cta">Buka Halaman Akses Ujian</a>
                </article>

                <form method="POST" action="{{ route('participant.logout') }}" class="logout-box">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Keluar</button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
