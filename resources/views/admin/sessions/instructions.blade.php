<x-layouts.admin
    title="Panduan Tes Peserta"
    heading="Panduan Tes Peserta"
    subtitle="Kelola isi petunjuk ujian yang tampil di halaman akses ujian peserta."
>
    <x-slot:actions>
        <a href="{{ route('admin.sessions.index') }}" class="btn btn-ghost">Kembali ke Manajemen Sesi</a>
    </x-slot:actions>

    @if ($sessions->isEmpty())
        <div class="alert warning">
            Belum ada sesi ujian. Buat sesi terlebih dahulu sebelum mengatur petunjuk ujian.
        </div>
    @else
        <section class="admin-form-card">
            <form method="GET" action="{{ route('admin.instructions.index') }}" class="filter-panel">
                <label class="field">
                    <span>Pilih Sesi</span>
                    <select class="input" name="session_id" onchange="this.form.submit()">
                        @foreach ($sessions as $sessionOption)
                            <option value="{{ $sessionOption->id }}" @selected((int) $selectedSessionId === (int) $sessionOption->id)>
                                {{ $sessionOption->title }}
                                ({{ $sessionOption->status ? 'Aktif' : 'Nonaktif' }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </section>

        @if ($selectedSession)
            <section class="admin-form-card" style="margin-top: 1rem;">
                <form method="POST" action="{{ route('admin.instructions.update', $selectedSession) }}" class="form-grid">
                    @csrf
                    @method('PUT')

                    <div class="field">
                        <span>Ringkasan Sesi</span>
                        <div class="input" style="display: grid; gap: 0.2rem; min-height: auto;">
                            <strong>{{ $selectedSession->title }}</strong>
                            <span>Mulai: {{ $selectedSession->start_time }}</span>
                            <span>Selesai: {{ $selectedSession->end_time }}</span>
                            <span>Status: {{ $selectedSession->status ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>

                    <label class="field">
                        <span>Isi Panduan Tes</span>
                        <textarea
                            class="input"
                            name="instructions"
                            rows="10"
                            placeholder="Contoh:
1. Pastikan identitas sudah sesuai.
2. Kerjakan soal secara berurutan.
3. Dilarang menutup browser selama ujian."
                        >{{ old('instructions', $selectedSession->instructions) }}</textarea>
                        <small class="field-help">Peserta akan melihat konten ini di halaman Akses Ujian sebelum input token.</small>
                    </label>

                    <button type="submit" class="btn btn-primary">Simpan Petunjuk</button>
                </form>
            </section>
        @endif
    @endif
</x-layouts.admin>

