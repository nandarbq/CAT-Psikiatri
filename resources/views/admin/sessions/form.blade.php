<x-layouts.admin
    :title="$isEdit ? 'Ubah Sesi Tes' : 'Buat Sesi Tes'"
    :heading="$isEdit ? 'Ubah Sesi Tes' : 'Buat Sesi Tes'"
    subtitle="Konfigurasi waktu ujian, mode akses, dan durasi pengerjaan."
>
    <x-slot:actions>
        @if ($isEdit)
            <a href="{{ route('admin.sessions.participants.bulk.form', $session) }}" class="btn btn-primary">Tautkan Akun Peserta</a>
        @endif
        <a href="{{ route('admin.sessions.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    @if ($hasAttempts)
        <div class="alert warning">Sesi ini sudah memiliki riwayat pengerjaan ujian. Waktu mulai, waktu selesai, durasi, dan konfigurasi akses dikunci.</div>
    @endif

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.sessions.update', $session) : route('admin.sessions.store') }}" class="form-grid">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Judul</span>
                <input class="input" type="text" name="title" required value="{{ old('title', $session->title) }}">
            </label>
            <label @class(['field', 'is-disabled' => $hasAttempts])>
                <span>Waktu Mulai</span>
                <input class="input" type="datetime-local" name="start_time" required value="{{ old('start_time', optional($session->start_time)->format('Y-m-d\TH:i')) }}" @disabled($hasAttempts)>
                @if ($hasAttempts)
                    <input type="hidden" name="start_time" value="{{ old('start_time', optional($session->start_time)->format('Y-m-d\TH:i')) }}">
                @endif
            </label>
            <label @class(['field', 'is-disabled' => $hasAttempts])>
                <span>Waktu Selesai</span>
                <input class="input" type="datetime-local" name="end_time" required value="{{ old('end_time', optional($session->end_time)->format('Y-m-d\TH:i')) }}" @disabled($hasAttempts)>
                @if ($hasAttempts)
                    <input type="hidden" name="end_time" value="{{ old('end_time', optional($session->end_time)->format('Y-m-d\TH:i')) }}">
                @endif
            </label>
            <label @class(['field', 'is-disabled' => $hasAttempts])>
                <span>Durasi (menit)</span>
                <input class="input" type="number" name="duration_minutes" min="1" max="360" required value="{{ old('duration_minutes', $session->duration_minutes ?? 90) }}" @disabled($hasAttempts)>
                @if ($hasAttempts)
                    <input type="hidden" name="duration_minutes" value="{{ old('duration_minutes', $session->duration_minutes ?? 90) }}">
                @endif
            </label>
            <label class="field">
                <span>Status</span>
                <select class="input" name="status" required>
                    <option value="1" @selected((string) old('status', (int) ($session->status ?? true)) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('status', (int) ($session->status ?? true)) === '0')>Nonaktif</option>
                </select>
            </label>
            <label @class(['field', 'is-disabled' => $hasAttempts])>
                <span>Mode Akses</span>
                <select class="input" name="access_mode" data-access-mode required @disabled($hasAttempts)>
                    <option value="online" @selected(old('access_mode', $session->access_mode ?? 'online') === 'online')>Daring (bisa di luar RSUD)</option>
                    <option value="onsite_radius" @selected(old('access_mode', $session->access_mode ?? 'online') === 'onsite_radius')>Dalam Radius RSUD</option>
                </select>
                @if ($hasAttempts)
                    <input type="hidden" name="access_mode" value="{{ old('access_mode', $session->access_mode ?? 'online') }}">
                @endif
            </label>
            <div class="field" data-onsite-field>
                <span>Pengaturan Lokasi RSUD</span>
                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('admin.location-settings.edit') }}" class="btn btn-ghost">Buka Halaman Lokasi RSUD</a>
                @else
                    <small class="field-help">Akses pengaturan lokasi hanya tersedia untuk admin.</small>
                @endif
                <small class="field-help">Titik pusat dan radius diatur terpisah secara global, lalu dipakai semua sesi dengan mode Radius RSUD.</small>
            </div>
            <label class="field">
                <span>Panduan Tes untuk Peserta</span>
                <textarea class="input" name="instructions" rows="6" placeholder="Tulis petunjuk pengerjaan, ketentuan waktu, dan larangan selama ujian...">{{ old('instructions', $session->instructions) }}</textarea>
                <small class="field-help">Konten ini akan ditampilkan di halaman akses ujian peserta.</small>
            </label>

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Sesi' }}</button>
        </form>
    </section>

    <script>
        (() => {
            const modeSelect = document.querySelector('[data-access-mode]');
            const onsiteFields = Array.from(document.querySelectorAll('[data-onsite-field]'));
            if (!modeSelect || onsiteFields.length === 0) return;

            const syncOnsiteFields = () => {
                const isOnsite = modeSelect.value === 'onsite_radius';
                onsiteFields.forEach((field) => {
                    field.style.display = isOnsite ? '' : 'none';
                });
            };

            modeSelect.addEventListener('change', syncOnsiteFields);
            syncOnsiteFields();
        })();
    </script>
</x-layouts.admin>
