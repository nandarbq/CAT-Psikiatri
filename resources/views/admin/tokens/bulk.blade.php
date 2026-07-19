<x-layouts.admin
    title="Buat Token Sesi Sekaligus"
    heading="Buat Token Sesi Sekaligus"
    subtitle="Buat token unik 6 digit untuk banyak sesi dalam satu aksi."
>
    @php
        $oldSessionIds = collect(old('session_ids', $defaultSessionIds ?? []))->map(fn ($id) => (string) $id)->all();
    @endphp

    <x-slot:actions>
        <a href="{{ route('admin.tokens.index') }}" class="btn btn-ghost">Kembali ke Daftar Token</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ route('admin.tokens.bulk') }}" class="form-grid" data-token-bulk-form>
            @csrf

            <label class="field">
                <span>Status Token</span>
                <select class="input" name="is_active" required>
                    <option value="1" @selected((string) old('is_active', '1') === '1')>Aktif</option>
                    <option value="0" @selected((string) old('is_active', '1') === '0')>Nonaktif</option>
                </select>
            </label>

            <label class="field">
                <span>Kedaluwarsa (opsional)</span>
                <input class="input" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
            </label>

            <div class="field">
                <span>Pilih Sesi</span>
                <div class="token-bulk-actions">
                    <button type="button" class="btn btn-ghost" data-select-all-sessions>Pilih Semua</button>
                    <button type="button" class="btn btn-ghost" data-clear-sessions>Bersihkan Pilihan</button>
                </div>
                <div class="token-bulk-list" data-session-list>
                    @foreach ($sessionOptions as $session)
                        @php
                            $hasToken = isset($tokenSessionIdLookup[(int) $session->id]);
                        @endphp
                        <label class="token-bulk-item {{ $hasToken ? 'has-token' : '' }}">
                            <span class="token-bulk-item-main">
                                <input
                                    type="checkbox"
                                    name="session_ids[]"
                                    value="{{ $session->id }}"
                                    data-session-check
                                    @checked(in_array((string) $session->id, $oldSessionIds, true))
                                    @disabled($hasToken)
                                >
                                <span>{{ $session->title }}</span>
                            </span>
                            <span class="status-chip {{ $hasToken ? 'pending' : 'ok' }}">
                                {{ $hasToken ? 'Sudah ada token' : 'Siap generate' }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="subtitle-tight token-bulk-summary">
                    <strong data-selected-count>0</strong> sesi dipilih dari
                    <strong data-eligible-count>0</strong> sesi yang bisa digenerate.
                </p>
                <small class="field-help">Sesi yang sudah memiliki token akan otomatis dikunci.</small>
            </div>

            <button class="btn btn-primary" type="submit">Buat Token untuk Sesi Terpilih</button>
        </form>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-token-bulk-form]');
            if (!form) return;

            const checks = Array.from(form.querySelectorAll('[data-session-check]'));
            const selectAllBtn = form.querySelector('[data-select-all-sessions]');
            const clearBtn = form.querySelector('[data-clear-sessions]');
            const selectedCountEl = form.querySelector('[data-selected-count]');
            const eligibleCountEl = form.querySelector('[data-eligible-count]');

            const enabledChecks = () => checks.filter((check) => check instanceof HTMLInputElement && !check.disabled);
            const selectedChecks = () => checks.filter((check) => check instanceof HTMLInputElement && !check.disabled && check.checked);

            const sync = () => {
                const eligible = enabledChecks().length;
                const selected = selectedChecks().length;
                if (selectedCountEl) {
                    selectedCountEl.textContent = String(selected);
                }
                if (eligibleCountEl) {
                    eligibleCountEl.textContent = String(eligible);
                }
                if (selectAllBtn instanceof HTMLButtonElement) {
                    selectAllBtn.disabled = eligible === 0;
                }
            };

            checks.forEach((check) => {
                check.addEventListener('change', sync);
            });

            selectAllBtn?.addEventListener('click', () => {
                enabledChecks().forEach((check) => {
                    check.checked = true;
                });
                sync();
            });

            clearBtn?.addEventListener('click', () => {
                enabledChecks().forEach((check) => {
                    check.checked = false;
                });
                sync();
            });

            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();

                if (selectedChecks().length === 0) {
                    await window.AppDialog.alert({
                        title: 'Pilihan Belum Ada',
                        text: 'Pilih minimal satu sesi yang belum punya token.',
                        icon: 'warning',
                    });
                    return;
                }

                const approved = await window.AppDialog.confirm({
                    title: 'Konfirmasi Pembuatan Token',
                    text: 'Buat token sesi untuk pilihan saat ini?',
                    icon: 'question',
                    confirmButtonText: 'Ya, buat token',
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

