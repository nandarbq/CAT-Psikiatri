<x-layouts.admin
    title="Master Kategori"
    heading="Master Kategori"
    subtitle="Kelola daftar kategori resmi untuk bank soal dan jalankan aksi massal."
>
    <x-slot:actions>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.categories.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Nama</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari kategori...">
        </label>
        <label class="field">
            <span>Status</span>
            <select class="input" name="is_active">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['is_active'] ?? '') === '1')>Aktif</option>
                <option value="0" @selected((string) ($filters['is_active'] ?? '') === '0')>Nonaktif</option>
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    <div class="table-wrap" data-bulk-scope="categories">
        <table class="data-table">
            <thead>
            <tr>
                <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua"></th>
                <th>Nama</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Dipakai Soal</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td><input type="checkbox" value="{{ $category->id }}" data-row-check data-questions-count="{{ $category->questions_count }}"></td>
                    <td>{{ $category->name }}</td>
                    <td><code>{{ $category->slug }}</code></td>
                    <td>
                        <span class="status-chip {{ $category->is_active ? 'ok' : 'pending' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td>{{ $category->questions_count }}</td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="table-link">Ubah</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline-form" data-confirm-message="Hapus kategori ini?" data-confirm-title="Konfirmasi Hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada kategori.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.categories.bulk') }}" class="bulk-bar" data-bulk-form="categories">
        @csrf
        <div data-hidden-ids></div>
        <p><strong data-selected-count>0</strong> item dipilih</p>
        <span class="bulk-badge" data-delete-warning hidden></span>
        <div class="bulk-controls">
            <select class="input" name="action" required data-bulk-action>
                <option value="">Pilih aksi</option>
                <option value="activate">Aktifkan</option>
                <option value="deactivate">Nonaktifkan</option>
                <option value="delete">Hapus</option>
            </select>
            <button type="submit" class="btn btn-primary">Jalankan</button>
        </div>
    </form>

    {{ $categories->links() }}

    <script>
        (() => {
            const scope = document.querySelector('[data-bulk-scope="categories"]');
            const form = document.querySelector('[data-bulk-form="categories"]');
            if (!scope || !form) return;

            const checks = Array.from(scope.querySelectorAll('[data-row-check]'));
            const selectAll = scope.querySelector('[data-select-all]');
            const countEl = form.querySelector('[data-selected-count]');
            const hiddenIds = form.querySelector('[data-hidden-ids]');
            const actionEl = form.querySelector('[data-bulk-action]');
            const warningEl = form.querySelector('[data-delete-warning]');

            const selectedIds = () => checks.filter((el) => el.checked).map((el) => el.value);
            const selectedLockedCount = () => checks.filter((el) => el.checked && Number(el.dataset.questionsCount || 0) > 0).length;

            const sync = () => {
                const checked = selectedIds().length;
                countEl.textContent = String(checked);
                if (selectAll) {
                    selectAll.checked = checked > 0 && checked === checks.length;
                    selectAll.indeterminate = checked > 0 && checked < checks.length;
                }

                if (warningEl && actionEl && actionEl.value === 'delete') {
                    const lockedCount = selectedLockedCount();
                    if (lockedCount > 0) {
                        warningEl.textContent = `${lockedCount} kategori kemungkinan tidak bisa dihapus`;
                        warningEl.hidden = false;
                    } else {
                        warningEl.hidden = true;
                        warningEl.textContent = '';
                    }
                } else if (warningEl) {
                    warningEl.hidden = true;
                    warningEl.textContent = '';
                }
            };

            checks.forEach((el) => el.addEventListener('change', sync));
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checks.forEach((el) => { el.checked = selectAll.checked; });
                    sync();
                });
            }
            if (actionEl) {
                actionEl.addEventListener('change', sync);
            }

            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();

                const ids = selectedIds();
                if (ids.length === 0) {
                    await window.AppDialog.alert({
                        title: 'Pilihan Belum Ada',
                        text: 'Pilih minimal satu item untuk aksi massal.',
                        icon: 'warning',
                    });
                    return;
                }

                hiddenIds.innerHTML = ids.map((id) => `<input type="hidden" name="ids[]" value="${id}">`).join('');

                const approved = await window.AppDialog.confirm({
                    title: 'Konfirmasi Aksi',
                    text: 'Jalankan aksi massal?',
                    icon: 'question',
                    confirmButtonText: 'Ya, jalankan',
                    cancelButtonText: 'Batal',
                });
                if (!approved) {
                    return;
                }

                form.dataset.confirmed = '1';
                form.submit();
                delete form.dataset.confirmed;
            });

            sync();
        })();
    </script>
</x-layouts.admin>
