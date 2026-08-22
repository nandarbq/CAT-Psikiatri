<x-layouts.admin
    :title="'Tautkan Akun Peserta - '.$session->title"
    heading="Tautkan Akun Peserta"
    subtitle="Pilih akun peserta yang sudah ada untuk ditautkan ke sesi ini."
>
    <x-slot:actions>
        <a href="{{ route('admin.tokens.create', ['session_id' => $session->id]) }}" class="btn btn-primary">Lanjut Buat Token Sesi</a>
        <a href="{{ route('admin.sessions.edit', $session) }}" class="btn btn-ghost">Kembali ke Ubah Sesi</a>
        <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Lihat Manajemen Peserta</a>
    </x-slot:actions>

    @php
        $oldSelectedIds = collect(old('participant_ids', []))->map(fn ($id) => (string) $id)->all();
    @endphp

    <form method="GET" action="{{ route('admin.sessions.participants.bulk.form', $session) }}" class="filter-panel">
        <label class="field">
            <span>Cari Akun Peserta</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama / username / NIK / kode">
        </label>
        <label class="field">
            <span>Sesi Asal</span>
            <select class="input" name="source_session_id">
                <option value="">Semua sesi</option>
                @foreach ($sessionOptions as $sourceSession)
                    <option value="{{ $sourceSession->id }}" @selected((string) ($filters['source_session_id'] ?? '') === (string) $sourceSession->id)>
                        {{ $sourceSession->title }}
                    </option>
                @endforeach
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.sessions.participants.bulk.form', $session) }}" class="btn btn-ghost">Atur Ulang</a>
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
            Total: <span class="font-medium">{{ $participants->total() }}</span> akun
        </div>
    </div>

    <section class="admin-form-card">
        <p><strong>Sesi Tujuan:</strong> {{ $session->title }}</p>
        <small class="field-help">Akun yang masih mengerjakan tes atau punya kode bentrok pada sesi tujuan akan otomatis dilewati.</small>

        <form method="POST" action="{{ route('admin.sessions.participants.bulk.store', $session) }}" class="form-grid" data-bulk-participant-pick-form>
            @csrf

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th class="select-col"><input type="checkbox" data-select-all aria-label="Pilih semua akun eligible"></th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>NIK</th>
                        <th>Kode</th>
                        <th>Sesi Saat Ini</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($participants as $participant)
                        @php
                            $isInTargetSession = (int) $participant->session_id === (int) $session->id;
                            $hasActiveAttempt = in_array((int) $participant->id, $activeAttemptParticipantIds, true);
                            $hasCodeConflict = ! $isInTargetSession && isset($targetSessionCodeLookup[(string) $participant->code]);
                            $isEligible = ! $isInTargetSession && ! $hasActiveAttempt && ! $hasCodeConflict;
                        @endphp
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="participant_ids[]"
                                    value="{{ $participant->id }}"
                                    data-row-check
                                    @checked(in_array((string) $participant->id, $oldSelectedIds, true))
                                    @disabled(! $isEligible)
                                >
                            </td>
                            <td>{{ $participant->name }}</td>
                            <td>{{ $participant->username }}</td>
                            <td>{{ $participant->nik }}</td>
                            <td>{{ $participant->code }}</td>
                            <td>{{ $participant->session?->title ?? '-' }}</td>
                            <td>
                                @if ($isInTargetSession)
                                    <span class="status-chip ok">Sudah di sesi tujuan</span>
                                @elseif ($hasActiveAttempt)
                                    <span class="status-chip pending">Sedang mengerjakan</span>
                                @elseif ($hasCodeConflict)
                                    <span class="status-chip danger">Kode bentrok</span>
                                @else
                                    <span class="status-chip ok">Bisa dipilih</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Tidak ada akun peserta untuk filter ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $participants->links() }}

            <div class="bulk-bar">
                <p><strong data-selected-count>0</strong> akun dipilih</p>
                <button class="btn btn-primary" type="submit">Tautkan Akun Terpilih ke Sesi Ini</button>
            </div>

            <script>
                (() => {
                    const form = document.querySelector('[data-bulk-participant-pick-form]');
                    if (!form) return;

                    const checks = Array.from(form.querySelectorAll('[data-row-check]'));
                    const selectAll = form.querySelector('[data-select-all]');
                    const countEl = form.querySelector('[data-selected-count]');

                    const enabledChecks = () => checks.filter((el) => !el.disabled);
                    const selectedChecks = () => checks.filter((el) => el.checked && !el.disabled);

                    const sync = () => {
                        const enabled = enabledChecks();
                        const selected = selectedChecks();

                        if (countEl) {
                            countEl.textContent = String(selected.length);
                        }

                        if (selectAll) {
                            if (enabled.length === 0) {
                                selectAll.checked = false;
                                selectAll.indeterminate = false;
                                selectAll.disabled = true;
                                return;
                            }

                            selectAll.disabled = false;
                            selectAll.checked = selected.length > 0 && selected.length === enabled.length;
                            selectAll.indeterminate = selected.length > 0 && selected.length < enabled.length;
                        }
                    };

                    checks.forEach((check) => check.addEventListener('change', sync));

                    if (selectAll) {
                        selectAll.addEventListener('change', () => {
                            enabledChecks().forEach((check) => {
                                check.checked = selectAll.checked;
                            });
                            sync();
                        });
                    }

                    form.addEventListener('submit', async (event) => {
                        if (form.dataset.confirmed === '1') {
                            return;
                        }

                        event.preventDefault();

                        if (selectedChecks().length === 0) {
                            await window.AppDialog.alert({
                                title: 'Pilihan Belum Ada',
                                text: 'Pilih minimal satu akun peserta yang bisa dipilih.',
                                icon: 'warning',
                            });
                            return;
                        }

                        const approved = await window.AppDialog.confirm({
                            title: 'Konfirmasi Pemindahan',
                            text: 'Pindahkan peserta terpilih ke sesi ini?',
                            icon: 'question',
                            confirmButtonText: 'Ya, pindahkan',
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
        </form>
    </section>
</x-layouts.admin>
