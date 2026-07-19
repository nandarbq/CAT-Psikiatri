<x-layouts.admin
    title="Manajemen Peserta"
    heading="Manajemen Peserta"
    subtitle="Kelola data akun peserta, tambah banyak peserta sekaligus, dan atur identitas."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.bulk-create.form') }}" class="btn btn-ghost">Buat Banyak Akun Peserta</a>
        <a href="{{ route('admin.participants.create') }}" class="btn btn-primary">Buat Akun Peserta</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.participants.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Nama / Username / NIK Peserta</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari peserta...">
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    <div class="table-wrap" data-bulk-scope="participants">
        <table class="data-table">
            <thead>
            <tr>
                <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua"></th>
                <th>Nama</th>
                <th>Username</th>
                <th>NIK</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($participants as $participant)
                <tr>
                    <td><input type="checkbox" value="{{ $participant->id }}" data-row-check></td>
                    <td>{{ $participant->name }}</td>
                    <td>{{ $participant->username }}</td>
                    <td>{{ $participant->nik }}</td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.participants.history', $participant) }}" class="table-link">Riwayat</a>
                            <a href="{{ route('admin.participants.edit', $participant) }}" class="table-link">Ubah</a>
                            <form method="POST" action="{{ route('admin.participants.destroy', $participant) }}" class="inline-form" data-confirm-message="Hapus peserta ini?" data-confirm-title="Konfirmasi Hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.participants.bulk') }}" class="bulk-bar" data-bulk-form="participants">
        @csrf
        <div data-hidden-ids></div>
        <p><strong data-selected-count>0</strong> item dipilih</p>
        <div class="bulk-controls">
            <select class="input" name="action" required>
                <option value="">Pilih aksi</option>
                <option value="mark_finished">Tandai Selesai</option>
                <option value="mark_unfinished">Tandai Belum</option>
                <option value="delete">Hapus</option>
            </select>
            <button type="submit" class="btn btn-primary">Jalankan</button>
        </div>
    </form>

    {{ $participants->links() }}

    <script>
        (() => {
            const scope = document.querySelector('[data-bulk-scope="participants"]');
            const form = document.querySelector('[data-bulk-form="participants"]');
            if (!scope || !form) return;

            const checks = Array.from(scope.querySelectorAll('[data-row-check]'));
            const selectAll = scope.querySelector('[data-select-all]');
            const countEl = form.querySelector('[data-selected-count]');
            const hiddenIds = form.querySelector('[data-hidden-ids]');

            const selectedIds = () => checks.filter((el) => el.checked).map((el) => el.value);

            const sync = () => {
                const checked = selectedIds().length;
                countEl.textContent = String(checked);
                if (selectAll) {
                    selectAll.checked = checked > 0 && checked === checks.length;
                    selectAll.indeterminate = checked > 0 && checked < checks.length;
                }
            };

            checks.forEach((el) => el.addEventListener('change', sync));
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checks.forEach((el) => { el.checked = selectAll.checked; });
                    sync();
                });
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
