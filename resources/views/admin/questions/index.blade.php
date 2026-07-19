<x-layouts.admin
    title="Manajemen Pustaka Soal"
    heading="Manajemen Pustaka Soal"
    subtitle="Kelola soal seperti kartu. Tarik kartu untuk ubah urutan, dan gunakan aksi massal untuk update cepat."
>
    <x-slot:actions>
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">Buat Soal (Bulk)</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.questions.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Pertanyaan</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari isi soal...">
        </label>
        <label class="field">
            <span>Kategori</span>
            <select class="input" name="category_id">
                <option value="">Semua Kategori</option>
                @foreach ($categoryOptions as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">
            <span>Status</span>
            <select class="input" name="is_active">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['is_active'] ?? '') === '1')>Aktif</option>
                <option value="0" @selected((string) ($filters['is_active'] ?? '') === '0')>Nonaktif</option>
            </select>
        </label>
        <label class="field">
            <span>Tampilan Data</span>
            <select class="input" name="per_page">
                <option value="20" @selected((string) ($filters['per_page'] ?? '20') === '20')>20 per halaman</option>
                <option value="50" @selected((string) ($filters['per_page'] ?? '20') === '50')>50 per halaman</option>
                <option value="100" @selected((string) ($filters['per_page'] ?? '20') === '100')>100 per halaman</option>
                <option value="all" @selected((string) ($filters['per_page'] ?? '20') === 'all')>Semua Soal (Mode Geser)</option>
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.questions.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    @if ($reorderNotice)
        <div class="alert warning">{{ $reorderNotice }}</div>
    @endif

    <p class="question-bank-toast" data-question-toast aria-live="polite"></p>

    <section
        class="question-bank-board"
        data-question-board
        data-can-reorder="{{ $canReorder ? '1' : '0' }}"
        data-reorder-url="{{ route('admin.questions.reorder') }}"
    >
        @forelse ($questions as $question)
            <article class="question-bank-card" data-question-card data-question-id="{{ $question->id }}" draggable="{{ $canReorder ? 'true' : 'false' }}">
                <div class="question-bank-card-head">
                    <label class="question-bank-select">
                        <input type="checkbox" value="{{ $question->id }}" data-row-check>
                        <span>Pilih</span>
                    </label>
                    <div class="question-bank-order-wrap">
                        <span class="question-bank-order-label">Urutan</span>
                        <strong class="question-bank-order-no" data-order-label>{{ $question->order_no }}</strong>
                    </div>
                    <button
                        type="button"
                        class="question-bank-drag-handle"
                        @disabled(! $canReorder)
                        aria-label="Tarik untuk ubah urutan soal"
                        title="{{ $canReorder ? 'Tarik untuk ubah urutan' : 'Aktifkan tanpa filter untuk drag urutan' }}"
                    >
                        <span class="question-bank-drag-icon" aria-hidden="true">⋮⋮</span>
                        <span class="question-bank-drag-text">Geser</span>
                    </button>
                </div>

                <p class="question-bank-category">{{ $question->masterCategory?->name ?? $question->category ?? '-' }}</p>
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
                Belum ada soal. Klik <strong>Buat Soal (Bulk)</strong> untuk menambahkan soal baru.
            </article>
        @endforelse
    </section>

    <form method="POST" action="{{ route('admin.questions.bulk') }}" class="bulk-bar" data-bulk-form="questions">
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

    @if (method_exists($questions, 'links'))
        {{ $questions->links() }}
    @endif

    <script>
        (() => {
            const board = document.querySelector('[data-question-board]');
            const form = document.querySelector('[data-bulk-form="questions"]');
            if (!board || !form) return;

            const canReorder = board.dataset.canReorder === '1';
            const reorderUrl = board.dataset.reorderUrl;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const hiddenIds = form.querySelector('[data-hidden-ids]');
            const countEl = form.querySelector('[data-selected-count]');
            const toastEl = document.querySelector('[data-question-toast]');
            let toastTimerId = null;

            const rowChecks = () => Array.from(board.querySelectorAll('[data-row-check]'));
            const selectedIds = () => rowChecks()
                .filter((el) => el instanceof HTMLInputElement && el.checked)
                .map((el) => el.value);
            const orderIds = () => Array.from(board.querySelectorAll('[data-question-card]'))
                .map((card) => Number(card.getAttribute('data-question-id')))
                .filter((id) => Number.isInteger(id) && id > 0);

            const syncSelection = () => {
                countEl.textContent = String(selectedIds().length);
            };

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

            const saveOrder = async (beforeIds, afterIds) => {
                try {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ ids: afterIds }),
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

            rowChecks().forEach((el) => {
                el.addEventListener('change', syncSelection);
            });

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

            syncSelection();
            syncOrderLabels();

            if (!canReorder) {
                return;
            }

            let draggedCard = null;
            let beforeDragIds = [];
            let dragPreviewEl = null;
            let autoScrollRafId = null;
            let lastPointerY = null;

            const cards = () => Array.from(board.querySelectorAll('[data-question-card]'));

            const findDropTarget = (pointerY) => {
                return cards()
                    .filter((card) => card !== draggedCard)
                    .find((card) => pointerY <= (card.getBoundingClientRect().top + (card.clientHeight / 2)));
            };

            const attachDragPreview = (sourceCard, dataTransfer) => {
                if (!(sourceCard instanceof HTMLElement) || !dataTransfer) {
                    return;
                }

                const clone = sourceCard.cloneNode(true);
                if (!(clone instanceof HTMLElement)) {
                    return;
                }

                clone.classList.add('drag-preview-card');
                clone.style.width = `${sourceCard.offsetWidth}px`;
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
                    if (!(draggedCard instanceof HTMLElement)) {
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

            board.addEventListener('dragstart', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                const card = target.closest('[data-question-card]');
                if (!(card instanceof HTMLElement)) return;
                draggedCard = card;
                beforeDragIds = orderIds();
                card.classList.add('is-dragging');
                event.dataTransfer?.setData('text/plain', card.getAttribute('data-question-id') ?? '');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                }
                attachDragPreview(card, event.dataTransfer ?? null);
                startAutoScroll();
            });

            board.addEventListener('dragover', (event) => {
                if (!(draggedCard instanceof HTMLElement)) return;
                event.preventDefault();
                lastPointerY = event.clientY;
                const dropTarget = findDropTarget(event.clientY);
                if (dropTarget) {
                    board.insertBefore(draggedCard, dropTarget);
                } else {
                    board.appendChild(draggedCard);
                }
            });

            document.addEventListener('dragover', (event) => {
                if (!(draggedCard instanceof HTMLElement)) return;
                lastPointerY = event.clientY;
            });

            board.addEventListener('dragend', async () => {
                if (!(draggedCard instanceof HTMLElement)) return;
                draggedCard.classList.remove('is-dragging');
                cleanupDragPreview();
                stopAutoScroll();
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
</x-layouts.admin>
