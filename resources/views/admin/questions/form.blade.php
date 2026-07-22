<x-layouts.admin
    title="Ubah Soal"
    heading="Ubah Soal"
    subtitle="Ubah isi soal sekaligus urutannya di dalam modul. Sistem akan merapikan nomor soal lain otomatis."
>
    <x-slot:actions>
        <a href="{{ route('admin.modules.edit', $question->module_id) }}" class="btn btn-ghost">Kembali ke Modul</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ route('admin.questions.update', $question) }}" class="form-grid">
            @csrf
            @method('PUT')

            <label class="field">
                <span>Pertanyaan</span>
                <textarea class="input" name="question" rows="5" required>{{ old('question', $question->question) }}</textarea>
            </label>

            <label class="field">
                <span>Status</span>
                <select class="input" name="is_active" required>
                    <option value="1" @selected((string) old('is_active', (int) ($question->is_active ?? true)) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('is_active', (int) ($question->is_active ?? true)) === '0')>Nonaktif</option>
                </select>
            </label>

            <label class="field">
                <span>Urutan</span>
                <input
                    class="input"
                    type="number"
                    name="order_no"
                    min="1"
                    max="{{ max(1, $question->newQuery()->where('module_id', $question->module_id)->count()) }}"
                    value="{{ old('order_no', $question->order_no) }}"
                    required
                >
            </label>

            <p class="field-help">
                Isi nomor urutan tujuan di dalam modul ini (contoh: dari 5 jadi 8). Nomor soal lain di modul yang sama akan digeser otomatis.
            </p>
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </section>
</x-layouts.admin>
