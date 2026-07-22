<x-layouts.admin
    title="Hasil Simulasi Tes"
    heading="Hasil Simulasi Tes"
    subtitle="Ringkasan hasil pengujian UI/UX dan fungsionalitas sesi simulasi."
>
    <x-slot:actions>
        <a href="{{ route('admin.simulation.index') }}" class="btn btn-ghost">Kembali ke Pengaturan Simulasi</a>
    </x-slot:actions>

    <div class="participant-info-grid">
        <article class="participant-card">
            <p class="participant-status-label">Ringkasan</p>
            <p style="margin: 0;">Soal terjawab: <strong>{{ $answeredCount }}</strong></p>
            <p style="margin: 0;">Soal belum dijawab: <strong>{{ $unansweredCount }}</strong></p>
            <p style="margin: 0;">Cakupan jawaban: <strong>{{ $scorePercent }}%</strong></p>
            <p style="margin: 0;">Durasi terpakai: <strong>{{ gmdate('H:i:s', $durationUsedSeconds) }}</strong></p>
        </article>

        <article class="participant-card">
            <p class="participant-status-label">Parameter Simulasi</p>
            <p style="margin: 0;">Mulai Simulasi: <strong>{{ \Illuminate\Support\Carbon::parse((string) ($simulation['started_at'] ?? now()->toIso8601String()))->format('d M Y H:i:s') }}</strong></p>
            <p style="margin: 0;">Submit: <strong>{{ \Illuminate\Support\Carbon::parse((string) ($simulation['submitted_at'] ?? now()->toIso8601String()))->format('d M Y H:i:s') }}</strong></p>
            <p style="margin: 0;">Durasi awal: <strong>{{ (int) ($simulation['duration_minutes'] ?? 0) }} menit</strong></p>
            <p style="margin: 0;">Jumlah keseluruhan soal: <strong>{{ (int) ($simulation['question_count'] ?? 0) }}</strong></p>
        </article>
    </div>

    <section class="admin-form-card" style="margin-top: 1rem;">
        <h2 class="title-md" style="margin-top: 0;">Mulai Pengujian</h2>
        <p class="subtitle">Untuk mencoba skenario lain, mulai simulasi baru dengan durasi/jumlah soal berbeda.</p>
        <a href="{{ route('admin.simulation.index') }}" class="btn btn-primary">Buat Simulasi Baru</a>
    </section>
</x-layouts.admin>
