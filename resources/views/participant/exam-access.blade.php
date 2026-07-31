<x-layouts.app title="Akses Tes">
    <x-participant-shell
        :participant="$participant"
        eyebrow="Akses Tes"
        heading="Akses Sesi Tes"
        description="Baca petunjuk tes dengan teliti sebelum memulai."
    >
        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <section class="participant-info-grid">
            <article class="participant-card">
                <h2>Petunjuk Tes</h2>
                @if ($session === null)
                    <p class="participant-muted">Anda belum terdaftar pada sesi tes. Hubungi admin.</p>
                @else
                    <p class="participant-muted"><strong>Sesi:</strong> {{ $session->title }}</p>
                    @if (filled($session->instructions))
                        <div class="participant-instruction-text">{!! nl2br(e((string) $session->instructions)) !!}</div>
                    @else
                        <p class="participant-muted">Petunjuk tes dari admin belum tersedia.</p>
                    @endif
                @endif
            </article>

            <article class="participant-card">
                <h2>Input Token Tes</h2>
                @if (! $hasSession)
                    <div class="alert warning">Form token belum tersedia karena Anda belum dimasukkan ke sesi tes.</div>
                @elseif (! $tokenBypassEnabled && ! $hasSessionToken)
                    <div class="alert warning">Token sesi belum dibuat oleh admin.</div>
                @else
                    @if ($tokenBypassEnabled)
                        <div class="alert warning">
                            Bypass token aktif dari konfigurasi `.env`. Nonaktifkan kembali setelah testing selesai.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('exam.start') }}" class="form-grid">
                        @csrf
                        @if (! $tokenBypassEnabled)
                            <label class="field">
                                <span>Token Tes (6 digit)</span>
                                <input
                                    type="text"
                                    name="token"
                                    value="{{ old('token') }}"
                                    required
                                    class="input"
                                    maxlength="6"
                                    inputmode="numeric"
                                    pattern="[0-9]{6}"
                                    title="Token harus 6 digit angka"
                                >
                            </label>
                        @else
                            <input type="hidden" name="token" value="{{ old('token', 'BYPASS') }}">
                        @endif
                        <input type="hidden" name="latitude" id="exam-latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="exam-longitude" value="{{ old('longitude') }}">
                        <input type="hidden" name="accuracy" id="exam-accuracy" value="{{ old('accuracy') }}">
                        <p class="save-status" id="location-status">
                            {{ $requiresLocation ? 'Meminta akses lokasi perangkat...' : 'Sesi online: akses lokasi tidak diperlukan.' }}
                        </p>
                        <button type="submit" class="btn btn-primary" id="exam-submit-btn" @disabled(! $canStartExam || $requiresLocation)>Mulai / Lanjutkan Tes</button>
                        <button type="button" class="btn btn-ghost" id="exam-retry-location-btn" style="display: none;">Coba deteksi lokasi lagi</button>
                    </form>
                @endif
            </article>
        </section>
    </x-participant-shell>

    <script>
        (() => {
            const requiresLocation = @js($requiresLocation);
            const canStartExam = @js($canStartExam);
            const latitudeInput = document.getElementById('exam-latitude');
            const longitudeInput = document.getElementById('exam-longitude');
            const accuracyInput = document.getElementById('exam-accuracy');
            const status = document.getElementById('location-status');
            const submitBtn = document.getElementById('exam-submit-btn');
            const retryBtn = document.getElementById('exam-retry-location-btn');

            if (!latitudeInput || !longitudeInput || !accuracyInput || !status) {
                return;
            }

            const setStatus = (text, type = 'idle') => {
                status.textContent = text;
                status.className = `save-status ${type}`;
            };

            if (!requiresLocation) {
                latitudeInput.value = '';
                longitudeInput.value = '';
                accuracyInput.value = '';
                setStatus('Sesi online: akses lokasi tidak diperlukan.', 'success');
                return;
            }

            const writePosition = (position) => {
                latitudeInput.value = String(position.coords.latitude);
                longitudeInput.value = String(position.coords.longitude);
                accuracyInput.value = String(position.coords.accuracy ?? '');
            };

            const requestLocation = () => new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Browser tidak mendukung geolokasi.'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => resolve(position),
                    (error) => reject(error),
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            });

            const attemptLocation = () => {
                setStatus('Meminta akses lokasi perangkat... Mohon izinkan akses lokasi pada browser.', 'idle');
                if (retryBtn) {
                    retryBtn.style.display = 'none';
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                requestLocation()
                    .then((position) => {
                        writePosition(position);
                        setStatus('Lokasi terdeteksi. Anda dapat melanjutkan.', 'success');
                        if (submitBtn && canStartExam) {
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(() => {
                        setStatus('Lokasi belum berhasil dideteksi. Pastikan izin lokasi browser aktif dan GPS/koneksi perangkat menyala, lalu coba lagi.', 'error');
                        if (retryBtn) {
                            retryBtn.style.display = 'inline-flex';
                        }
                    });
            };

            if (retryBtn) {
                retryBtn.addEventListener('click', attemptLocation);
            }

            attemptLocation();
        })();
    </script>
</x-layouts.app>
