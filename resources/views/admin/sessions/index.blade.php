<x-layouts.admin
    title="Manajemen Sesi"
    heading="Manajemen Sesi"
    subtitle="Atur jadwal tes, mode akses, status sesi, dan aksi massal."
>
    <x-slot:actions>
        <a href="{{ route('admin.instructions.index') }}" class="btn btn-ghost">Atur Panduan Tes</a>
        <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">Tambah Sesi</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.sessions.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Judul</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari sesi...">
        </label>
        <label class="field">
            <span>Status</span>
            <select class="input" name="status">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['status'] ?? '') === '1')>Aktif</option>
                <option value="0" @selected((string) ($filters['status'] ?? '') === '0')>Nonaktif</option>
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.sessions.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    {{-- Per Page Selector --}}
    <div class="flex items-center justify-between mb-4 mt-4">
        <div class="flex items-center space-x-2">
            <label for="per_page" class="text-sm text-gray-700">Tampilkan</label>
            <select id="per_page"
                    name="per_page"
                    class="input !w-auto min-w-[70px]"
                    onchange="window.location.href = this.value">
                @php
                    $currentUrl = request()->fullUrlWithQuery(['per_page' => 'PER_PAGE']);
                @endphp
                @foreach([10, 25, 50, 100] as $size)
                    <option value="{{ str_replace('PER_PAGE', $size, $currentUrl) }}"
                            {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>
            <span class="text-sm text-gray-700">per halaman</span>
        </div>
        <div class="text-sm text-gray-500">
            Total: <span class="font-medium">{{ $sessions->total() }}</span> sesi
        </div>
    </div>

    <div class="table-wrap" data-bulk-scope="sessions">
        <table class="data-table">
            <thead>
            <tr>
                <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua"></th>
                <th>Judul</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Durasi</th>
                <th>Mode Akses</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td><input type="checkbox" value="{{ $session->id }}" data-row-check data-has-attempts="{{ $session->attempts_count > 0 ? '1' : '0' }}"></td>
                    <td>{{ $session->title }}</td>
                    <td>{{ $session->start_time }}</td>
                    <td>{{ $session->end_time }}</td>
                    <td>{{ $session->duration_minutes }} menit</td>
                    <td>
                        <span class="status-chip {{ $session->access_mode === 'onsite_radius' ? 'danger' : 'ok' }}">
                            {{ $session->access_mode === 'onsite_radius' ? 'Radius RSUD' : 'Daring' }}
                        </span>
                    </td>
                    <td>
                        <span class="status-chip {{ $session->status ? 'ok' : 'pending' }}">{{ $session->status ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.sessions.participants.bulk.form', $session) }}" class="table-link">Tautkan Akun Peserta</a>
                            <a href="{{ route('admin.instructions.index', ['session_id' => $session->id]) }}" class="table-link">Petunjuk</a>
                            <a href="{{ route('admin.sessions.edit', $session) }}" class="table-link">Ubah</a>
                            <form method="POST" action="{{ route('admin.sessions.status', $session) }}" class="inline-form" data-confirm-message="{{ $session->status ? 'Nonaktifkan sesi ini?' : 'Aktifkan kembali sesi ini?' }}" data-confirm-title="Konfirmasi Status">
                                @csrf
                                <input type="hidden" name="status" value="{{ $session->status ? '0' : '1' }}">
                                <button type="submit" class="link-button">{{ $session->status ? 'Tutup Sesi' : 'Buka Sesi' }}</button>
                            </form>
                            <form
                                method="POST"
                                action="{{ route('admin.sessions.destroy', $session) }}"
                                class="inline-form"
                                data-confirm-title="Konfirmasi Hapus"
                                data-confirm-icon="{{ $session->attempts_count > 0 ? 'error' : 'warning' }}"
                                data-confirm-message="{{ $session->attempts_count > 0
                                    ? 'Sesi ini sudah memiliki riwayat pengerjaan tes. Menghapusnya akan ikut menghapus seluruh riwayat dan jawaban tes peserta secara permanen. Lanjutkan?'
                                    : 'Hapus sesi ini?' }}"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada sesi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.sessions.bulk') }}" class="bulk-bar" data-bulk-form="sessions">
        @csrf
        <div data-hidden-ids></div>
        <p><strong data-selected-count>0</strong> item dipilih</p>
        <div class="bulk-controls">
            <select class="input" name="action" required>
                <option value="">Pilih aksi</option>
                <option value="activate">Aktifkan</option>
                <option value="deactivate">Nonaktifkan</option>
                <option value="delete">Hapus</option>
            </select>
            <button type="submit" class="btn btn-primary">Jalankan</button>
        </div>
    </form>

    {{ $sessions->links() }}

    <script>
        (() => {
            const scope = document.querySelector('[data-bulk-scope="sessions"]');
            const form = document.querySelector('[data-bulk-form="sessions"]');
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

                const selectedAction = form.querySelector('select[name="action"]').value;
                const selectedHasAttempts = checks.some((el) => el.checked && el.dataset.hasAttempts === '1');

                const approved = await window.AppDialog.confirm({
                    title: 'Konfirmasi Aksi',
                    text: (selectedAction === 'delete' && selectedHasAttempts)
                        ? 'Beberapa sesi yang dipilih sudah memiliki riwayat pengerjaan tes. Menghapusnya akan ikut menghapus seluruh riwayat dan jawaban tes peserta secara permanen. Lanjutkan?'
                        : 'Jalankan aksi massal?',
                    icon: (selectedAction === 'delete' && selectedHasAttempts) ? 'error' : 'question',
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
