<x-layouts.app title="Status Sistem">
    @php
        $overallTone = ($health['status'] ?? 'degraded') === 'ok' ? 'ok' : 'danger';
        $databaseCheck = $health['checks']['database'] ?? [];
        $databaseTone = ($databaseCheck['status'] ?? 'degraded') === 'ok' ? 'ok' : 'danger';
        $payloadPreview = [
            'health' => $health,
            'version' => $version,
        ];
    @endphp

    <section class="panel participant-panel status-monitor-panel">
        <div class="org-brand-inline">
            <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="org-brand-logo">
            <div>
                <p class="org-brand-kicker">RSUD Blambangan</p>
                <p class="org-brand-name">CAT Psikiatri • Monitoring</p>
            </div>
        </div>

        <div class="participant-hero">
            <h1 class="title-xl">Status Sistem</h1>
            <p class="subtitle">
                Ringkasan kondisi aplikasi, koneksi database, dan metadata build untuk verifikasi deploy.
            </p>
        </div>

        <section class="status-monitor-grid">
            <article class="participant-card">
                <div class="status-monitor-head">
                    <h2>Health Check</h2>
                    <span class="status-chip {{ $overallTone }}">
                        {{ strtoupper((string) ($health['status'] ?? 'degraded')) }}
                    </span>
                </div>
                <p class="participant-muted">
                    HTTP {{ $healthHttpStatus }} • {{ $health['timestamp'] ?? '-' }}
                </p>

                <div class="participant-status-box is-{{ $databaseTone === 'ok' ? 'success' : 'danger' }}">
                    <div class="participant-status-head">
                        <span class="participant-status-icon" aria-hidden="true"></span>
                        <p class="participant-status-label">Database</p>
                    </div>
                    <p class="participant-status-text">{{ $databaseCheck['message'] ?? 'Informasi database tidak tersedia.' }}</p>
                    <p class="participant-muted status-monitor-extra">
                        Connection: {{ $databaseCheck['connection'] ?? '-' }}
                    </p>
                </div>
            </article>

            <article class="participant-card">
                <div class="status-monitor-head">
                    <h2>Version / Build</h2>
                    <span class="status-chip {{ ($version['commit'] ?? null) !== null ? 'ok' : 'pending' }}">
                        {{ ($version['commit'] ?? null) !== null ? 'TERDETEKSI' : 'UNKNOWN' }}
                    </span>
                </div>

                <dl class="participant-meta status-monitor-meta">
                    <div><dt>App</dt><dd>{{ $version['app'] ?? '-' }}</dd></div>
                    <div><dt>Environment</dt><dd>{{ $version['environment'] ?? '-' }}</dd></div>
                    <div><dt>Version</dt><dd>{{ $version['version'] ?? '-' }}</dd></div>
                    <div><dt>Commit</dt><dd>{{ $version['commit'] ?? '-' }}</dd></div>
                    <div><dt>Commit Short</dt><dd>{{ $version['commit_short'] ?? '-' }}</dd></div>
                    <div><dt>Deployed At</dt><dd>{{ $version['deployed_at'] ?? '-' }}</dd></div>
                    <div><dt>Generated At</dt><dd>{{ $version['generated_at'] ?? '-' }}</dd></div>
                </dl>
            </article>
        </section>

        <section class="participant-card">
            <h2>Endpoint Monitoring</h2>
            <p class="participant-muted">Gunakan endpoint berikut untuk integrasi machine-to-machine.</p>
            <div class="status-monitor-links">
                <a href="{{ route('monitoring.health') }}" class="btn btn-ghost">GET /health</a>
                <a href="{{ route('monitoring.version') }}" class="btn btn-ghost">GET /version</a>
                <a href="{{ route('monitoring.build-info') }}" class="btn btn-ghost">GET /build-info</a>
            </div>
        </section>

        <section class="participant-card">
            <h2>Payload Preview</h2>
            <p class="participant-muted">Snapshot payload untuk verifikasi cepat dari browser.</p>
            <pre class="settings-log-view status-monitor-json">{{ json_encode($payloadPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    </section>
</x-layouts.app>
