<x-layouts.admin
    title="Manajemen Peserta"
    heading="Manajemen Peserta"
    subtitle="Kelola data akun peserta, tambah banyak peserta sekaligus, dan atur identitas."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.bulk-create.form') }}" class="btn btn-ghost">Buat Banyak Akun Peserta</a>
        <a href="{{ route('admin.participants.create') }}" class="btn btn-primary">Buat Akun Peserta</a>
    </x-slot:actions>

    {{-- Filter Panel --}}
    <form method="GET" action="{{ route('admin.participants.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Nama / Username / NIK Peserta</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari peserta...">
        </label>
        <label class="field">
            <span>Sesi Tes</span>
            <select class="input" name="session_id">
                <option value="">Sesi</option>
                @foreach ($sessionOptions as $sessionOption)
                    <option value="{{ $sessionOption->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $sessionOption->id)>
                        {{ $sessionOption->title }}
                    </option>
                @endforeach
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Atur Ulang</a>
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
        Total: <span class="font-medium">{{ $participants->total() }}</span> peserta
    </div>
</div>

    {{-- Table --}}
    <div class="table-wrap" data-bulk-scope="participants">
        <table class="data-table">
            <thead>
            <tr>
                <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua"></th>
                <th>Nama</th>
                <th>Username</th>
                <th>NIK</th>
                <th>Sesi</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($participants as $participant)
                <tr>
                    <td><input type="checkbox" value="{{ $participant->id }}" data-row-check data-has-attempts="{{ $participant->attempts_count > 0 ? '1' : '0' }}"></td>
                    <td>{{ $participant->name }}</td>
                    <td>{{ $participant->username }}</td>
                    <td>{{ $participant->nik }}</td>
                    <td>{{ $participant->session->title ?? '-' }}</td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.participants.history', $participant) }}" class="table-link">Riwayat</a>
                            <a href="{{ route('admin.participants.edit', $participant) }}" class="table-link">Ubah</a>
                            <form
                                method="POST"
                                action="{{ route('admin.participants.destroy', $participant) }}"
                                class="inline-form"
                                data-confirm-title="Konfirmasi Hapus"
                                data-confirm-icon="{{ $participant->attempts_count > 0 ? 'error' : 'warning' }}"
                                data-confirm-message="{{ $participant->attempts_count > 0
                                    ? 'Peserta ini sudah memiliki riwayat pengerjaan tes. Menghapusnya akan ikut menghapus seluruh riwayat dan jawaban tesnya secara permanen. Lanjutkan?'
                                    : 'Hapus peserta ini?' }}"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-500">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Bulk Action --}}
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

    {{-- ============================================ --}}
    {{-- PAGINATION CUSTOM - WARNA HIJAU --}}
    {{-- ============================================ --}}
    @if ($participants->hasPages())
        <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 px-4 py-3 mt-6 sm:flex-row">
            {{-- Info --}}
            <div class="text-sm text-gray-700">
                Menampilkan
                <span class="font-medium">{{ $participants->firstItem() }}</span>
                sampai
                <span class="font-medium">{{ $participants->lastItem() }}</span>
                dari
                <span class="font-medium">{{ $participants->total() }}</span>
                peserta
            </div>

            {{-- Tombol Pagination --}}
            <div class="flex items-center gap-1 flex-wrap justify-center">
                {{-- Previous --}}
                @if ($participants->onFirstPage())
                    <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                        ← Sebelumnya
                    </span>
                @else
                    <a href="{{ $participants->previousPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">
                        ← Sebelumnya
                    </a>
                @endif

                {{-- Nomor Halaman --}}
                @php
                    $currentPage = $participants->currentPage();
                    $lastPage = $participants->lastPage();
                    $start = max(1, $currentPage - 2);
                    $end = min($lastPage, $currentPage + 2);
                @endphp

                @if ($start > 1)
                    <a href="{{ $participants->url(1) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">1</a>
                    @if ($start > 2)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                @endif

                @for ($page = $start; $page <= $end; $page++)
                    @if ($page == $currentPage)
                        <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-green-600 border border-green-600 rounded-md shadow-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $participants->url($page) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">{{ $page }}</a>
                    @endif
                @endfor

                @if ($end < $lastPage)
                    @if ($end < $lastPage - 1)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                    <a href="{{ $participants->url($lastPage) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">{{ $lastPage }}</a>
                @endif

                {{-- Next --}}
                @if ($participants->hasMorePages())
                    <a href="{{ $participants->nextPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">
                        Selanjutnya →
                    </a>
                @else
                    <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                        Selanjutnya →
                    </span>
                @endif
            </div>
        </div>
    @else
        {{-- Jika hanya 1 halaman --}}
        <div class="flex justify-center border-t border-gray-200 px-4 py-3 mt-6">
            <div class="text-sm text-gray-700">
                Menampilkan 
                <span class="font-medium">{{ $participants->firstItem() }}</span> 
                sampai 
                <span class="font-medium">{{ $participants->lastItem() }}</span> 
                dari 
                <span class="font-medium">{{ $participants->total() }}</span> 
                peserta
            </div>
        </div>
    @endif

    {{-- ============================================ --}}
    {{-- SCRIPT --}}
    {{-- ============================================ --}}
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

                const selectedAction = form.querySelector('select[name="action"]').value;
                const selectedHasAttempts = checks.some((el) => el.checked && el.dataset.hasAttempts === '1');

                const approved = await window.AppDialog.confirm({
                    title: 'Konfirmasi Aksi',
                    text: (selectedAction === 'delete' && selectedHasAttempts)
                        ? 'Beberapa peserta yang dipilih sudah memiliki riwayat pengerjaan tes. Menghapusnya akan ikut menghapus seluruh riwayat dan jawaban tesnya secara permanen. Lanjutkan?'
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
