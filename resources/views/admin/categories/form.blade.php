<x-layouts.admin
    :title="$isEdit ? 'Ubah Kategori' : 'Buat Kategori'"
    :heading="$isEdit ? 'Ubah Kategori' : 'Buat Kategori'"
    subtitle="Kategori master dipakai sebagai referensi resmi pada setiap soal."
>
    <x-slot:actions>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="form-grid">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Nama Kategori</span>
                <input class="input" type="text" name="name" required maxlength="120" value="{{ old('name', $category->name) }}">
            </label>
            <label class="field">
                <span>Status</span>
                <select class="input" name="is_active" required>
                    <option value="1" @selected((string) old('is_active', (int) ($category->is_active ?? true)) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('is_active', (int) ($category->is_active ?? true)) === '0')>Nonaktif</option>
                </select>
            </label>

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Kategori' }}</button>
        </form>
    </section>
</x-layouts.admin>
