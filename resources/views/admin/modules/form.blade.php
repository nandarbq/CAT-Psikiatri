<x-layouts.admin
    :title="$isEdit ? 'Kelola Modul' : 'Buat Modul'"
    :heading="$isEdit ? 'Kelola Modul' : 'Buat Modul'"
    :subtitle="$isEdit
        ? 'Ubah info modul dan kelola daftar soal di dalamnya.'
        : 'Isi info modul lalu tambahkan soal sebanyak yang dibutuhkan. Modul baru langsung berisi soal saat dibuat.'"
>
    <x-slot:actions>
        <a href="{{ route('admin.modules.index') }}" class="btn btn-ghost">Kembali ke Daftar Modul</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.modules.update', $module) : route('admin.modules.store') }}" class="form-grid" data-module-info-form>
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Nama Modul</span>
                <input class="input" type="text" name="name" required maxlength="150" value="{{ old('name', $module->name) }}">
            </label>
            <label class="field">
                <span>Deskripsi</span>
                <textarea class="input" name="description" rows="3" maxlength="2000">{{ old('description', $module->description) }}</textarea>
            </label>
            <label class="field">
                <span>Status</span>
                <select class="input" name="status" required>
                    <option value="1" @selected((string) old('status', (int) ($module->status ?? true)) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('status', (int) ($module->status ?? true)) === '0')>Nonaktif</option>
                </select>
            </label>

            @unless ($isEdit)
                <div class="field">
                    <span>Inputkan Banyak Soal Sekaligus</span>
                    <textarea
                        class="input"
                        rows="6"
                        placeholder="Tempel atau ketik soal di sini, satu soal per baris. Contoh:&#10;Saya mudah merasa cemas saat menghadapi perubahan.&#10;Saya nyaman bekerja sama dalam tim.&#10;Saya sering menunda pekerjaan sampai mendekati tenggat waktu."
                        data-bulk-paste-input
                    ></textarea>
                    <p class="question-builder-summary" data-bulk-paste-preview>Belum ada soal yang ditempel.</p>
                    <div class="question-builder-actions">
                        <button type="button" class="btn btn-primary" data-bulk-paste-apply>Jadikan Daftar Soal</button>
                    </div>
                    <p class="field-help">
                        Setiap baris otomatis jadi satu soal terpisah dan diberi nomor urut. Baris kosong akan dilewati. Kalau teksmu sudah bernomor duluan (mis. "1. Saya merasa..."), nomor itu otomatis dibuang — tidak ikut tersimpan sebagai isi soal. Setelah diproses, kamu masih bisa cek, sunting, atau hapus satu-satu di bawah sebelum menyimpan.
                    </p>
                </div>

                @php
                    $rawRows = old('questions');
                    $rows = is_array($rawRows) && count($rawRows) > 0
                        ? array_values($rawRows)
                        : [['question' => '', 'is_active' => '1']];
                @endphp

                <div class="field">
                    <span>Soal Modul</span>
                    <div class="question-builder-list" data-question-list>
                        @foreach ($rows as $index => $row)
                            <article class="question-builder-item" data-row draggable="true">
                                <div class="question-builder-head">
                                    <div class="question-builder-head-meta">
                                        <button type="button" class="question-builder-drag-handle" data-drag-row-handle aria-label="Geser urutan pertanyaan" title="Geser untuk ubah urutan">
                                            <span class="question-builder-drag-icon" aria-hidden="true">⋮⋮</span>
                                            <span class="question-builder-drag-text">Geser</span>
                                        </button>
                                        <div>
                                        <strong>Pertanyaan <span data-row-number>{{ $loop->iteration }}</span></strong>
                                        <p class="question-builder-summary" data-row-summary></p>
                                        </div>
                                    </div>
                                    <div class="question-builder-head-actions">
                                        <button type="button" class="btn btn-ghost" data-toggle-row aria-expanded="true">Tutup</button>
                                        <button type="button" class="btn btn-ghost" data-remove-row>Hapus</button>
                                    </div>
                                </div>

                                <div class="question-builder-body" data-row-body>
                                    <label class="field">
                                        <span>Pertanyaan</span>
                                        <textarea class="input" name="questions[{{ $index }}][question]" rows="4" required>{{ $row['question'] ?? '' }}</textarea>
                                    </label>

                                    <label class="field">
                                        <span>Status</span>
                                        <select class="input" name="questions[{{ $index }}][is_active]" required>
                                            <option value="1" @selected((string) ($row['is_active'] ?? '1') === '1')>Aktif</option>
                                            <option value="0" @selected((string) ($row['is_active'] ?? '1') === '0')>Nonaktif</option>
                                        </select>
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="question-builder-actions">
                        <button type="button" class="btn btn-ghost" data-add-row>Tambah Pertanyaan</button>
                    </div>
                </div>
            @endunless

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan Modul' : 'Buat Modul' }}</button>
        </form>
    </section>

    @if ($isEdit)
        @php $existingQuestions = $module->questions; @endphp

        <section class="admin-form-card" style="margin-top: 1rem;">
            <h2 class="title-md" style="margin-top: 0;">Soal dalam Modul Ini ({{ $existingQuestions->count() }})</h2>
            <p class="subtitle">Tarik kartu untuk ubah urutan. Perubahan urutan tersimpan otomatis.</p>

            <p class="question-bank-toast" data-question-toast aria-live="polite"></p>

            <section
                class="question-bank-board"
                data-question-board
                data-module-id="{{ $module->id }}"
                data-reorder-url="{{ route('admin.questions.reorder') }}"
            >
                @forelse ($existingQuestions as $question)
                    <article class="question-bank-card" data-question-card data-question-id="{{ $question->id }}" draggable="true">
                        <div class="question-bank-card-head">
                            <div class="question-bank-order-wrap">
                                <span class="question-bank-order-label">Urutan</span>
                                <strong class="question-bank-order-no" data-order-label>{{ $question->order_no }}</strong>
                            </div>
                            <button type="button" class="question-bank-drag-handle" aria-label="Tarik untuk ubah urutan soal" title="Tarik untuk ubah urutan">
                                <span class="question-bank-drag-icon" aria-hidden="true">⋮⋮</span>
                                <span class="question-bank-drag-text">Geser</span>
                            </button>
                        </div>

                        <p class="question-bank-text">{{ $question->question }}</p>

                        <div class="question-bank-card-foot">
                            <span class="status-chip {{ $question->is_active ? 'ok' : 'pending' }}">{{ $question->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            <div class="table-actions">
                                <a href="{{ route('admin.questions.edit', $question) }}" class="table-link">Ubah</a>
                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="inline-form" data-confirm-message="Hapus soal ini?" data-confirm-title="Konfirmasi Hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link-button">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="question-bank-empty">
                        Belum ada soal pada modul ini. Tambahkan soal lewat form di bawah.
                    </article>
                @endforelse
            </section>
        </section>

        <section class="admin-form-card" style="margin-top: 1rem;">
            <h2 class="title-md" style="margin-top: 0;">Tambah Soal Baru</h2>
            <p class="subtitle">Admin dapat menambahkan satu atau beberapa soal sekaligus ke modul ini.</p>

            <form method="POST" action="{{ route('admin.modules.questions.store', $module) }}" class="form-grid" data-question-builder-form>
                @csrf

                <div class="field">
                    <span>Tempelkan Soal Sekaligus</span>
                    <textarea
                        class="input"
                        rows="6"
                        placeholder="Tempel atau ketik soal di sini, satu soal per baris."
                        data-bulk-paste-input
                    ></textarea>
                    <p class="question-builder-summary" data-bulk-paste-preview>Belum ada soal yang ditempel.</p>
                    <div class="question-builder-actions">
                        <button type="button" class="btn btn-primary" data-bulk-paste-apply>Jadikan Daftar Soal</button>
                    </div>
                    <p class="field-help">
                        Setiap baris otomatis jadi satu soal terpisah dan diberi nomor urut. Baris kosong akan dilewati. Kalau teksmu sudah bernomor duluan (mis. "1. Saya merasa..."), nomor itu otomatis dibuang — tidak ikut tersimpan sebagai isi soal.
                    </p>
                </div>

                <div class="question-builder-list" data-question-list data-start-number="{{ $existingQuestions->count() + 1 }}">
                    <article class="question-builder-item" data-row draggable="true">
                        <div class="question-builder-head">
                            <div class="question-builder-head-meta">
                                <button type="button" class="question-builder-drag-handle" data-drag-row-handle aria-label="Geser urutan pertanyaan" title="Geser untuk ubah urutan">
                                    <span class="question-builder-drag-icon" aria-hidden="true">⋮⋮</span>
                                    <span class="question-builder-drag-text">Geser</span>
                                </button>
                                <div>
                                <strong>Pertanyaan <span data-row-number>{{ $existingQuestions->count() + 1 }}</span></strong>
                                <p class="question-builder-summary" data-row-summary></p>
                                </div>
                            </div>
                            <div class="question-builder-head-actions">
                                <button type="button" class="btn btn-ghost" data-toggle-row aria-expanded="true">Tutup</button>
                                <button type="button" class="btn btn-ghost" data-remove-row>Hapus</button>
                            </div>
                        </div>

                        <div class="question-builder-body" data-row-body>
                            <label class="field">
                                <span>Pertanyaan</span>
                                <textarea class="input" name="questions[0][question]" rows="4" required></textarea>
                            </label>

                            <label class="field">
                                <span>Status</span>
                                <select class="input" name="questions[0][is_active]" required>
                                    <option value="1" selected>Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </label>
                        </div>
                    </article>
                </div>

                <div class="question-builder-actions">
                    <button type="button" class="btn btn-ghost" data-add-row>Tambah Pertanyaan</button>
                    <button class="btn btn-primary" type="submit">Simpan Soal Baru</button>
                </div>
            </form>
        </section>
    @endif

    <script>
        (() => {
            document.querySelectorAll('[data-question-builder-form], [data-module-info-form]').forEach((form) => {
                const list = form.querySelector('[data-question-list]');
                const addBtn = form.querySelector('[data-add-row]');
                if (!list || !(addBtn instanceof HTMLButtonElement)) return;

                let nextIndex = list.querySelectorAll('[data-row]').length;

                const rowTemplate = (index) => `
                    <article class="question-builder-item" data-row draggable="true">
                        <div class="question-builder-head">
                            <div class="question-builder-head-meta">
                                <button type="button" class="question-builder-drag-handle" data-drag-row-handle aria-label="Geser urutan pertanyaan" title="Geser untuk ubah urutan">
                                    <span class="question-builder-drag-icon" aria-hidden="true">⋮⋮</span>
                                    <span class="question-builder-drag-text">Geser</span>
                                </button>
                                <div>
                                <strong>Pertanyaan <span data-row-number></span></strong>
                                <p class="question-builder-summary" data-row-summary></p>
                                </div>
                            </div>
                            <div class="question-builder-head-actions">
                                <button type="button" class="btn btn-ghost" data-toggle-row aria-expanded="true">Tutup</button>
                                <button type="button" class="btn btn-ghost" data-remove-row>Hapus</button>
                            </div>
                        </div>

                        <div class="question-builder-body" data-row-body>
                            <label class="field">
                                <span>Pertanyaan</span>
                                <textarea class="input" name="questions[${index}][question]" rows="4" required></textarea>
                            </label>

                            <label class="field">
                                <span>Status</span>
                                <select class="input" name="questions[${index}][is_active]" required>
                                    <option value="1" selected>Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </label>
                        </div>
                    </article>
                `;

                const syncNumbers = () => {
                    const startNumber = Number(list.dataset.startNumber || '1') || 1;
                    const rows = Array.from(list.querySelectorAll('[data-row]'));
                    rows.forEach((row, index) => {
                        const number = row.querySelector('[data-row-number]');
                        if (number) {
                            number.textContent = String(startNumber + index);
                        }
                        syncSummary(row);
                    });
                };

                const syncSummary = (row) => {
                    if (!(row instanceof HTMLElement)) return;
                    const summaryEl = row.querySelector('[data-row-summary]');
                    const questionInput = row.querySelector('textarea[name$="[question]"]');
                    if (!(summaryEl instanceof HTMLElement)) return;

                    const rawText = questionInput instanceof HTMLTextAreaElement
                        ? questionInput.value.trim().replace(/\s+/g, ' ')
                        : '';
                    const shortText = rawText === '' ? 'Belum ada pertanyaan.' : rawText.slice(0, 100);

                    summaryEl.textContent = `${shortText}${rawText.length > 100 ? '…' : ''}`;
                };

                const toggleRow = (row, forceCollapsed = null) => {
                    if (!(row instanceof HTMLElement)) return;
                    const shouldCollapse = typeof forceCollapsed === 'boolean'
                        ? forceCollapsed
                        : !row.classList.contains('is-collapsed');
                    const toggleBtn = row.querySelector('[data-toggle-row]');
                    if (!(toggleBtn instanceof HTMLButtonElement)) return;

                    row.classList.toggle('is-collapsed', shouldCollapse);
                    toggleBtn.textContent = shouldCollapse ? 'Buka' : 'Tutup';
                    toggleBtn.setAttribute('aria-expanded', shouldCollapse ? 'false' : 'true');
                };

                const removeRow = async (button) => {
                    const rows = list.querySelectorAll('[data-row]');
                    if (rows.length <= 1) {
                        await window.AppDialog.alert({
                            title: 'Pertanyaan Tidak Bisa Dihapus',
                            text: 'Minimal harus ada satu pertanyaan.',
                            icon: 'warning',
                        });
                        return;
                    }

                    button.closest('[data-row]')?.remove();
                    syncNumbers();
                };

                list.addEventListener('click', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const removeBtn = target.closest('[data-remove-row]');
                    if (removeBtn instanceof HTMLButtonElement) {
                        removeRow(removeBtn);
                        return;
                    }

                    const toggleBtn = target.closest('[data-toggle-row]');
                    if (!(toggleBtn instanceof HTMLButtonElement)) return;
                    toggleRow(toggleBtn.closest('[data-row]'));
                });

                const addOneRow = ({ questionText = '', focus = true, collapsed = false } = {}) => {
                    list.insertAdjacentHTML('beforeend', rowTemplate(nextIndex));
                    nextIndex += 1;
                    const latestRow = list.querySelector('[data-row]:last-of-type');
                    if (latestRow instanceof HTMLElement) {
                        const textarea = latestRow.querySelector('textarea[name$="[question]"]');
                        if (textarea instanceof HTMLTextAreaElement) {
                            textarea.value = questionText;
                        }
                        toggleRow(latestRow, collapsed);
                        if (focus && textarea instanceof HTMLTextAreaElement) {
                            textarea.focus();
                        }
                    }
                    return latestRow;
                };

                addBtn.addEventListener('click', () => {
                    addOneRow({ focus: true, collapsed: false });
                    syncNumbers();
                });

                list.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const row = target.closest('[data-row]');
                    syncSummary(row);
                });

                list.addEventListener('change', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const row = target.closest('[data-row]');
                    syncSummary(row);
                });

                let draggedRow = null;

                const rowsOf = () => Array.from(list.querySelectorAll('[data-row]'));

                const findDropTarget = (pointerY) => {
                    return rowsOf()
                        .filter((row) => row !== draggedRow)
                        .find((row) => pointerY <= (row.getBoundingClientRect().top + (row.clientHeight / 2)));
                };

                let armedDragRow = null;

                list.addEventListener('pointerdown', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const handle = target.closest('[data-drag-row-handle]');
                    if (!(handle instanceof HTMLElement)) {
                        armedDragRow = null;
                        return;
                    }

                    armedDragRow = handle.closest('[data-row]');
                });

                list.addEventListener('dragstart', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const row = target.closest('[data-row]');
                    if (!(row instanceof HTMLElement)) return;

                    if (armedDragRow !== row) {
                        event.preventDefault();
                        return;
                    }

                    draggedRow = row;
                    row.classList.add('is-dragging');
                    list.classList.add('is-drag-active');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                    }
                });

                list.addEventListener('dragover', (event) => {
                    if (!(draggedRow instanceof HTMLElement)) return;
                    event.preventDefault();
                    const dropTarget = findDropTarget(event.clientY);

                    if (dropTarget) {
                        list.insertBefore(draggedRow, dropTarget);
                    } else {
                        list.appendChild(draggedRow);
                    }
                });

                list.addEventListener('dragend', () => {
                    if (!(draggedRow instanceof HTMLElement)) return;
                    draggedRow.classList.remove('is-dragging');
                    list.classList.remove('is-drag-active');
                    draggedRow = null;
                    armedDragRow = null;
                    syncNumbers();
                });

                syncNumbers();

                // -------------------------------------------------------------
                // Tempel Banyak Soal Sekaligus: pisahkan tiap baris teks jadi
                // satu baris form soal terpisah, tanpa perlu klik "Tambah
                // Pertanyaan" berkali-kali. Baris kosong dilewati.
                // -------------------------------------------------------------
                const bulkInput = form.querySelector('[data-bulk-paste-input]');
                const bulkPreview = form.querySelector('[data-bulk-paste-preview]');
                const bulkApplyBtn = form.querySelector('[data-bulk-paste-apply]');

                if (bulkInput instanceof HTMLTextAreaElement && bulkPreview instanceof HTMLElement && bulkApplyBtn instanceof HTMLButtonElement) {
                    // Kalau admin nempel teks yang sudah bernomor duluan (mis. dari
                    // Word: "1. Saya merasa...", "2) Saya nyaman..."), nomor di
                    // depan itu cuma penanda visual saat menempel — bukan bagian
                    // dari isi soal, jadi harus dibuang sebelum disimpan.
                    const stripLeadingNumber = (line) => line.replace(/^\s*\(?\d{1,4}\)?\s*[.\-:)]\s*/, '').trim();

                    const parseLines = () => bulkInput.value
                        .split('\n')
                        .map((line) => stripLeadingNumber(line.trim()))
                        .filter((line) => line.length > 0);

                    const updateBulkPreview = () => {
                        const lines = parseLines();
                        if (lines.length === 0) {
                            bulkPreview.textContent = 'Belum ada soal yang ditempel.';
                            return;
                        }
                        const previewStart = Number(list.dataset.startNumber || '1') || 1;
                        const preview = lines
                            .slice(0, 5)
                            .map((line, index) => `${previewStart + index}. ${line.slice(0, 60)}${line.length > 60 ? '…' : ''}`)
                            .join('  ');
                        const extra = lines.length > 5 ? ` … dan ${lines.length - 5} soal lagi` : '';
                        bulkPreview.textContent = `${lines.length} soal terdeteksi: ${preview}${extra}`;
                    };

                    bulkInput.addEventListener('input', updateBulkPreview);
                    updateBulkPreview();

                    bulkApplyBtn.addEventListener('click', async () => {
                        const lines = parseLines();
                        if (lines.length === 0) {
                            await window.AppDialog.alert({
                                title: 'Belum Ada Soal',
                                text: 'Tempel atau ketik minimal satu baris soal terlebih dahulu.',
                                icon: 'warning',
                            });
                            return;
                        }

                        // Kalau baris pertama form masih kosong (form baru dibuka,
                        // belum diisi apa-apa), pakai baris itu untuk soal pertama
                        // supaya tidak menyisakan satu form kosong yang mengganjal.
                        const firstRow = list.querySelector('[data-row]');
                        const firstTextarea = firstRow?.querySelector('textarea[name$="[question]"]');
                        let startIndex = 0;
                        if (firstTextarea instanceof HTMLTextAreaElement && firstTextarea.value.trim() === '' && list.querySelectorAll('[data-row]').length === 1) {
                            firstTextarea.value = lines[0];
                            startIndex = 1;
                        }

                        for (let i = startIndex; i < lines.length; i++) {
                            addOneRow({ questionText: lines[i], focus: false, collapsed: true });
                        }

                        syncNumbers();
                        bulkInput.value = '';
                        updateBulkPreview();

                        await window.AppDialog.alert({
                            title: 'Soal Ditambahkan',
                            text: `${lines.length} soal berhasil dijadikan daftar soal. Periksa kembali soal yang ditambahkan, edit atau hapus baris tertentu sebelum menyimpan.`,
                            icon: 'success',
                        });
                    });
                }
            });
        })();
    </script>

    @if ($isEdit)
        <script>
            (() => {
                const board = document.querySelector('[data-question-board]');
                if (!board) return;

                const reorderUrl = board.dataset.reorderUrl;
                const moduleId = board.dataset.moduleId;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const toastEl = document.querySelector('[data-question-toast]');
                let toastTimerId = null;

                const orderIds = () => Array.from(board.querySelectorAll('[data-question-card]'))
                    .map((card) => Number(card.getAttribute('data-question-id')))
                    .filter((id) => Number.isInteger(id) && id > 0);

                const syncOrderLabels = () => {
                    const cards = Array.from(board.querySelectorAll('[data-question-card]'));
                    cards.forEach((card, index) => {
                        const label = card.querySelector('[data-order-label]');
                        if (label) {
                            label.textContent = String(index + 1);
                        }
                    });
                };

                const restoreOrder = (ids) => {
                    const cardMap = new Map(
                        Array.from(board.querySelectorAll('[data-question-card]'))
                            .map((card) => [Number(card.getAttribute('data-question-id')), card])
                    );

                    ids.forEach((id) => {
                        const card = cardMap.get(id);
                        if (card) {
                            board.appendChild(card);
                        }
                    });

                    syncOrderLabels();
                };

                const showToast = (message, type = 'success') => {
                    if (!(toastEl instanceof HTMLElement)) return;
                    toastEl.textContent = message;
                    toastEl.className = `question-bank-toast is-visible is-${type}`;
                    if (toastTimerId !== null) {
                        window.clearTimeout(toastTimerId);
                    }
                    toastTimerId = window.setTimeout(() => {
                        toastEl.className = 'question-bank-toast';
                        toastTimerId = null;
                    }, 1800);
                };

                const saveOrder = async (beforeIds, afterIds) => {
                    try {
                        const response = await fetch(reorderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ module_id: Number(moduleId), ids: afterIds }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || data.success !== true) {
                            throw new Error(data.message ?? 'Gagal menyimpan urutan soal.');
                        }
                        showToast('Urutan soal tersimpan.', 'success');
                    } catch (error) {
                        restoreOrder(beforeIds);
                        showToast('Urutan gagal disimpan. Posisi dikembalikan.', 'error');
                        await window.AppDialog.alert({
                            title: 'Urutan Gagal Disimpan',
                            text: error instanceof Error ? error.message : 'Terjadi gangguan saat menyimpan urutan.',
                            icon: 'error',
                        });
                    }
                };

                syncOrderLabels();

                let draggedCard = null;
                let beforeDragIds = [];

                const cards = () => Array.from(board.querySelectorAll('[data-question-card]'));

                const findDropTarget = (pointerY) => {
                    return cards()
                        .filter((card) => card !== draggedCard)
                        .find((card) => pointerY <= (card.getBoundingClientRect().top + (card.clientHeight / 2)));
                };

                board.addEventListener('dragstart', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) return;
                    const card = target.closest('[data-question-card]');
                    if (!(card instanceof HTMLElement)) return;
                    draggedCard = card;
                    beforeDragIds = orderIds();
                    card.classList.add('is-dragging');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                    }
                });

                board.addEventListener('dragover', (event) => {
                    if (!(draggedCard instanceof HTMLElement)) return;
                    event.preventDefault();
                    const dropTarget = findDropTarget(event.clientY);
                    if (dropTarget) {
                        board.insertBefore(draggedCard, dropTarget);
                    } else {
                        board.appendChild(draggedCard);
                    }
                });

                board.addEventListener('dragend', async () => {
                    if (!(draggedCard instanceof HTMLElement)) return;
                    draggedCard.classList.remove('is-dragging');
                    draggedCard = null;

                    const afterDragIds = orderIds();
                    if (beforeDragIds.length === 0 || beforeDragIds.join(',') === afterDragIds.join(',')) {
                        return;
                    }

                    syncOrderLabels();
                    await saveOrder(beforeDragIds, afterDragIds);
                });
            })();
        </script>
    @endif
</x-layouts.admin>
