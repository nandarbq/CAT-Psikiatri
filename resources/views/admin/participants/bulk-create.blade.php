<x-layouts.admin
    title="Buat Banyak Akun Peserta"
    heading="Buat Banyak Akun Peserta"
    subtitle="Buat banyak akun peserta sekaligus dan tentukan sesi tujuan."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Kembali ke Manajemen Peserta</a>
    </x-slot:actions>

    @php
        $rows = old('participants');
        if (! is_array($rows) || count($rows) === 0) {
            $rows = [[]];
        }
        $selectedSessionId = (string) old('session_id', '');
    @endphp

    <style>
        .bulk-create-toolbar {
            margin-bottom: 0.75rem;
        }

        .bulk-create-sticky {
            position: sticky;
            z-index: 20;
            border: 1px solid #d7ded4;
            background: #fff;
            box-shadow: 0 8px 20px rgb(13 36 25 / 0.08);
        }

        .bulk-create-toolbar.bulk-create-sticky {
            top: 0.75rem;
        }

        .bulk-create-summary.bulk-create-sticky {
            bottom: 0.75rem;
            margin-top: 0.9rem;
        }

        .bulk-create-toolbar .bulk-controls {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .bulk-create-session-field {
            display: flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            margin: 0 !important;
            min-width: 260px;
            flex: 1 1 360px;
        }

        .bulk-create-session-field > span {
            margin: 0 !important;
            white-space: nowrap;
        }

        .bulk-create-session-field .input {
            width: min(100%, 460px);
            min-width: 240px;
        }

        .bulk-create-card-list {
            display: grid;
            gap: 0.8rem;
            margin-top: 0.9rem;
        }

        .bulk-create-card {
            border: 1px solid #d7ded4;
            border-radius: 0.9rem;
            background: #f7f6f2;
            padding: 0.85rem;
        }

        .bulk-create-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            margin-bottom: 0.6rem;
        }

        .bulk-create-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .bulk-create-card-grid .field {
            margin: 0;
        }

        @media (width <= 768px) {
            .bulk-create-card-grid {
                grid-template-columns: 1fr;
            }

            .bulk-create-session-field {
                display: grid !important;
                align-items: initial !important;
                gap: 0.4rem !important;
            }

            .bulk-create-session-field .input {
                width: 100%;
                min-width: 100%;
            }

            .bulk-create-toolbar .bulk-controls {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <section class="admin-form-card">
        <form method="POST" action="{{ route('admin.participants.bulk-create.store') }}" data-bulk-create-form>
            @csrf

            <div class="bulk-bar bulk-create-toolbar bulk-create-sticky">
                <label class="field bulk-create-session-field" for="bulk-create-session">
                    <span>Sesi Tujuan</span>
                    <select class="input" id="bulk-create-session" name="session_id" required>
                        <option value="">Pilih sesi</option>
                        @foreach ($sessionOptions as $session)
                            <option value="{{ $session->id }}" @selected($selectedSessionId === (string) $session->id)>{{ $session->title }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="bulk-controls">
                    <button type="button" class="btn btn-ghost" data-add-row>Tambah Baris</button>
                    <button type="submit" class="btn btn-primary">Simpan Peserta Sekaligus</button>
                </div>
            </div>

            <small class="field-help">`code` peserta akan dibuat otomatis oleh sistem, status selesai otomatis `Belum`.</small>

            <div class="bulk-create-card-list" data-rows>
                @foreach ($rows as $index => $row)
                    <article class="bulk-create-card" data-row>
                        <div class="bulk-create-card-head">
                            <strong>Peserta <span data-row-number>{{ $loop->iteration }}</span></strong>
                            <button type="button" class="btn btn-ghost" data-remove-row>Hapus</button>
                        </div>
                        <div class="bulk-create-card-grid">
                            <label class="field">
                                <span>NIK</span>
                                <input class="input" type="text" name="participants[{{ $index }}][nik]" value="{{ $row['nik'] ?? '' }}" required>
                            </label>
                            <label class="field">
                                <span>Username</span>
                                <input class="input" type="text" name="participants[{{ $index }}][username]" value="{{ $row['username'] ?? '' }}" required>
                            </label>
                            <label class="field">
                                <span>Password</span>
                                <input class="input" type="password" name="participants[{{ $index }}][password]" required>
                            </label>
                            <label class="field">
                                <span>Nama</span>
                                <input class="input" type="text" name="participants[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" required>
                            </label>
                            <label class="field">
                                <span>Tanggal Lahir</span>
                                <input class="input" type="date" name="participants[{{ $index }}][birth_date]" value="{{ $row['birth_date'] ?? '' }}" required>
                            </label>
                            <label class="field">
                                <span>Gender</span>
                                <select class="input" name="participants[{{ $index }}][gender]" required>
                                    <option value="male" @selected(($row['gender'] ?? '') === 'male')>Laki-laki</option>
                                    <option value="female" @selected(($row['gender'] ?? '') === 'female')>Perempuan</option>
                                </select>
                            </label>
                            <label class="field">
                                <span>No. HP</span>
                                <input class="input" type="text" name="participants[{{ $index }}][phone]" value="{{ $row['phone'] ?? '' }}">
                            </label>
                            <label class="field">
                                <span>Alamat</span>
                                <textarea class="input" name="participants[{{ $index }}][address]" rows="2">{{ $row['address'] ?? '' }}</textarea>
                            </label>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="bulk-bar bulk-create-summary bulk-create-sticky">
                <p><strong data-row-count>{{ count($rows) }}</strong> baris peserta</p>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-bulk-create-form]');
            if (!form) return;

            const tbody = form.querySelector('[data-rows]');
            const addBtn = form.querySelector('[data-add-row]');
            const rowCountEl = form.querySelector('[data-row-count]');
            if (!tbody || !addBtn || !rowCountEl) return;

            let nextIndex = tbody.querySelectorAll('[data-row]').length;

            const rowTemplate = (index) => `
                <article class="bulk-create-card" data-row>
                    <div class="bulk-create-card-head">
                        <strong>Peserta <span data-row-number></span></strong>
                        <button type="button" class="btn btn-ghost" data-remove-row>Hapus</button>
                    </div>
                    <div class="bulk-create-card-grid">
                        <label class="field">
                            <span>NIK</span>
                            <input class="input" type="text" name="participants[${index}][nik]" required>
                        </label>
                        <label class="field">
                            <span>Username</span>
                            <input class="input" type="text" name="participants[${index}][username]" required>
                        </label>
                        <label class="field">
                            <span>Password</span>
                            <input class="input" type="password" name="participants[${index}][password]" required>
                        </label>
                        <label class="field">
                            <span>Nama</span>
                            <input class="input" type="text" name="participants[${index}][name]" required>
                        </label>
                        <label class="field">
                            <span>Tanggal Lahir</span>
                            <input class="input" type="date" name="participants[${index}][birth_date]" required>
                        </label>
                        <label class="field">
                            <span>Gender</span>
                            <select class="input" name="participants[${index}][gender]" required>
                                <option value="male">Laki-laki</option>
                                <option value="female">Perempuan</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>No. HP</span>
                            <input class="input" type="text" name="participants[${index}][phone]">
                        </label>
                        <label class="field">
                            <span>Alamat</span>
                            <textarea class="input" name="participants[${index}][address]" rows="2"></textarea>
                        </label>
                    </div>
                </article>
            `;

            const syncNumbers = () => {
                const rows = Array.from(tbody.querySelectorAll('[data-row]'));
                rows.forEach((row, index) => {
                    const number = row.querySelector('[data-row-number]');
                    if (number) number.textContent = String(index + 1);
                });
            };

            const syncCount = () => {
                rowCountEl.textContent = String(tbody.querySelectorAll('[data-row]').length);
                syncNumbers();
            };

            const removeRow = (button) => {
                const rows = tbody.querySelectorAll('[data-row]');
                if (rows.length <= 1) {
                    window.AppDialog.alert({
                        title: 'Baris Tidak Bisa Dihapus',
                        text: 'Minimal harus ada satu baris peserta.',
                        icon: 'warning',
                    });
                    return;
                }
                button.closest('[data-row]')?.remove();
                syncCount();
            };

            tbody.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                const removeBtn = target.closest('[data-remove-row]');
                if (removeBtn instanceof HTMLButtonElement) {
                    removeRow(removeBtn);
                }
            });

            addBtn.addEventListener('click', () => {
                tbody.insertAdjacentHTML('beforeend', rowTemplate(nextIndex));
                nextIndex += 1;
                syncCount();
            });

            syncCount();
        })();
    </script>
</x-layouts.admin>
