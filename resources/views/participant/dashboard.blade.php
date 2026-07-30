<x-layouts.app title="Dashboard Peserta">
    <x-participant-shell
        :participant="$participant"
        eyebrow="Beranda"
        heading="Halo, {{ $participant->name }} 👋"
        description="Pastikan data diri Anda sudah benar sebelum mengakses tes."
    >
        @if ($hasIncompleteProfile)
            <div class="alert warning">
                Data diri Anda belum lengkap. Mohon cek dan lengkapi NIK, Nama, TTL, Alamat, serta Keperluan sebelum akses tes.
            </div>
        @endif

        <div class="participant-info-grid">
            <article class="participant-card">
                <h2>Status Akses Tes</h2>
                <div class="participant-status-box is-{{ $accessTone }}">
                    <div class="participant-status-head">
                        <span class="participant-status-icon" aria-hidden="true"></span>
                        <p class="participant-status-label">
                            {{ $hasExamAccess ? 'Akses tersedia' : 'Akses belum tersedia' }}
                        </p>
                    </div>
                    <p class="participant-status-text">{{ $accessText }}</p>
                </div>
                <a href="{{ route('exam.access') }}" class="btn btn-primary participant-card-cta">Buka Halaman Akses Tes</a>
            </article>

            <article class="participant-card">
                <h2>Ringkasan Data Diri</h2>
                @php
                    $birthPlace = trim((string) ($participant->birth_place ?? ''));
                    $birthDate = optional($participant->birth_date)->format('d-m-Y');
                    $ttl = $birthPlace !== '' && $birthDate !== null
                        ? "{$birthPlace}, {$birthDate}"
                        : ($birthPlace !== '' ? $birthPlace : ($birthDate !== null ? $birthDate : '-'));
                @endphp
                <dl class="participant-meta">
                    <div><dt>NIK</dt><dd>{{ $participant->nik ?: '-' }}</dd></div>
                    <div><dt>TTL</dt><dd>{{ $ttl }}</dd></div>
                    <div><dt>Alamat</dt><dd>{{ $participant->address ?: '-' }}</dd></div>
                    <div><dt>Keperluan</dt><dd>{{ $participant->purpose ?: '-' }}</dd></div>
                </dl>
                <a href="{{ route('participant.profile.edit') }}" class="btn btn-ghost participant-card-cta">Ubah Data Diri</a>
            </article>
        </div>
    </x-participant-shell>
</x-layouts.app>
