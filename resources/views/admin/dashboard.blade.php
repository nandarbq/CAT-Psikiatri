<x-layouts.admin
    title="Dashboard"
    heading="Dashboard"
    subtitle="Ringkasan aktivitas sistem CAT Psikiatri hari ini."
>
    <div class="dashboard-grid">
        <article class="stat-card">
            <p class="stat-card-label">Sesi Aktif</p>
            <p class="stat-card-value">{{ $stats['active_sessions'] }}</p>
            <p class="stat-card-hint">dari {{ $stats['total_sessions'] }} total sesi tes</p>
        </article>
        <article class="stat-card">
            <p class="stat-card-label">Total Peserta</p>
            <p class="stat-card-value">{{ $stats['total_participants'] }}</p>
            <p class="stat-card-hint">terdaftar di seluruh sesi</p>
        </article>
        <article class="stat-card">
            <p class="stat-card-label">Sedang Mengerjakan</p>
            <p class="stat-card-value">{{ $stats['in_progress'] }}</p>
            <p class="stat-card-hint">percobaan ujian berjalan</p>
        </article>
        <article class="stat-card">
            <p class="stat-card-label">Selesai Hari Ini</p>
            <p class="stat-card-value">{{ $stats['submitted_today'] }}</p>
            <p class="stat-card-hint">{{ $stats['attempts_today'] }} percobaan dimulai hari ini</p>
        </article>
        <article class="stat-card">
            <p class="stat-card-label">Pustaka Soal</p>
            <p class="stat-card-value">{{ $stats['total_questions'] }}</p>
            <p class="stat-card-hint">butir soal tersimpan</p>
        </article>
    </div>

    <div class="dashboard-columns">
        <section class="admin-form-card dashboard-panel">
            <div class="dashboard-panel-head">
                <h2>Sesi Tes Terbaru</h2>
                <a href="{{ route('admin.sessions.index') }}" class="table-link">Lihat Semua</a>
            </div>

            @if ($recentSessions->isEmpty())
                <p class="participant-muted">Belum ada sesi tes yang dibuat.</p>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul Sesi</th>
                                <th>Status</th>
                                <th>Peserta</th>
                                <th>Percobaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSessions as $session)
                                <tr>
                                    <td>{{ $session->title }}</td>
                                    <td>
                                        <span class="status-chip {{ $session->status ? 'ok' : 'pending' }}">
                                            {{ $session->status ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>{{ $session->participants_count }}</td>
                                    <td>{{ $session->attempts_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="admin-form-card dashboard-panel">
            <div class="dashboard-panel-head">
                <h2>Aktivitas Terbaru</h2>
                <a href="{{ route('admin.results') }}" class="table-link">Lihat Hasil</a>
            </div>

            @if ($recentAttempts->isEmpty())
                <p class="participant-muted">Belum ada aktivitas percobaan ujian.</p>
            @else
                <ul class="dashboard-activity-list">
                    @foreach ($recentAttempts as $attempt)
                        <li class="dashboard-activity-item">
                            <span class="dashboard-activity-dot {{ $attempt->is_submitted ? 'is-done' : 'is-progress' }}"></span>
                            <div>
                                <p class="dashboard-activity-title">
                                    {{ $attempt->participant->name ?? 'Peserta' }}
                                    <span class="dashboard-activity-muted">&middot; {{ $attempt->session->title ?? '-' }}</span>
                                </p>
                                <p class="dashboard-activity-muted">
                                    {{ $attempt->is_submitted ? 'Selesai' : 'Sedang berjalan' }}
                                    @if ($attempt->started_at)
                                        &middot; {{ $attempt->started_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.admin>
