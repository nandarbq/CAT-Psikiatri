<x-layouts.admin
    title="Simulasi Tes"
    heading="Simulasi Tes Admin"
    subtitle="Gunakan halaman ini untuk menguji UI/UX tes, timer, navigasi soal, dan submit jawaban."
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
                <h3 class="title-md" style="margin: 0;">Sesi Simulasi Terakhir</h3>
                <p class="subtitle" style="margin: 0;">{{ $isSubmitted ? 'Status: Sudah Disubmit' : 'Status: Sedang Berjalan' }}</p>
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
                <span>Durasi Simulasi (menit)</span>
                <input class="input" type="number" name="duration_minutes" min="1" max="300" required value="{{ old('duration_minutes', 30) }}">
                <small class="field-help">Rentang 1-300 menit. Saat waktu habis, simulasi akan dikunci otomatis.</small>
            </label>

            <label class="field">
                <span>Jumlah Soal Simulasi</span>
                <input class="input" type="number" name="question_count" min="5" max="200" required value="{{ old('question_count', 60) }}">
                <small class="field-help">Rentang 5-200 soal. Isi soal otomatis menggunakan teks simulasi (lorem + template).</small>
            </label>

            <button class="btn btn-primary" type="submit">Mulai Simulasi Baru</button>
        </form>
    </section>
</x-layouts.admin>
