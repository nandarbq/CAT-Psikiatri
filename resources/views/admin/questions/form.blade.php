<x-layouts.admin
    :title="$isEdit ? 'Ubah Soal' : 'Buat Soal (Bulk)'"
    :heading="$isEdit ? 'Ubah Soal' : 'Buat Soal (Bulk)'"
    :subtitle="$isEdit
        ? 'Ubah isi soal sekaligus urutannya. Sistem akan merapikan nomor soal lain otomatis.'
        : 'Urutan soal dibuat otomatis berdasarkan urutan kartu.'"
>
    <x-slot:actions>
        <a href="{{ route('admin.questions.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    <section class="admin-form-card">
        @if ($isEdit)
            <form method="POST" action="{{ route('admin.questions.update', $question) }}" class="form-grid">
                @csrf
                @method('PUT')

                <label class="field">
                    <span>Kategori</span>
                    <select class="input" name="category_id" required>
                        <option value="">Pilih Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $question->category_id) === (string) $category->id)>
                                {{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Pertanyaan</span>
                    <textarea class="input" name="question" rows="5" required>{{ old('question', $question->question) }}</textarea>
                </label>

                <label class="field">
                    <span>Status</span>
                    <select class="input" name="is_active" required>
                        <option value="1" @selected((string) old('is_active', (int) ($question->is_active ?? true)) === '1')>Aktif</option>
                        <option value="0" @selected((string) old('is_active', (int) ($question->is_active ?? true)) === '0')>Nonaktif</option>
                    </select>
                </label>

                <label class="field">
                    <span>Urutan</span>
                    <input
                        class="input"
                        type="number"
                        name="order_no"
                        min="1"
                        max="{{ max(1, $question->newQuery()->count()) }}"
                        value="{{ old('order_no', $question->order_no) }}"
                        required
                    >
                </label>

                <p class="field-help">
                    Isi nomor urutan tujuan (contoh: dari 5 jadi 51). Nomor soal lain akan digeser otomatis.
                </p>
                <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
            </form>
        @else
            @php
                $rawRows = old('questions');
                $rows = is_array($rawRows) && count($rawRows) > 0
                    ? array_values($rawRows)
                    : [['category_id' => '', 'question' => '', 'is_active' => '1']];
            @endphp

            <form method="POST" action="{{ route('admin.questions.store') }}" class="form-grid" data-question-builder-form>
                @csrf

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
                                    <span>Kategori</span>
                                    <select class="input" name="questions[{{ $index }}][category_id]" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) ($row['category_id'] ?? '') === (string) $category->id)>
                                                {{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="field">
                                    <span>Pertanyaan</span>
                                    <textarea class="input" name="questions[{{ $index }}][question]" rows="5" required>{{ $row['question'] ?? '' }}</textarea>
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
                    <button class="btn btn-primary" type="submit">Simpan Semua Soal</button>
                </div>
            </form>

            <script>
                (() => {
                    const form = document.querySelector('[data-question-builder-form]');
                    if (!form) return;

                    const list = form.querySelector('[data-question-list]');
                    const addBtn = form.querySelector('[data-add-row]');
                    if (!list || !(addBtn instanceof HTMLButtonElement)) return;

                    const categoryOptions = @js(
                        $categories->map(fn ($category) => [
                            'id' => $category->id,
                            'label' => $category->name.($category->is_active ? '' : ' (Nonaktif)'),
                        ])->values()
                    );

                    let nextIndex = list.querySelectorAll('[data-row]').length;

                    const categoryOptionHtml = (selectedId = '') => {
                        const current = String(selectedId);
                        const options = categoryOptions
                            .map((option) => {
                                const isSelected = String(option.id) === current ? ' selected' : '';
                                return `<option value="${option.id}"${isSelected}>${option.label}</option>`;
                            })
                            .join('');

                        return `<option value="">Pilih Kategori</option>${options}`;
                    };

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
                                    <span>Kategori</span>
                                    <select class="input" name="questions[${index}][category_id]" required>
                                        ${categoryOptionHtml()}
                                    </select>
                                </label>

                                <label class="field">
                                    <span>Pertanyaan</span>
                                    <textarea class="input" name="questions[${index}][question]" rows="5" required></textarea>
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
                        const rows = Array.from(list.querySelectorAll('[data-row]'));
                        rows.forEach((row, index) => {
                            const number = row.querySelector('[data-row-number]');
                            if (number) {
                                number.textContent = String(index + 1);
                            }
                            syncSummary(row);
                        });
                    };

                    const syncSummary = (row) => {
                        if (!(row instanceof HTMLElement)) return;
                        const summaryEl = row.querySelector('[data-row-summary]');
                        const questionInput = row.querySelector('textarea[name$="[question]"]');
                        const categorySelect = row.querySelector('select[name$="[category_id]"]');
                        if (!(summaryEl instanceof HTMLElement)) return;

                        const rawText = questionInput instanceof HTMLTextAreaElement
                            ? questionInput.value.trim().replace(/\s+/g, ' ')
                            : '';
                        const shortText = rawText === '' ? 'Belum ada pertanyaan.' : rawText.slice(0, 92);

                        const selectedCategory = categorySelect instanceof HTMLSelectElement
                            ? categorySelect.selectedOptions[0]?.textContent?.trim() ?? 'Kategori belum dipilih'
                            : 'Kategori belum dipilih';

                        summaryEl.textContent = `${selectedCategory} • ${shortText}${rawText.length > 92 ? '…' : ''}`;
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

                    addBtn.addEventListener('click', () => {
                        list.insertAdjacentHTML('beforeend', rowTemplate(nextIndex));
                        nextIndex += 1;
                        const latestRow = list.querySelector('[data-row]:last-of-type');
                        if (latestRow instanceof HTMLElement) {
                            toggleRow(latestRow, false);
                            const textarea = latestRow.querySelector('textarea[name$="[question]"]');
                            if (textarea instanceof HTMLTextAreaElement) {
                                textarea.focus();
                            }
                        }
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
                    let activeDropTarget = null;
                    let armedDragRow = null;
                    let dragPreviewEl = null;
                    let autoScrollRafId = null;
                    let lastPointerY = null;

                    const rows = () => Array.from(list.querySelectorAll('[data-row]'));

                    const findDropTarget = (pointerY) => {
                        return rows()
                            .filter((row) => row !== draggedRow)
                            .find((row) => pointerY <= (row.getBoundingClientRect().top + (row.clientHeight / 2)));
                    };

                    const clearDropTargetHighlight = () => {
                        if (activeDropTarget instanceof HTMLElement) {
                            activeDropTarget.classList.remove('is-drop-target');
                        }
                        activeDropTarget = null;
                    };

                    const attachDragPreview = (sourceRow, dataTransfer) => {
                        if (!(sourceRow instanceof HTMLElement) || !dataTransfer) {
                            return;
                        }

                        const clone = sourceRow.cloneNode(true);
                        if (!(clone instanceof HTMLElement)) {
                            return;
                        }

                        clone.classList.add('drag-preview-card');
                        clone.style.width = `${sourceRow.offsetWidth}px`;
                        clone.style.position = 'fixed';
                        clone.style.top = '-9999px';
                        clone.style.left = '-9999px';
                        clone.style.margin = '0';
                        clone.style.pointerEvents = 'none';
                        document.body.appendChild(clone);
                        dataTransfer.setDragImage(clone, 28, 24);
                        dragPreviewEl = clone;
                    };

                    const cleanupDragPreview = () => {
                        if (dragPreviewEl instanceof HTMLElement) {
                            dragPreviewEl.remove();
                        }
                        dragPreviewEl = null;
                    };

                    const stopAutoScroll = () => {
                        if (autoScrollRafId !== null) {
                            window.cancelAnimationFrame(autoScrollRafId);
                            autoScrollRafId = null;
                        }
                        lastPointerY = null;
                    };

                    const startAutoScroll = () => {
                        if (autoScrollRafId !== null) {
                            return;
                        }

                        const edgeSize = 120;
                        const maxSpeed = 18;

                        const step = () => {
                            if (!(draggedRow instanceof HTMLElement)) {
                                stopAutoScroll();
                                return;
                            }

                            if (typeof lastPointerY === 'number') {
                                const viewportHeight = window.innerHeight;
                                let delta = 0;

                                if (lastPointerY < edgeSize) {
                                    delta = -Math.ceil(((edgeSize - lastPointerY) / edgeSize) * maxSpeed);
                                } else if (lastPointerY > viewportHeight - edgeSize) {
                                    delta = Math.ceil(((lastPointerY - (viewportHeight - edgeSize)) / edgeSize) * maxSpeed);
                                }

                                if (delta !== 0) {
                                    window.scrollBy(0, delta);
                                }
                            }

                            autoScrollRafId = window.requestAnimationFrame(step);
                        };

                        autoScrollRafId = window.requestAnimationFrame(step);
                    };

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
                        event.dataTransfer?.setData('text/plain', row.querySelector('[data-row-number]')?.textContent ?? '');
                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                        }
                        attachDragPreview(row, event.dataTransfer ?? null);
                        startAutoScroll();
                    });

                    list.addEventListener('dragover', (event) => {
                        if (!(draggedRow instanceof HTMLElement)) return;
                        event.preventDefault();
                        lastPointerY = event.clientY;
                        const dropTarget = findDropTarget(event.clientY);

                        if (dropTarget instanceof HTMLElement && dropTarget !== draggedRow) {
                            if (activeDropTarget !== dropTarget) {
                                clearDropTargetHighlight();
                                dropTarget.classList.add('is-drop-target');
                                activeDropTarget = dropTarget;
                            }
                        } else {
                            clearDropTargetHighlight();
                        }

                        if (dropTarget) {
                            list.insertBefore(draggedRow, dropTarget);
                        } else {
                            list.appendChild(draggedRow);
                        }
                    });

                    document.addEventListener('dragover', (event) => {
                        if (!(draggedRow instanceof HTMLElement)) return;
                        lastPointerY = event.clientY;
                    });

                    list.addEventListener('dragend', () => {
                        if (!(draggedRow instanceof HTMLElement)) return;
                        draggedRow.classList.remove('is-dragging');
                        clearDropTargetHighlight();
                        list.classList.remove('is-drag-active');
                        cleanupDragPreview();
                        stopAutoScroll();
                        draggedRow = null;
                        armedDragRow = null;
                        syncNumbers();
                    });

                    syncNumbers();
                })();
            </script>
        @endif
    </section>
</x-layouts.admin>
