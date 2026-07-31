<x-layouts.admin
    title="Manajemen Peserta"
    heading="Manajemen Peserta"
    subtitle="Kelola data akun peserta, tambah banyak peserta sekaligus, dan atur identitas."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.bulk-create.form') }}" class="btn btn-ghost">Buat Banyak Akun Peserta</a>
        <a href="{{ route('admin.participants.create') }}" class="btn btn-primary">Buat Akun Peserta</a>
    </x-slot:actions>

    <!-- Filter -->
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

    <!-- Per Page Selector -->
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
                            {{ request('per_page', 15) == $size ? 'selected' : '' }}>
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

    <!-- Table -->
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
                    <td><input type="checkbox" value="{{ $participant->id }}" data-row-check></td>
                    <td>{{ $participant->name }}</td>
                    <td>{{ $participant->username }}</td>
                    <td>{{ $participant->nik }}</td>
                    <td>{{ $participant->session->title ?? '-' }}</td>
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
                <tr><td colspan="6" class="text-center py-8 text-gray-500">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Bulk Action -->
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

<!-- ============================================ -->
<!-- PAGINATION CUSTOM (LANGSUNG DI VIEW) -->
<!-- ============================================ -->
@if ($participants->hasPages())
    <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 px-4 py-3 mt-6 sm:flex-row">
        <!-- Info -->
        <div class="text-sm text-gray-700">
            Menampilkan
            <span class="font-medium">{{ $participants->firstItem() }}</span>
            sampai
            <span class="font-medium">{{ $participants->lastItem() }}</span>
            dari
            <span class="font-medium">{{ $participants->total() }}</span>
            peserta
        </div>

        <!-- Tombol Pagination -->
        <div class="flex items-center gap-1 flex-wrap justify-center">
            {{-- Previous --}}
            @if ($participants->onFirstPage())
                <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                    ← Sebelumnya
                </span>
            @else
                <a href="{{ $participants->previousPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:border-gray-400 transition-all duration-150">
                    ← Sebelumnya
                </a>
            @endif

            {{-- Nomor Halaman --}}
            @foreach ($participants->links()->elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-2 text-sm text-gray-500">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $participants->currentPage())
                            <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-blue-600 border border-blue-600 rounded-md shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:border-gray-400 transition-all duration-150">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($participants->hasMorePages())
                <a href="{{ $participants->nextPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:border-gray-400 transition-all duration-150">
                    Selanjutnya →
                </a>
            @else
                <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                    Selanjutnya →
                </span>
            @endif
        </div>
    </div>
@endif

    <!-- JavaScript -->
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
