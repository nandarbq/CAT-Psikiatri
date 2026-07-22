<x-layouts.admin
    title="Simulasi Tes"
    heading="Simulasi Tes Admin"
    subtitle="Halaman ini bertujuan untuk menguji UI/UX tes, timer, navigasi soal, dan submit jawaban."
>
    @if ($simulation)
        @php
            $isSubmitted = (bool) ($simulation['is_submitted'] ?? false);
            $questionCount = (int) ($simulation['question_count'] ?? 0);
            $durationMinutes = (int) ($simulation['duration_minutes'] ?? 0);
            $answers = is_array($simulation['answers'] ?? null) ? $simulation['answers'] : [];
        @endphp

        <div class="participant-info-grid" style="margin-bottom: 1rem;">
            <article class="participant-card">
                <h3 class="title-md" style="margin: 0;">Riwayat simulasi tes</h3>
                <p class="subtitle" style="margin: 0;">{{ $isSubmitted ? 'Status: Sudah Disubmit' : 'Status: Sedang Berjalan' }}</p>
                <p style="margin: 0;">Modul: <strong>{{ $simulation['module_name'] ?? '-' }}</strong></p>
                <p style="margin: 0;">Jumlah soal: <strong>{{ $questionCount }}</strong></p>
                <p style="margin: 0;">Durasi: <strong>{{ $durationMinutes }} menit</strong></p>
                <p style="margin: 0;">Jawaban tersimpan: <strong>{{ count($answers) }}</strong></p>
                <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                    <a href="{{ $isSubmitted ? route('admin.simulation.result') : route('admin.simulation.run') }}" class="btn btn-ghost">
                        {{ $isSubmitted ? 'Lihat Hasil Simulasi' : 'Lanjutkan Simulasi' }}
                    </a>
                </div>
            </article>
        </div>
    @endif

    <section class="admin-form-card">
        <form method="POST" action="{{ route('admin.simulation.start') }}" class="form-grid">
            @csrf

            <label class="field">
                <span>Modul Soal</span>
                <select class="input" name="module_id" required>
                    <option value="">Pilih Modul</option>
                    @foreach ($modules as $moduleOption)
                        <option value="{{ $moduleOption->id }}" @selected((string) old('module_id') === (string) $moduleOption->id)>
                            {{ $moduleOption->name }} ({{ $moduleOption->active_questions_count }} soal aktif){{ $moduleOption->status ? '' : ' — Nonaktif' }}
                        </option>
                    @endforeach
                </select>
                <small class="field-help">Simulasi akan memakai soal aktif asli dari modul yang dipilih, sama seperti tampilan yang dilihat peserta.</small>
            </label>

            <label class="field">
                <span>Durasi Simulasi (menit)</span>
                <input class="input" type="number" name="duration_minutes" min="1" max="300" required value="{{ old('duration_minutes', 30) }}">
                <small class="field-help">Rentang 1-300 menit. Saat waktu habis, simulasi akan dikunci otomatis.</small>
            </label>

            <button class="btn btn-primary" type="submit">Mulai Simulasi Baru</button>
        </form>
    </section>
</x-layouts.admin>
