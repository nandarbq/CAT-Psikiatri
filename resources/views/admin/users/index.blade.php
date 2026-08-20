<x-layouts.admin
    title="Manajemen Admin"
    heading="Manajemen Admin"
    subtitle="Kelola akun operator internal. Akun peserta tetap dikelola pada menu Peserta."
>
    <x-slot:actions>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Buat Akun Admin</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Nama / Username / Email</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari Admin...">
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Atur Ulang</a>
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
            Total: <span class="font-medium">{{ $users->total() }}</span> admin
        </div>
    </div>

    <div class="table-wrap" data-bulk-scope="users">
        <table class="data-table">
            <thead>
            <tr>
                <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua"></th>
                <th>Nama</th>
                <th>Username</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><input type="checkbox" value="{{ $user->id }}" data-row-check></td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.users.edit', $user) }}" class="table-link">Ubah</a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-form" data-confirm-message="Hapus akun ini?" data-confirm-title="Konfirmasi Hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada user admin.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.users.bulk') }}" class="bulk-bar" data-bulk-form="users">
        @csrf
        <div data-hidden-ids></div>
        <p><strong data-selected-count>0</strong> item dipilih</p>
        <div class="bulk-controls">
            <select class="input" name="action" required>
                <option value="">Pilih aksi</option>
                <option value="delete">Hapus</option>
            </select>
            <button type="submit" class="btn btn-primary">Jalankan</button>
        </div>
    </form>

    {{ $users->links() }}

    <script>
        (() => {
            const scope = document.querySelector('[data-bulk-scope="users"]');
            const form = document.querySelector('[data-bulk-form="users"]');
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
                    text: 'Jalankan aksi massal untuk user terpilih?',
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
